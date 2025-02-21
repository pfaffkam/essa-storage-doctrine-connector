<?php

namespace PfaffKIT\Essa\Adapters\Storage;

use PfaffKIT\Essa\Adapters\Storage\Entity\DoctrineEvent;
use PfaffKIT\Essa\EventSourcing\AggregateEvent;
use PfaffKIT\Essa\EventSourcing\EventClassResolver;
use PfaffKIT\Essa\EventSourcing\Exception\UnresolvableEventException;
use PfaffKIT\Essa\EventSourcing\Serializer\EventSerializer;
use PfaffKIT\Essa\Shared\Identity;

readonly class DoctrineEventConverter
{
    public function __construct(
        private EventSerializer $eventSerializer,
        private EventClassResolver $eventClassResolver,
    ) {}

    public function toDoctrineEvent(Identity $aggregateId, AggregateEvent $event): DoctrineEvent
    {
        $normalizedEvent = $this->eventSerializer->normalize($event);
        $normalizedEvent['_payload'] = $this->eventSerializer->encode($normalizedEvent['_payload']);

        $class = 'App\Entity\Event';

        return $class::fromNormalizedAggregateEventWithSerializedPayload($aggregateId, $normalizedEvent);
    }

    public function fromDoctrineEvent(DoctrineEvent $doctrineEvent): AggregateEvent
    {
        $event = $doctrineEvent->toNormalizedArrayWithNormalizedPayload();
        $event['_payload'] = $this->eventSerializer->decode($event['_payload']);

        $type = $this->eventClassResolver->resolve($event['_name']);

        if (!$type) {
            throw new UnresolvableEventException($event['_name']);
        }

        return $this->eventSerializer->denormalize(
            $event,
            $type
        );
    }
}
