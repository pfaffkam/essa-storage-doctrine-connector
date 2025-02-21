<?php

namespace PfaffKIT\Essa\Adapters\Storage\Tests\mocks;

use PfaffKIT\Essa\EventSourcing\AbstractAggregateEvent;

readonly class TestAggregateEvent extends AbstractAggregateEvent
{
    public function __construct(
        public string $stringData,
    ) {
        parent::__construct();
    }

    public static function getEventName(): string
    {
        return 'test_event';
    }
}
