<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;

class PayoutRequestListener
{
    public function postPersist(LifecycleEventArgs $args)
    {
        $payoutRequest = $args->getEntity();
        if ($payoutRequest instanceof PayoutRequest) {
            $em = $args->getEntityManager();
            $payoutRequest->setReferenceNumber('WR-O-PH-0-'.$payoutRequest->getPayoutRequestId());
            $em->flush();
        }
    }
}