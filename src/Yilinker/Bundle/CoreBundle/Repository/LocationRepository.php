<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class LocationRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class LocationRepository extends EntityRepository
{
    public function getLocationTypeQB($locationType)
    {
        $this->qb()
             ->innerJoin('this.locationType', 'locationType')
             ->andWhere('locationType.locationType = :locationType')
             ->setParameter('locationType', $locationType)
        ;

        return $this->getQB();
    }

    public function loadBarangaysByCity($locationId, $isActive = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("l2")
                     ->from("YilinkerCoreBundle:Location", "l1")
                     ->innerJoin("YilinkerCoreBundle:Location", "l2", Join::WITH, "l2.parent = l1.locationId")
                     ->where("l1.locationId = :locationId")
                     ->setParameter(":locationId", $locationId);

        if(!is_null($isActive)){
            $queryBuilder->andWhere("l2.isActive = :isActive")->setParameter(":isActive", $isActive);
        }

        $barangays = $queryBuilder->getQuery()->execute();

        return $barangays;
    }

    /**
     * Get locations by type
     *
     * @param int $locationType
     * @return Yilinker\Bundle\CoreBundle\Entity\Location[]
     */
    public function getLocationsByType($locationType, $isActive = null)
    {
        $queryBuilder = $this->createLocationsByTypeQB($locationType, $isActive);

        return $queryBuilder->getQuery()->getResult();
    }

    public function createLocationsByTypeQB($locationType, $isActive = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        
        $queryBuilder->select("l")
                     ->from("YilinkerCoreBundle:Location", "l")
                     ->setParameter('locationType', $locationType)
        ;

        if(!is_null($isActive)){
            $queryBuilder->andWhere('l.isActive = :isActive')->setParameter(":isActive", $isActive);
        }

        if (is_array($locationType)) {
            $queryBuilder->andWhere('l.locationType IN (:locationType)');
        }
        else {
            $queryBuilder->andWhere('l.locationType = :locationType');
        }

        return $queryBuilder;
    }

    public function filterBy($array)
    {
        if (isset($array['isActive'])) {
            $this->getQB()->andWhere('this.isActive = :isActive')
                          ->setParameter('isActive', (bool) $array['isActive']);
        }

        if (isset($array['locationType'])) {
            $this->getQB()->andWhere('this.locationType IN (:locationType)')
                          ->setParameter('locationType', $array['locationType']);
        }

        return $this;
    }

    public function filterByMultipleParent($parents)
    {
        $this->getQB()->andWhere('this.parent IN (:parents)')
                      ->setParameter('parents', $parents);

        return $this;
    }
}
