<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

trait ContainerHandler
{
    private $mainContainer;

    public function setMainContainer($container)
    {
    	$this->mainContainer = $container;
    }

    public function getMainContainer()
    {
    	return $this->mainContainer;
    }
}
