<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;

class UserImageListener
{
    private $serviceContainer;

    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $assetsHelper = $this->serviceContainer->get('templating.helper.assets');
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof UserImage) {
            $fullImagePath = $assetsHelper->getUrl($entity->getImageLocation(), 'user');
            $entity->setFullImagePath($fullImagePath);
        }
    }
}
