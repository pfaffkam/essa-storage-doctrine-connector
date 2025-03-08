<?php

namespace PfaffKIT\Essa\Adapters\Storage\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PfaffKIT\Essa\EventSourcing\Serializer\JsonEventSerializer;
use PfaffKIT\Essa\Shared\Identity;

#[
    ORM\MappedSuperclass(repositoryClass: DoctrineEventRepository::class)
]
abstract readonly class DoctrineEvent
{
    #[
        ORM\Id,
        ORM\Column(name: 'id', type: Types::GUID),
        ORM\GeneratedValue(strategy: 'NONE'),
    ]
    public string $id;

    #[ORM\Column(name: 'aggregate_id', type: Types::GUID)]
    public string $aggregateId;

    #[ORM\Column(name: 'event_name', type: Types::STRING)]
    public string $eventName;

    #[ORM\Column(name: 'occurred_on', type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $timestamp;

    #[ORM\Column(name: 'payload', type: Types::TEXT)]
    public string $payload;

    public function __construct(
        string $id,
        string $aggregateId,
        string $eventName,
        \DateTimeImmutable $timestamp,
        string $payload,
    ) {
        $this->id = $id;
        $this->aggregateId = $aggregateId;
        $this->eventName = $eventName;
        $this->timestamp = $timestamp;
        $this->payload = $payload;
    }

    public static function fromNormalizedAggregateEventWithSerializedPayload(Identity $aggregateId, array $event): self
    {
        return new static(
            $event['_id'],
            (string) $aggregateId,
            $event['_name'],
            new \DateTimeImmutable($event['_timestamp']),
            $event['_payload'],
        );
    }

    public function toNormalizedArrayWithNormalizedPayload(): array
    {
        return [
            '_id' => $this->id,
            '_aggregateId' => $this->aggregateId,
            '_name' => $this->eventName,
            '_timestamp' => $this->timestamp->format(JsonEventSerializer::DATE_TIME_FORMAT),
            '_payload' => $this->payload,
        ];
    }
}
