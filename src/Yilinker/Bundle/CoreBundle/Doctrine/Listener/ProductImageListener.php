<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;

class ProductImageListener
{
    private $serviceContainer;

    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $assetsHelper = $this->serviceContainer->get('templating.helper.assets');
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof ProductImage) {
            $fullImagePath = $assetsHelper->getUrl($entity->getImageLocation(), 'product');
            $entity->setFullImagePath($fullImagePath);
        }
    }
}
