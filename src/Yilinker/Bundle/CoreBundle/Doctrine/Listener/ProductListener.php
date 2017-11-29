<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\Product;

class ProductListener
{
    private $container;
    private $objectPersister;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function setObjectPersister($objectPersister)
    {
        $this->objectPersister = $objectPersister;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->defaultLocale($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->reindexStore($args);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->reindexStore($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->reindexStore($args);
        // $this->updateCountryStatus($args);
    }

    //for removal
    public function updateCountryStatus($args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Product) {
            if (in_array($entity->getStatus(), array(Product::INACTIVE, Product::DELETE))) {
                $em = $args->getEntityManager();
                $conn = $em->getConnection();
                $conn->exec('UPDATE `ProductCountry` SET `status` = '.$entity->getStatus().' WHERE `product_id` = '.$entity->getProductId());   
            }
        }
    }

    private function reindexStore(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof Product) {
            $store = $entity->getUser()->getStore();
            $this->objectPersister->insertOne($store);
        }
    }

    private function defaultLocale(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Product) {

            if (!$entity->getDefaultLocale()) {
                $locale = $entity->getLocale();
                if (!$locale) {
                    $trans = $this->container->get('yilinker_core.translatable.listener');
                    $locale = $trans->getListenerLocale();
                }
                
                $entity->setDefaultLocale($locale);
            }
        }
    }
}