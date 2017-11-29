<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class UserFollowHistoryRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserFollowHistoryRepository extends EntityRepository
{
    /**
     * Load user follow history
     *
     * @param User $user
     * @param $offset
     * @param $limit
     * @return array
     * @internal param $userIds
     */
    public function loadUserFollowHistory(User $user, $limit, $offset)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("ufh")
                     ->from("YilinkerCoreBundle:UserFollowHistory", "ufh")
                     ->where("ufh.follower = :user")
                     ->orderBy("ufh.dateCreated", "DESC")
                     ->setParameter(":user", $user)
                     ->setFirstResult($offset)
                     ->setMaxResults($limit);

        $userFollowHistory = $queryBuilder->getQuery()->getResult();

        return $userFollowHistory;
    }
}
