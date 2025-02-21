<?php

namespace PfaffKIT\Essa\Adapters\Storage\Config;

use Doctrine\ORM\EntityManagerInterface;
use PfaffKIT\Essa\Adapters\Storage\Entity\DoctrineEvent;
use PfaffKIT\Essa\Internal\Configurator;
use PfaffKIT\Essa\Internal\ConfiguratorLogWriter;
use PfaffKIT\Essa\Internal\ExtensionConfigChanger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @internal
 */
final readonly class EntityConfigurator implements Configurator
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $rootDir,
        private EntityManagerInterface $em,
    ) {}

    public static function getExtensionName(): string
    {
        return 'storage_doctrine_connector';
    }

    public function shouldConfigure(): bool
    {
        return !$this->isEventEntityExists();
    }

    public function configure(ConfiguratorLogWriter $log, ExtensionConfigChanger $configChanger): void
    {
        $log->info('Creating default Event entity class...');
        $eventPath = $log->ask(
            'Where should the Event entity class be created?',
            'src/Entity/Event.php',
            fn ($path) => (bool) preg_match('/^(?:\w+\/)+\w+\.php$/', $path)
        );
        $entityFQCN = $this->createEntityClass($eventPath);
        $configChanger->set('entity', $entityFQCN);
        $log->tip([
            'Default event class have been created in src/Entity/Event.php file.',
            'You should consider relocate it to proper namespace and directory.',
        ]);
        $log->tip([
            'Also - it is recommended to store events in another database to avoid performance issues.',
            'You can configure that in config/doctrine.yaml file.',
        ]);
        $log->tip([
            'Due to new entity class creation, you should create migrations and update database schema using:',
            'php bin/console make:migration',
            'php bin/console doctrine:migrations:migrate',
        ]);
    }

    private function isEventEntityExists(): bool
    {
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        return array_any($metadata, fn ($meta) => DoctrineEvent::class == ($meta->reflClass->getParentClass()->name ?? false));
    }

    private function createEntityClass(string $eventPath): string
    {
        $entityDestFile = $this->rootDir.DIRECTORY_SEPARATOR.$eventPath;
        $entityNamespace = str_replace('/', '\\', preg_replace('/\.php$/', '', $eventPath));
        $entityClassName = basename($entityNamespace, '.php');

        $content = file_get_contents(__DIR__.'..'.DIRECTORY_SEPARATOR.'EventEntity.php.template');
        $content = str_replace('%%namespace%%', $entityNamespace, $content);

        //file_put_contents($entityDestFile, $content);

        return $entityNamespace.'\\'.$entityClassName;
    }
}
