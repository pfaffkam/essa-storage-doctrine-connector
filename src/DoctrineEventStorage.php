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
        $this->eventConverter = new DoctrineEventConverter($eventSerializer, $eventClassResolver);
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
        $entityClass = $this->config->entity;

        $doctrineEvents = $this->em->getRepository($entityClass)->findBy(
            ['aggregateId' => $aggregateId]
        );

        return array_map(
            fn (DoctrineEvent $event) => $this->eventConverter->fromDoctrineEvent($event),
            $doctrineEvents
        );
    }
}
