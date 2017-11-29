<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;

class ProductCountryListener
{
    private $container;
    private $objectPersister;

    public function setContainer($container)
    {
        $this->container = $container;
        $this->objectPersister = $this->container->get('fos_elastica.object_persister.yilinker_online.product');

        return $this;
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
        if ($entity instanceof ProductCountry) {
            $product = $entity->getProduct();
            $this->objectPersister->insertOne($product);
        }
    }
}
