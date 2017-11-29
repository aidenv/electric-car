<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class OrderStatusRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class OrderStatusRepository extends EntityRepository
{
    public function getOrderStatusIn(array $status)
    {
        $queryBuilder = $this->createQueryBuilder("os");

        $statusExpr = $queryBuilder->expr()->in("os.orderStatusId", ":status");
        $queryBuilder->where($statusExpr)
                     ->setParameter(":status", $status);

        return $queryBuilder->getQuery()->getResult();
    }
}
