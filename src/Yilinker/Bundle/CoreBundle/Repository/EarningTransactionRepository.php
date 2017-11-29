<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class EarningTransactionRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class EarningTransactionRepository extends EntityRepository
{
    public function joinEarning()
    {
        $this->leftJoin('this.earning', 'earning');

        return $this;
    }

    public function whereQuery(array $filters)
    {
        $this->joinEarning();

        if (isset($filters['orderProduct']) && $filters['orderProduct']) {
            $this->getQB()
                 ->andWhere('this.orderProduct = :orderProduct')
                 ->setParameter('orderProduct', $filters['orderProduct']);
        }

        if (isset($filters['user']) && $filters['user']) {
            $this->getQB()
                 ->andWhere('earning.user = :user')
                 ->setParameter('user', $filters['user']);
        }

        if (isset($filters['earningType']) && $filters['earningType']) {
            $this->getQB()
                 ->andWhere('earning.earningType = :earningType')
                 ->setParameter('earningType', $filters['earningType']);
        }

        return $this;
    }
}