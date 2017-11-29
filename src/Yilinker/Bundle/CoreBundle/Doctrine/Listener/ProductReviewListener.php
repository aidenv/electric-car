<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Yilinker\Bundle\CoreBundle\Entity\Earning;

use Doctrine\ORM\EntityManager;

class ProductReviewListener
{
    private $serviceContainer;

    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $earner = $this->serviceContainer->get('yilinker_core.service.earner');

        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof ProductReview) {
            /**
             * Sanity check to make sure a point is only earned when the review is new
             */
            $existingProductReview = $entityManager->getRepository('YilinkerCoreBundle:ProductReview')
                                                   ->findOneBy(array(
                                                       'reviewer' => $entity->getReviewer(),
                                                       'product' => $entity->getProduct(),
                                                       'orderProduct' => $entity->getOrderProduct(),
                                                   ));
            if($existingProductReview === null){
                $earner->get($entity)
                       ->earn();
            }
        }
    }
}
