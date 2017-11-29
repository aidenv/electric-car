<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory;
use Doctrine\Common\Persistence\Proxy as EntityProxy;

class UserActivityListener
{
    protected $requestStack;
    protected $tokenStorage;
    protected $em;
    protected $container;
    protected $logService;

    public function __construct($requestStack, $tokenStorage, $container)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->container = $container;
    }

    private function getEntityChange($entity)
    {
        $class = $this->em->getClassMetadata(get_class($entity));
        $uow = $this->em->getUnitOfWork();
        $uowEntityChanges = $uow->getEntityChangeSet($entity);
        $changes = array();
        foreach ($uowEntityChanges as $column => $change) {
            if (!$this->logService->isLoggableColumn($entity, $column)) {
                continue;
            }

            if ($class->hasAssociation($column)) {
                $changes[$column] = array();
                foreach ($change as $key => $assocChange) {
                    $entityValues = $this->getEntityValues($assocChange);
                    if (!$entityValues) {
                        continue;
                    }
                    $changes[$column][$key] = $entityValues;
                }
            }
            else {
                $changes[$column] = $change;
            }
        }

        return $changes;
    }

    private function getEntityValues($entity)
    {
        if ($entity instanceof EntityProxy) {
            $entity->__load();
        }
        if (!$entity) {
            return null;
        }

        $class = $this->em->getClassMetadata(get_class($entity));
        $entityValues = array();
        foreach ($class->reflFields as $name => $refProp) {
            $value = $refProp->getValue($entity);
            if (!$class->isCollectionValuedAssociation($name) && !$class->hasAssociation($name)) {
                $entityValues[$name] = $value;
            }
            if ($class->hasAssociation($name)) {
                $associations = $this->logService->includedAssociations($class->table['name']);
                if (in_array($name, $associations)) {
                    $entityValues[$name] = $this->getEntityValues($value);
                }
            }
        }
        $includedValues = $this->logService->getIncludedValues($entity);
        $entityValues = array_merge($entityValues, $includedValues);

        return $entityValues;
    }

    private function saveActivityHistory($data, $entity, $mysqlAction)
    {
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $customEm = $this->container->get('doctrine')->getManager('custom');
        $customEm->clear();
        $table = $metadata->table['name'];
        $user = $this->tokenStorage->getToken()->getUser();
        if ($user instanceof User) {
            $userActivityHistory = new UserActivityHistory;
            $userActivityHistory->setActivityData($data);
            $userActivityHistory->setAffectedTable($table);
            $userProxy = $customEm->getReference('YilinkerCoreBundle:User', $user->getUserId());
            $userActivityHistory->setUser($userProxy);
            $userActivityHistory->setMysqlAction($mysqlAction);

            $customEm->persist($userActivityHistory);
            $customEm->flush();

            return true;
        }

        return false;
    }

    private function recordHistory(LifecycleEventArgs $args, $mysqlAction)
    {
        $this->logService = $this->container->get('yilinker_core.service.log.user.activity');
        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;
        $this->em = $args->getEntityManager();
        $entity = $args->getEntity();

        if (!($user instanceof User)) {
            $user = $this->logService->getAwayUser($entity);
            if (!($user instanceof User)) {
                return false;
            }
        }
        if (!$this->logService->isLoggableEntity($entity, $mysqlAction)) {
            return false;
        }

        $metadata = $this->em->getClassMetadata(get_class($entity));
        $table = $metadata->table['name'];
        $associations = $this->logService->includedAssociations($table, false);

        $entityService = $this->container->get('yilinker_core.service.entity');
        $entityValues = $entityService->toArray($entity, $associations);
        $includedValues = $this->logService->getIncludedValues($entity);
        $entityValues = array_merge($entityValues, $includedValues);
        if ($mysqlAction == 'UPDATE') {
            $changes = $this->getEntityChange($entity);
            if ($changes) {
                $entityValues['__changes'] = $changes;
            }
        }
        $recorded = $this->saveActivityHistory($entityValues, $entity, $mysqlAction);

        return $recorded;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        return $this->recordHistory($args, 'INSERT');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        return $this->recordHistory($args, 'UPDATE');
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        return $this->recordHistory($args, 'DELETE');
    }
}
