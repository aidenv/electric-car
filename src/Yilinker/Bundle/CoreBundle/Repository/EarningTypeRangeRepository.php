<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;

/**
 * Class ProductRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class EarningTypeRangeRepository extends EntityRepository
{
    public function getEarningByRange(EarningType $earningType, $count)
    {
        return $this->qb()
                    ->andWhere(':count BETWEEN this.from AND this.to')
                    ->andWhere('this.earningType = :earningType')
                    ->setParameter('count', $count)
                    ->setParameter('earningType', $earningType)
                    ->getOneOrNullResult();
    }

    public function getEarningWithToIsNull(EarningType $earningType, $count)
    {
        return $this->qb()
                    ->andWhere(':count >= this.from')
                    ->andWhere('this.to IS NULL')
                    ->andWhere('this.earningType = :earningType')
                    ->setParameter('count', $count)
                    ->setParameter('earningType', $earningType)
                    ->getOneOrNullResult();
    }
}
