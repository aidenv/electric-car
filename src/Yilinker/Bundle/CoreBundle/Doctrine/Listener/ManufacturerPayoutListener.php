<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ManufacturerPayoutListener
{

    public function createReferenceNumber(ManufacturerPayout $payout, $args)
    {
        $entityManager = $args->getEntityManager();
        $date = date('YmdHis');
        $adminUser = $payout->getAdminUser();
        $referenceNumber = $adminUser->getAdminUserId().$date;

        $payout->setReferenceNumber($referenceNumber);
        $entityManager->flush();
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof ManufacturerPayout) {
            if(is_null($entity->getReferenceNumber())){
                $this->createReferenceNumber($entity, $args);
            }
        }
    }

}
