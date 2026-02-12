<?php

namespace PfaffKIT\Essa\Adapters\Storage\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
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

    #[ORM\Column(name: 'occurred_on', type: Types::BIGINT)]
    public int $timestamp;

    #[ORM\Column(name: 'version', type: Types::INTEGER, options: ['default' => 1])]
    public int $version;

    #[ORM\Column(name: 'payload', type: Types::TEXT)]
    public string $payload;

    public function __construct(
        string $id,
        string $aggregateId,
        string $eventName,
        int $timestamp,
        int $version,
        string $payload,
    ) {
        $this->id = $id;
        $this->aggregateId = $aggregateId;
        $this->eventName = $eventName;
        $this->timestamp = $timestamp;
        $this->version = $version;
        $this->payload = $payload;
    }

    public static function fromNormalizedAggregateEventWithSerializedPayload(Identity $aggregateId, array $event): self
    {
        return new static(
            $event['_id'],
            (string) $aggregateId,
            $event['_name'],
            $event['_timestamp'],
            $event['_version'],
            $event['_payload'],
        );
    }

    public function toNormalizedArrayWithNormalizedPayload(): array
    {
        return [
            '_id' => $this->id,
            '_aggregateId' => $this->aggregateId,
            '_name' => $this->eventName,
            '_timestamp' => $this->timestamp,
            '_version' => $this->version,
            '_payload' => $this->payload,
        ];
    }
}
