<?php

namespace NSM\Bundle\DTOMapperBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MappingCompilerPass implements CompilerPassInterface
{
    const MAPPER_KEY = 'dto_mapper';
    const MAPPING_TAG = 'dto_mapping';
    
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::MAPPER_KEY)) {
            return;
        }
        
        $mapper = $container->getDefinition(self::MAPPER_KEY);
        
        $mappingIds = [];
        foreach ($container->findTaggedServiceIds(self::MAPPING_TAG) as $id => $tag) {
            $mappingIds[] = $id;
        }
        
        foreach ($mappingIds as $mappingId) {
            $mapper->addMethodCall('registerCustomMapping', [$container->getDefinition($mappingId)]);
        }
    }
}