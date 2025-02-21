<?php

namespace PfaffKIT\Essa\Adapters\Storage\Tests\mocks;

use PfaffKIT\Essa\EventSourcing\AbstractAggregateEvent;

readonly class TestAnotherAggregateEvent extends AbstractAggregateEvent
{
    public function __construct(
        public string $xData,
        public string $yData,
    ) {
        parent::__construct();
    }

    public static function getEventName(): string
    {
        return 'another_test_event';
    }
}
