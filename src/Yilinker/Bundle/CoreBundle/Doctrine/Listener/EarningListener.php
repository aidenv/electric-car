<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\StoreLevel;
use Yilinker\Bundle\CoreBundle\Services\Store\StoreService;

/**
 * Class EarningListener
 * @package Yilinker\Bundle\CoreBundle\Doctrine\Listener
 */
class EarningListener
{
    private $container;

    /**
     * Constructor
     *
     * @param $container
     */
    public function __construct ($container)
    {
        $this->container = $container;
    }

    public function prePersist (LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Earning) {
            $this->updateAccreditationLevel($entity, $args);
        }

    }

    public function preUpdate (LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Earning && $entity->getStatus() == Earning::COMPLETE ) {
            $this->updateAccreditationLevel($entity, $args);
        }

    }

    /**
     * Update store level base on earnings
     *
     * @param Earning $earning
     * @param LifecycleEventArgs $args
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateAccreditationLevel (Earning $earning, LifecycleEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $storeLevels = $this->container->get('yilinker_core.service.entity.store')->getStoreLevel();
        $totalEarning = floatval($entityManager->getRepository('YilinkerCoreBundle:Earning')->getTotalEarningByUser($earning->getUser())) + floatval($earning->getAmount());
        $storeEntity = $earning->getUser()->getStore();

        if ($storeEntity) {
            if (!($storeEntity->getStoreLevel() instanceof StoreLevel)) {
                $storeLevelSilver = $entityManager->getReference('YilinkerCoreBundle:StoreLevel', StoreLevel::STORE_LEVEL_SILVER);
                $storeEntity->setStoreLevel($storeLevelSilver);
            }

            foreach ($storeLevels as $storeLevel) {
                $storeLevelId = $storeLevel['storeLevelId'];
                $min = $storeLevel['storeEarning']['min'];
                $max = $storeLevel['storeEarning']['max'];
                $storeLevelEntity = $entityManager->getReference('YilinkerCoreBundle:StoreLevel', $storeLevelId);

                if (floatval($totalEarning) > $min && floatval($totalEarning) <= $max && $storeEntity->getStoreLevel()->getStoreLevelId() != $storeLevelId) {
                    $storeEntity->setStoreLevel($storeLevelEntity);
                }
            }
        }
    }

}
