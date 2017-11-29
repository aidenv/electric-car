<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityRepository;

/**
 * Class ManufacturerPayout
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ManufacturerPayoutRepository extends EntityRepository
{

    /**
     * Get Manufacturer Payout
     *
     * @param null $keyword
     * @param null $dateFrom
     * @param null $dateTo
     * @param null $storeType
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getManufacturerPayouts ($keyword = null, $dateFrom = null, $dateTo = null, $storeType = null, $limit = 30, $offset = 0)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:ManufacturerPayout", "p")
                     ->leftJoin("YilinkerCoreBundle:ManufacturerPayoutOrderProduct", "op", Join::WITH, "op.manufacturerPayout = p.manufacturerPayoutId")
                     ->innerJoin("YilinkerCoreBundle:User", "u", Join::WITH, "p.user = u")
                     ->innerJoin("YilinkerCoreBundle:Manufacturer", "m", Join::WITH, "op.manufacturer = m.manufacturerId")
                     ->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u.userId");

        if (!is_null($keyword)) {
            $queryBuilder->andWhere('(m.name LIKE :searchKeyword OR p.referenceNumber LIKE :searchKeyword)')
                         ->setParameter('searchKeyword', '%' . $keyword . '%');
        }

        if (!is_null($dateFrom)) {
            $gte = $queryBuilder->expr()->gte("p.dateCreated", ":dateFrom");
            $queryBuilder->andWhere($gte)->setParameter(":dateFrom", $dateFrom);
        }


        if (!is_null($dateTo)) {
            $lte = $queryBuilder->expr()->lte("p.dateCreated", ":dateTo");
            $queryBuilder->andWhere($lte)->setParameter(":dateTo", $dateTo);
        }

        if (!is_null($storeType)) {
            $storeTypeCond = $queryBuilder->expr()->eq("s.storeType", ":storeType");
            $queryBuilder->andWhere($storeTypeCond)->setParameter(":storeType", $storeType);
        }

        $manufacturerCount = count($queryBuilder->getQuery()->getResult());
        $manufacturers = $queryBuilder->setMaxResults($limit)
                                      ->setFirstResult($offset)
                                      ->getQuery()
                                      ->getResult();

        return compact("manufacturers", "manufacturerCount");
    }
}