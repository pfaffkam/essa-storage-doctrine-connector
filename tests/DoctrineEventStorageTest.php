<?php

declare(strict_types=1);

namespace PfaffKIT\Essa\Adapters\Storage\Tests;

use PfaffKIT\Essa\Adapters\Storage\Config\Config;
use PfaffKIT\Essa\Adapters\Storage\DoctrineEventStorage;
use PfaffKIT\Essa\Adapters\Storage\Tests\Entity\TestEvent;
use PfaffKIT\Essa\Adapters\Storage\Tests\mocks\TestAggregateEvent;
use PfaffKIT\Essa\EventSourcing\EventClassResolver;
use PfaffKIT\Essa\EventSourcing\Serializer\JsonEventSerializer;
use PfaffKIT\Essa\EventSourcing\Storage\EventStorage;
use PfaffKIT\Essa\Shared\Id;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DoctrineEventStorage::class)]
final class DoctrineEventStorageTest extends ORMTestCase
{
    private EventStorage $eventStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Config(TestEvent::class);
        $this->eventStorage = new DoctrineEventStorage(
            $this->em,
            $config,
            new JsonEventSerializer(),
            new EventClassResolver([
                TestAggregateEvent::class,
            ])
        );
    }

    public function testStoreEvents(): void
    {
        // Arrange
        $aggregateId = Id::new();
        $event = new TestAggregateEvent('test data', $aggregateId);

        // Act
        $this->eventStorage->save($aggregateId, [$event]);

        // Assert
        $storedEvents = $this->em->getRepository(TestEvent::class)->findAll();
        $this->assertCount(1, $storedEvents);
        $this->assertSame((string) $aggregateId, $storedEvents[0]->aggregateId);
    }

    public function testLoadEvents(): void
    {
        // Arrange
        $aggregateId = Id::new();
        $event = new TestAggregateEvent('test data', $aggregateId);

        // Act
        $this->eventStorage->save($aggregateId, [$event]);
        $loadedEvents = $this->eventStorage->load($aggregateId);

        // Assert
        $this->assertCount(1, $loadedEvents);
        $this->assertInstanceOf(TestAggregateEvent::class, $loadedEvents[0]);
        $this->assertSame('test_event', $loadedEvents[0]->getEventName());
        $this->assertSame('test data', $loadedEvents[0]->stringData);
    }

    public function testHandleMultipleEvents(): void
    {
        // Arrange
        $aggregateId = Id::new();
        $event1 = new TestAggregateEvent('foo', $aggregateId);
        $event2 = new TestAggregateEvent('bar', $aggregateId);
        $event3 = new TestAggregateEvent('baz', $aggregateId);

        $events = [$event1, $event2, $event3];

        // Act
        $this->eventStorage->save($aggregateId, $events);
        $loadedEvents = $this->eventStorage->load($aggregateId);

        // Assert
        $this->assertCount(3, $loadedEvents);

        // Verify the order and types
        $this->assertInstanceOf(TestAggregateEvent::class, $loadedEvents[0]);
        $this->assertInstanceOf(TestAggregateEvent::class, $loadedEvents[1]);
        $this->assertInstanceOf(TestAggregateEvent::class, $loadedEvents[2]);

        // Verify the data is preserved
        $this->assertSame('foo', $loadedEvents[0]->stringData);
        $this->assertSame('bar', $loadedEvents[1]->stringData);
        $this->assertSame('baz', $loadedEvents[2]->stringData);
    }

    public function testReturnsEmptyArrayWhenNoEventsFound(): void
    {
        // Act
        $events = $this->eventStorage->load(Id::new());

        // Assert
        $this->assertIsArray($events);
        $this->assertEmpty($events);
    }
}
