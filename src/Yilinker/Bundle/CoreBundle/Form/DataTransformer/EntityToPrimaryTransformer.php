<?php

namespace Yilinker\Bundle\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class EntityToPrimaryTransformer implements DataTransformerInterface
{
    private $em;
    private $entityName;
    private $throwError;
    private $singleID;

    public function __construct($em, $entityName, $throwError = false, $singleID = true)
    {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->throwError = $throwError;
        $this->singleID = $singleID;
    }

    public function transform($entity)
    {
        $id = null;
        if (is_null($entity) || $entity === false) {
            return $id;
        }

        $entityMapping = $this->em->getClassMetadata(get_class($entity));
        if ($entityMapping) {
            $id = $entityMapping->getIdentifierValues($entity);
            if (is_array($id) && $this->singleID) {
                $id = array_shift($id);
            }
        }

        return $id;
    }

    public function reverseTransform($primary)
    {
        if (!$primary) {
            return null;
        }

        $repository = $this->em->getRepository($this->entityName);
        $entity = $repository->find($primary);

        if (!$entity && $this->throwError) {
            $className = $repository->getClassName();
            throw new TransformationFailedException("$className with id $primary does not exist.");
        }

        return $entity;
    }
}