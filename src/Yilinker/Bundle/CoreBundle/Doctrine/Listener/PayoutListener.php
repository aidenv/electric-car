<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Entity\Payout;
use Doctrine\ORM\Event\LifecycleEventArgs;

class PayoutListener
{    

    public function createReferenceNumber(Payout $payout, $args)
    {
        $entityManager = $args->getEntityManager();
        $date = date('YmdHis');
        $admiUser = $payout->getAdminUser();        
        $referenceNumber = $admiUser->getAdminUserId().$date;

        $payout->setReferenceNumber($referenceNumber);
        $entityManager->flush();
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Payout) {
            if(is_null($entity->getReferenceNumber())){
                $this->createReferenceNumber($entity, $args);
            }
        }
    }
        
}
