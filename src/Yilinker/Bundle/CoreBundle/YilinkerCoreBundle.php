<?php

namespace Yilinker\Bundle\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Yilinker\Bundle\CoreBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class YilinkerCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }   

}
