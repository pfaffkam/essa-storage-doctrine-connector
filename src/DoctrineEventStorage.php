<?php

namespace PfaffKIT\Essa\Adapters\Storage;

use Doctrine\ORM\EntityManagerInterface;
use PfaffKIT\Essa\Adapters\Storage\Config\Config;
use PfaffKIT\Essa\Adapters\Storage\Entity\DoctrineEvent;
use PfaffKIT\Essa\EventSourcing\AggregateEvent;
use PfaffKIT\Essa\EventSourcing\EventClassResolver;
use PfaffKIT\Essa\EventSourcing\Exception\UnresolvableEventException;
use PfaffKIT\Essa\EventSourcing\Serializer\EventSerializer;
use PfaffKIT\Essa\EventSourcing\Storage\EventStorage;
use PfaffKIT\Essa\Shared\Identity;

readonly class DoctrineEventStorage implements EventStorage
{
    private DoctrineEventConverter $eventConverter;

    public function __construct(
        private EntityManagerInterface $em,
        private Config $config,
        EventSerializer $eventSerializer,
        EventClassResolver $eventClassResolver,
    ) {
        $this->eventConverter = new DoctrineEventConverter($eventSerializer, $eventClassResolver, $config);
    }

    public function save(Identity $aggregateId, array $aggregateEvents): void
    {
        foreach ($aggregateEvents as $event) {
            $doctrineEvent = $this->eventConverter->toDoctrineEvent($aggregateId, $event);
            $this->em->persist($doctrineEvent);
        }

        $this->em->flush();
    }

    /**
     * @return AggregateEvent[]
     *
     * @throws UnresolvableEventException
     */
    public function load(Identity $aggregateId): array
    {
        $doctrineEvents = $this->em->getRepository($this->config->entity)->findBy(
            ['aggregateId' => $aggregateId],
            ['timestamp' => 'ASC']
        );

        return array_map(
            fn (DoctrineEvent $event) => $this->eventConverter->fromDoctrineEvent($event),
            $doctrineEvents
        );
    }

    /**
     * @param array<class-string<AggregateEvent>> $eventTypes
     */
    public function loadInBatches(
        int $offset = 0,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        array $eventTypes = [],
    ): iterable {
        $repository = $this->em->getRepository($this->config->entity);
        $queryBuilder = $repository->createQueryBuilder('e')
            ->orderBy('e.timestamp', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($batchSize);

        if (!empty($eventTypes)) {
            // getEventName for each eventType
            $eventNames = array_map(fn (string $eventType) => $eventType::getEventName(), $eventTypes);
            $queryBuilder->andWhere('e.eventName IN (:eventNames)')
                ->setParameter('eventNames', $eventNames);
        }

        while (true) {
            $query = $queryBuilder->getQuery();
            $results = $query->getResult();

            if (empty($results)) {
                break;
            }

            // Convert Doctrine events to domain events
            $events = array_map(
                fn (DoctrineEvent $event) => $this->eventConverter->fromDoctrineEvent($event),
                $results
            );

            yield $events;

            // If we got fewer results than the batch size, we've reached the end
            if (count($results) < $batchSize) {
                break;
            }

            // Update the query for the next batch
            $lastId = end($results)->getId();
            $queryBuilder->andWhere('e.id > :lastId')
                ->setParameter('lastId', $lastId)
                ->setFirstResult(0); // Reset offset for subsequent queries
        }
    }
}
