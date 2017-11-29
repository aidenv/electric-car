<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class UserPointRepository.php
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserPointRepository extends EntityRepository
{

    /**
     * Filter By with args
     *
     * @param array $args
     * @return $this
     */
    public function filterBy(array $args = array())
    {
        $thisQb = $this->qb();

        if (isset($args['user']) && $args['user']) {
            $thisQb->andWhere('this.user = :user')
                   ->setParameter('user', $args['user']);
        }

        if (isset($args['dateFrom']) && $args['dateFrom']) {
            $thisQb->andWhere('this.dateAdded >= :dateFrom')
                   ->setParameter('dateFrom', $args['dateFrom']);
        }

        if (isset($args['dateTo']) && $args['dateTo']) {
            $thisQb->andWhere('this.dateAdded < :dateTo')
                   ->setParameter('dateTo', $args['dateTo']);
        }

        if (isset($args['type']) && $args['type']) {
            $thisQb->andWhere('this.type = :type')
                   ->setParameter('type', $args['type']);
        }

        return $this;
    }

    /**
     * [sumUserPoint totalPoints]
     * @param  $userId 
     * @return [int]
     */
    public function sumUserPoint($userId)
    {
        $qb = $this->qb();

        $qb->where('this.user = :userId')
            ->setParameter('userId', $userId);
        
        return $qb->getSum('this.points');

    }

}
