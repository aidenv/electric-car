<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
* Class DeviceNotificationRepository
* @package Yilinker\Bundle\CoreBundle\Repository
*/
class DeviceNotificationRepository extends EntityRepository
{
    const SORT_DIRECTION_ASC = "ASC";

    const SORT_DIRECTION_DESC = "DESC";

    public function getNotifications(
        $recipient = null,
        $isSent = null,
        $dateFrom = null,
        $dateTo = null,
        $limit = null,
        $offset = null,
        $orderBy = null,
        $sortDirection = self::SORT_DIRECTION_DESC,
        $hasResultCount = false,
        $hasTotalPages = false,
        $isActive = null,
        $keyword = null,
        $targetType = null
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("dn")
                    ->from("YilinkerCoreBundle:DeviceNotification", "dn");

        if(!is_null($keyword)){
            $queryBuilder->andWhere("dn.title LIKE :keyword OR dn.message LIKE :keyword")
                         ->setParameter(":keyword", "%".$keyword."%");
        }

        if(!is_null($recipient)){

            if(is_array($recipient)){
                $queryBuilder->andWhere("dn.recipient IN (:recipient)");
            }
            else{
                $queryBuilder->andWhere("dn.recipient = :recipient");
            }

            $queryBuilder->setParameter(":recipient", $recipient);
        }

        if(!is_null($isActive)){
            $queryBuilder->andWhere("dn.isActive = :isActive")->setParameter(":isActive", $isActive);
        }

        if(!is_null($isSent)){
            $queryBuilder->andWhere("dn.isSent = :isSent")->setParameter(":isSent", $isSent);
        }

        if($dateFrom && $dateTo){
            $queryBuilder->andWhere("dn.dateScheduled BETWEEN :dateFrom AND :dateTo")
                         ->setParameter(":dateFrom", $dateFrom)
                         ->setParameter(":dateTo", $dateTo);
        }

        if(!is_null($targetType)){
            $queryBuilder->andWhere("dn.targetType = :targetType")->setParameter(":targetType", $targetType);
        }

        if(!is_null($orderBy) && !is_null($sortDirection)){
            $queryBuilder->orderBy($orderBy, $sortDirection);
        }

        if($hasTotalPages || $hasResultCount){
            $paginator = new Paginator($queryBuilder->getQuery());
            $result = array();
        }

        if($hasResultCount){
            $totalResultCount = $paginator->count();
            $result["totalResultCount"] = $totalResultCount;
        }

        if($hasTotalPages && $hasTotalPages){
            $totalPages = ceil($totalResultCount/$limit);
            $result["totalResultCount"] = $totalPages;
        }

        if(!is_null($limit)){
            $queryBuilder->setMaxResults($limit);
        }

        if(!is_null($offset)){
            $queryBuilder->setFirstResult($offset);
        }

        if($hasTotalPages || $hasResultCount){
            $result["results"] = $queryBuilder->getQuery()->getResult();
            return $result;
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
