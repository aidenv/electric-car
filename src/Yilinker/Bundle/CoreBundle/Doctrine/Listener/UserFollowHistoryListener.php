<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Doctrine\ORM\EntityManager;

class UserFollowHistoryListener
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
        if ($entity instanceof UserFollowHistory) {  
            if($entity->getIsFollow()){
                $earner->get($entity)
                       ->earn();
            }
        }

    }
}
