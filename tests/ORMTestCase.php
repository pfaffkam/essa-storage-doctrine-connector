<?php

namespace PfaffKIT\Essa\Adapters\Storage\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PfaffKIT\Essa\Adapters\Storage\Entity\DoctrineEvent;
use PfaffKIT\Essa\Adapters\Storage\Tests\Entity\TestEvent;
use PHPUnit\Framework\TestCase;

abstract class ORMTestCase extends TestCase
{
    protected static ?EntityManagerInterface $entityManager = null;
    protected EntityManagerInterface $em;

    public static function setUpBeforeClass(): void
    {
        if (null === self::$entityManager) {
            $config = ORMSetup::createAttributeMetadataConfiguration([
                __DIR__.'/Entity',
                __DIR__.'/../src/Entity',
            ], true);

            $connection = DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'memory' => true,
            ], $config);

            self::$entityManager = new EntityManager($connection, $config);

            // Create the schema once for all tests
            $schemaTool = new SchemaTool(self::$entityManager);
            $schemaTool->dropDatabase();
            $schemaTool->createSchema([
                self::$entityManager->getClassMetadata(TestEvent::class),
                self::$entityManager->getClassMetadata(DoctrineEvent::class),
            ]);
        }
    }

    protected function setUp(): void
    {
        $this->em = self::$entityManager;

        // Clear the database between tests
        $this->em->clear();

        // Begin a transaction for each test
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback the transaction after each test
        if ($this->em->getConnection()->isTransactionActive()) {
            $this->em->rollback();
        }
    }
}
