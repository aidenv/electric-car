<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Entity\PayoutOrderProduct;

class PayoutOrderProductListener
{    

    public function createReferenceNumber($payout)
    {
        
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof PayoutOrderProduct) {
            if(is_null($entity->getReferenceNumber())){
                $entity->setReferenceNumber();
            }
        }
    }
        
}
