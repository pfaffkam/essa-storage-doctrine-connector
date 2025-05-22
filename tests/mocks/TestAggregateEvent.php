<?php

namespace PfaffKIT\Essa\Adapters\Storage\Tests\mocks;

use PfaffKIT\Essa\EventSourcing\AbstractAggregateEvent;
use PfaffKIT\Essa\Shared\Identity;

readonly class TestAggregateEvent extends AbstractAggregateEvent
{
    public function __construct(
        public string $stringData,
        ?Identity $aggregateId = null,
    ) {
        parent::__construct(null, null, $aggregateId);
    }

    public static function getEventName(): string
    {
        return 'test_event';
    }
}
