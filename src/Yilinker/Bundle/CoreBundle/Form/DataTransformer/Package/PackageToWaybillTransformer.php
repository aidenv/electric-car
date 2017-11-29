<?php

namespace Yilinker\Bundle\CoreBundle\Form\DataTransformer\Package;

use Symfony\Component\Form\DataTransformerInterface;

class PackageToWaybillTransformer implements DataTransformerInterface
{

    private $em;

    private $entityName;

    private $throwError;

    public function __construct($em, $entityName, $throwError = false)
    {
        $this->em = $em;
        $this->entityName = $entityName;
        $this->throwError = $throwError;
    }

    public function transform($entity)
    {
        $waybillNumber = null;
        if (is_null($entity)) {
            return $waybillNumber;
        }

        if ($entityMapping) {
            $waybillNumber = $entity->getWaybillNumber();
        }

        return $waybillNumber;
    }

    public function reverseTransform($waybillNumber)
    {
        if (!$waybillNumber) {
            return null;
        }

        $repository = $this->em->getRepository($this->entityName);
        $entity = $repository->findOneBy(array('waybillNumber' => $waybillNumber));
        if (!$entity && $this->throwError) {
            $className = $repository->getClassName();
            throw new TransformationFailedException("$className with waybill $waybillNumber does not exist.");
        }

        return $entity;
    }

}
