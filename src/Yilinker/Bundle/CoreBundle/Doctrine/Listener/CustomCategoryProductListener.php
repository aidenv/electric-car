<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup;

class CustomCategoryProductListener
{

    private $objectPersister;

    public function setObjectPersister($objectPersister)
    {
        $this->objectPersister = $objectPersister;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->reindexProduct($args);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->reindexProduct($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->reindexProduct($args);
    }

    private function reindexProduct(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof CustomizedCategoryProductLookup) {
            $product = $entity->getProduct();
            $this->objectPersister->insertOne($product);
        }
    }
}