<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\DisputeMessage;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;

class DisputeMessageListener
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $disputeMessage = $args->getEntity();
        if ($disputeMessage instanceof DisputeMessage) {
            $em = $args->getEntityManager();
            $inProcessStatus = $em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProduct::STATUS_DISPUTE_IN_PROCESS);
            $dispute = $disputeMessage->getDispute();
            $disputeDetails = $dispute->getDisputeDetails();
            foreach ($disputeDetails as $disputeDetail) {
                // todo: dont change status of order product for some order product
                // with status that indicates that the dispute for order product
                // is already resolved
                $orderProduct = $disputeDetail->getOrderProduct();
                $orderProduct->setOrderProductStatus($inProcessStatus);
            }
        }
    }
}