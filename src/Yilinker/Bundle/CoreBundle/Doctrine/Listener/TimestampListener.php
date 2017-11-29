<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Proxy as EntityProxy;

class TimestampListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $disabledTimestamp = isset($entity->disabledTimestamp) && $entity->disabledTimestamp;
        if ($disabledTimestamp) {
            return;
        }

        $em = $args->getEntityManager();
        if (method_exists($entity, 'setDateAdded')) {
            if (!$entity->getDateAdded()) {
                $now = new \DateTime;
                $entity->setDateAdded($now);
            }
        }

        if (method_exists($entity, 'setDateCreated')) {
            if (!$entity->getDateCreated()) {
                $now = new \DateTime;
                $entity->setDateCreated($now);
            }
        }

        $this->updateLastModified($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->updateLastModified($args);
    }

    public function updateLastModified(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $disabledTimestamp = isset($entity->disabledTimestamp) && $entity->disabledTimestamp;
        if ($disabledTimestamp) {
            return;
        }

        $em = $args->getEntityManager();
        if (method_exists($entity, 'setLastDateModified')) {
            $now = new \DateTime;
            $entity->setLastDateModified($now);
        }

        if (method_exists($entity, 'setDateLastModified')) {
            $now = new \DateTime;
            $entity->setDateLastModified($now);
        }

        if (method_exists($entity, 'setLastModifiedDate')) {
            $now = new \DateTime;
            $entity->setLastModifiedDate($now);
        }
    }
}
