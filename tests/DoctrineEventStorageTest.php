<?php

namespace PfaffKIT\Essa\Adapters\Storage\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use PfaffKIT\Essa\Adapters\Storage\DoctrineEventStorage;
use PfaffKIT\Essa\Adapters\Storage\Entity\DoctrineEvent;
use PfaffKIT\Essa\Adapters\Storage\Tests\mocks\TestAggregateEvent;
use PfaffKIT\Essa\Adapters\Storage\Tests\mocks\TestAnotherAggregateEvent;
use PfaffKIT\Essa\EventSourcing\EventClassResolver;
use PfaffKIT\Essa\EventSourcing\Serializer\JsonEventSerializer;
use PfaffKIT\Essa\EventSourcing\Storage\EventStorage;
use PfaffKIT\Essa\Shared\Id;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DoctrineEventStorage::class)]
class DoctrineEventStorageTest extends ORMTestCase
{
    protected readonly EventStorage $eventStorage;

    protected function setUp(): void
    {
        parent::setUp();

        new SchemaTool($this->em)->createSchema([
            $this->em->getClassMetadata(DoctrineEvent::class),
        ]);

        $this->eventStorage = new DoctrineEventStorage(
            $this->em,
            new JsonEventSerializer(),
            new EventClassResolver([TestAggregateEvent::class, TestAnotherAggregateEvent::class])
        );
    }

    public function testStore(): void
    {
        $this->eventStorage->save(Id::new(), [
            new TestAggregateEvent(
                'sample string data',
            ),
        ]);

        $this->assertCount(1, $this->em->getRepository(DoctrineEvent::class)->findAll());
    }

    public function testStoreAndLoadSameData(): void
    {
        $this->eventStorage->save($aggregateId = Id::new(), [
            new TestAggregateEvent(
                'sample string data',
            ),
        ]);

        $events = $this->eventStorage->load($aggregateId);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TestAggregateEvent::class, $events[0]);
        $this->assertEquals('test_event', $events[0]->getEventName());
        $this->assertEquals('sample string data', $events[0]->stringData);
    }

    public function testStoreAndLoadMultipleEvents(): void
    {
        $this->eventStorage->save($aggregateId = Id::new(), [
            $e1 = new TestAggregateEvent(
                'sample string data',
            ),
            $e2 = new TestAggregateEvent(
                'another sample string data',
            ),
            $e3 = new TestAnotherAggregateEvent(
                'x data',
                'y data',
            ),
            $e4 = new TestAnotherAggregateEvent(
                'another x data',
                'another y data',
            ),
        ]);

        $events = $this->eventStorage->load($aggregateId);

        $this->assertCount(4, $events);
        $this->assertInstanceOf(TestAggregateEvent::class, $events[0]);
        $this->assertInstanceOf(TestAggregateEvent::class, $events[1]);
        $this->assertInstanceOf(TestAnotherAggregateEvent::class, $events[2]);
        $this->assertInstanceOf(TestAnotherAggregateEvent::class, $events[3]);
        $this->assertEquals([$e1, $e2, $e3, $e4], $events);
    }
}
