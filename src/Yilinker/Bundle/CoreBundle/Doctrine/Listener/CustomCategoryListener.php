<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory;

class CustomCategoryListener
{

    private $productObjectPersister;

    private $storeObjectPersister;

    public function setProductObjectPersister($productObjectPersister)
    {
        $this->productObjectPersister = $productObjectPersister;
    }

    public function setStoreObjectPersister($storeObjectPersister)
    {
        $this->storeObjectPersister = $storeObjectPersister;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->reindex($args);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->reindex($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->reindex($args);
    }

    private function reindex(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if ($entity instanceof CustomizedCategory) {

            $productLookups = $entity->getProductsLookup();

            foreach($productLookups as $productLookup){
                $product = $productLookup->getProduct();
                $this->productObjectPersister->insertOne($product);
            }

            $store = $entity->getUser()->getStore();
            $this->storeObjectPersister->insertOne($store);
        }
    }
}
