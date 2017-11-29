<?php

namespace Yilinker\Bundle\CoreBundle\Services\Entity;

use Doctrine\Common\Persistence\Proxy as EntityProxy;
use Doctrine\ORM\PersistentCollection;

class EntityService
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function getValues($collection, $keys)
    {
        $data = array();
        foreach ($collection as $item) {
            $data[] = $this->getValue($item, $keys);
        }

        return $data;
    }

    public function getValue($entity, $keys)
    {
        if ($entity instanceof EntityProxy) {
            $entity->__load();
        }

        $metadata = $this->em->getClassMetadata(get_class($entity));
        $keys = is_array($keys) ? $keys: explode('.', $keys);
        $key = array_shift($keys);
        if (array_key_exists($key, $metadata->reflFields)) {
            $value = $metadata->reflFields[$key]->getValue($entity);
            if (!$keys) {
                return $value;
            }

            if ($metadata->hasAssociation($key)) {
                if ($value instanceof PersistentCollection) {
                    return $this->getValues($value, $keys);
                }

                return $this->getValue($value, $keys);
            }
        }

        return null;
    }

    public function compare($entity, $compare)
    {
        foreach ($compare as $key => $compareValue) {
            $value = $this->getValue($entity, $key);
            if ($value != $compareValue) {
                return false;
            }
        }

        return true;
    }

    public function compareOr($entity, $compare)
    {
        foreach ($compare as $key => $compareValue) {
            $value = $this->getValue($entity, $key);
            if ($value == $compareValue) {
                return true;
            }
        }

        return false;
    }

    public function toArray($entity, $associations = array(), $includes = array())
    {
        if ($entity instanceof EntityProxy) {
            $entity->__load();
        }
        if (!$entity) {
            return array();
        }

        $metadata = $this->em->getClassMetadata(get_class($entity));
        $entityValues = array();
        foreach ($metadata->reflFields as $name => $refProp) {
            $value = $refProp->getValue($entity);
            if (!$metadata->isCollectionValuedAssociation($name) && !$metadata->hasAssociation($name)) {
                $entityValues[$name] = $value;
            }
            if ($metadata->hasAssociation($name) && $associations) {
                $keyExists = array_key_exists($name, $associations);
                $valueAssociations = $keyExists ? $associations[$name]: array();
                if (in_array($name, $associations) || $keyExists) {
                    if ($metadata->isAssociationWithSingleJoinColumn($name) 
                        || $metadata->isSingleValuedAssociation($name)) {
                        $entityValues[$name] = $this->toArray($value, $valueAssociations);                       
                    }
                    else {
                        foreach ($value as $part) {
                            $entityValues[$name][] = $this->toArray($part, $valueAssociations);
                        }
                    }
                }
            }
        }
        foreach ($includes as $include) {
            if (method_exists($entity, $include)) {
                $entityValues[$include] = $entity->{$include}();
            }
        }

        return $entityValues;
    }

    public function getChanges($entity, $columns = array(), $recompute = false)
    {
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $uow = $this->em->getUnitOfWork();

        if ($recompute) {
            $uow = clone $this->em->getUnitOfWork();
            $uow->recomputeSingleEntityChangeSet($metadata, $entity);
        }

        $uowEntityChanges = $uow->getEntityChangeSet($entity);
        $changes = array();
        foreach ($uowEntityChanges as $column => $change) {
            $keyExists = array_key_exists($column, $columns);
            $valueAssociations = $keyExists ? $columns[$column]: array();

            if (!$columns || in_array($column, $columns) || $keyExists) {
                if ($metadata->hasAssociation($column)) {
                    $changes[$column] = array();
                    foreach ($change as $key => $assocChange) {
                        $entityValues = $this->toArray($assocChange, $valueAssociations);
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
        }

        return $changes;
    }
}