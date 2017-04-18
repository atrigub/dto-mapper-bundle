<?php

namespace NSM\Bundle\DTOMapperBundle;

use NSM\Bundle\DTOMapperBundle\DependencyInjection\Compiler\MappingCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DTOMapperBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MappingCompilerPass());
    }
}
