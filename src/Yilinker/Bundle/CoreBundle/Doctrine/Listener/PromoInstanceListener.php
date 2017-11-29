<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;

class PromoInstanceListener
{

    private $objectPersister;

    public function setObjectPersister($objectPersister)
    {
        $this->objectPersister = $objectPersister;
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->insert($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->insert($args);
    }

    private function insert(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof PromoInstance){
            $this->indexProduct($entity);
        }
    }

    public function indexProduct(PromoInstance $promoInstance)
    {
        $productPromoMaps = $promoInstance->getProductPromoMap();

        foreach($productPromoMaps as $productPromoMap){
            $product = $productPromoMap->getProductUnit()->getProduct();
            $this->objectPersister->insertOne($product);
        }
    }
}