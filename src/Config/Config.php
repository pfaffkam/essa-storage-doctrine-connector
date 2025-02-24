<?php

namespace PfaffKIT\Essa\Adapters\Storage\Config;

use PfaffKIT\Essa\Internal\ExtensionConfig;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class Config extends ExtensionConfig
{
    public function __construct(
        public readonly string $entity,
    ) {}

    public static function instantiate(array $config): self
    {
        return new self(
            $config['entity']
        );
    }

    public static function getExtensionName(): string
    {
        return 'storage_doctrine_connector';
    }

    public static function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('entity')->defaultValue('App\Entity\Event')->end();
    }
}
