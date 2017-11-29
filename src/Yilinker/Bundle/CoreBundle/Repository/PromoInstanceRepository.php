<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Class PromoRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class PromoInstanceRepository extends EntityRepository
{
    const ORDER_BY_DATE_START = "pi.dateStart";

    public function loadPromoInstances($keyword = "", $dateFrom = "2015-01-01", $dateTo = "2016-01-01", $limit = null, $offset = null, $getTotalCount = false, $promoType = null, $orderBy = 'DESC')
    {
        $instances = array();

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pi")
                     ->from("YilinkerCoreBundle:PromoInstance", "pi")
                     ->where($queryBuilder->expr()->like("pi.title", ":keyword"))
                     ->orderBy("pi.dateCreated", $orderBy)
                     ->setParameter(":keyword", "%".$keyword."%");

        if(!is_null($dateFrom)){
            $andx1 = $queryBuilder->expr()->andx();
            $andx1->add($queryBuilder->expr()->gte("pi.dateStart", ":dateStart"));

            $queryBuilder->andWhere($andx1)
                         ->setParameter(":dateStart", $dateFrom);
        }

        if(!is_null($dateTo)){
            $andx2 = $queryBuilder->expr()->andx();
            $andx2->add($queryBuilder->expr()->lte("pi.dateEnd", ":dateEnd"));

            $queryBuilder->andWhere($andx2)
                         ->setParameter(":dateEnd", $dateTo);
        }

        if ($promoType !== null) {
            $queryBuilder->andWhere("pi.promoType = :promoType")
                         ->setParameter(":promoType", $promoType);

        }


        if(!is_null($limit) && !is_null($offset)){
            $queryBuilder->setFirstResult($offset)
                         ->setMaxResults($limit);
        }

        if(!$getTotalCount){
            return $queryBuilder->getQuery()->getResult();
        }

        $query = $queryBuilder->getQuery();
        
        $paginator = new Paginator($query);
        $instances = array(
            "instances"     => $query->getResult(),
            "totalCount"    => $paginator->count()
        );

        return $instances;
    }

    public function getPromoInstanceIn(array $promoInstanceIds)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pi")
                     ->from("YilinkerCoreBundle:PromoInstance", "pi")
                     ->where("pi.promoInstanceId IN (:promoInstanceId)")
                     ->setParameter(":promoInstanceId", $promoInstanceIds);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getPromoInstanceByType(
        $promoType = PromoType::FIXED, 
        $isEnabled = true, 
        $dateStart = null, 
        $dateEnd = null,
        $orderBy = null,
        $sortType = null,
        $isOneResult = false
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("pi")
                     ->from("YilinkerCoreBundle:PromoInstance", "pi")
                     ->where("pi.promoType = :promoType")
                     ->andWhere("pi.isEnabled = :isEnabled")
                     ->setParameter(":promoType", $promoType)
                     ->setParameter(":isEnabled", $isEnabled);

        if(!is_null($dateStart)){
            $gteDateStart = $queryBuilder->expr()->gte("pi.dateStart", ":dateStart");
            $queryBuilder->andWhere($gteDateStart)
                         ->setParameter(":dateStart", $dateStart);
        }

        if(!is_null($dateEnd)){
            $lteDateEnd = $queryBuilder->expr()->lte("pi.dateEnd", ":dateEnd");
            $queryBuilder->andWhere($lteDateEnd)
                         ->setParameter(":dateEnd", $dateEnd);
        }

        if(!is_null($orderBy) && !is_null($sortType)){
            $queryBuilder->orderBy($orderBy, $sortType);
        }

        if($isOneResult){
            return $queryBuilder->getQuery()->setMaxResults(1)->getOneOrNullResult();
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getCurrentPromoInstance(
        $promoType = PromoType::FIXED, 
        $isEnabled = true, 
        $dateStart = null, 
        $dateEnd = null,
        $orderBy = null,
        $sortType = null,
        $isOneResult = false
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("pi")
                     ->from("YilinkerCoreBundle:PromoInstance", "pi")
                     ->where("pi.promoType = :promoType")
                     ->andWhere("pi.isEnabled = :isEnabled")
                     ->setParameter(":promoType", $promoType)
                     ->setParameter(":isEnabled", $isEnabled);

        if(!is_null($dateStart)){
            $lteDateStart = $queryBuilder->expr()->lte("pi.dateStart", ":dateStart");
            $queryBuilder->andWhere($lteDateStart)
                         ->setParameter(":dateStart", $dateStart);
        }

        if(!is_null($dateEnd)){
            $gteDateEnd = $queryBuilder->expr()->gte("pi.dateEnd", ":dateEnd");
            $queryBuilder->andWhere($gteDateEnd)
                         ->setParameter(":dateEnd", $dateEnd);
        }

        if(!is_null($orderBy) && !is_null($sortType)){
            $queryBuilder->orderBy($orderBy, $sortType);
        }

        if($isOneResult){
            return $queryBuilder->getQuery()->setMaxResults(1)->getOneOrNullResult();
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getCurrentProductPromoInstances($product, $isEnabled = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("pi")
                     ->from("YilinkerCoreBundle:PromoInstance", "pi")
                     ->innerJoin("YilinkerCoreBundle:ProductPromoMap", "ppm", Join::WITH, "ppm.promoInstance = pi")
                     ->innerJoin("YilinkerCoreBundle:ProductUnit", "pu", Join::WITH, "ppm.productUnit = pu")
                     ->innerJoin("YilinkerCoreBundle:Product", "p", Join::WITH, "pu.product = p")
                     ->where("pu.product IN (:product)")
                     ->andWhere("pi.dateEnd > :dateNow")
                     ->groupBy("pi")
                     ->setParameter(":product", $product)
                     ->setParameter(":dateNow", Carbon::now());

        if(!is_null($isEnabled)){
            $queryBuilder->andWhere("pi.isEnabled = :isEnabled")->setParameter(":isEnabled", $isEnabled);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getNextPromoInstance($dateNow)
    {
        $sql = "
            SELECT
                pi.promo_instance_id,
                pi.date_start,
                pi.date_end
            FROM
                PromoInstance pi
            WHERE
                pi.is_enabled = 1
            AND
                pi.date_start > :dateNow
            ORDER BY 
                pi.date_start ASC
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute(array(
            ":dateNow" => $dateNow
        ));
        
        return $stmt->fetch();
    }

    public function loadPromoInstancesIn($promoInstanceIds = array())
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        return $queryBuilder->select("pi")
                            ->from("YilinkerCoreBundle:PromoInstance", "pi")
                            ->where("pi.promoInstanceId IN (:promoInstanceIds)")
                            ->setParameter(":promoInstanceIds", $promoInstanceIds)
                            ->getQuery()
                            ->getResult();
    }
}
