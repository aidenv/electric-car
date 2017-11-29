<?php 

namespace Yilinker\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('fos_elastica.finder.yilinker_online.store');
        $definition->setClass('Yilinker\Bundle\CoreBundle\Services\Search\Finder\StoreTransformedFinder');
    }
}
