<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class UserFollowRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserFollowRepository extends EntityRepository
{
    const PAGE_LIMIT = 30;

    const PAGE_NUMBER = 0;

    /**
     * Get the number of follower sellers of a user
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return int
     */
    public function getNumberOfFollowedSellers($user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("count(u)")
                     ->from("YilinkerCoreBundle:UserFollow", "uf")
                     ->innerJoin("YilinkerCoreBundle:User", "u", Join::WITH, "u = uf.followee")
                     ->where("uf.follower = :user")
                     ->andWhere("u.userType = :sellerType")
                     ->setParameter("user", $user)
                     ->setParameter("sellerType", User::USER_TYPE_SELLER);
                     
        $count = $queryBuilder->getQuery()
                              ->getSingleScalarResult();

        return (int) $count;
    }

    /**
     * Load sellers follower by a user
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param $offset
     * @param $limit
     * @return array
     * @internal param $userIds
     */
    public function loadFollowedSellers(User $user, $keyword, $limit = self::PAGE_LIMIT, $offset = self::PAGE_NUMBER)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("u")
                     ->from("YilinkerCoreBundle:UserFollow", "uf")
                     ->innerJoin("YilinkerCoreBundle:User", "u", Join::WITH, "u = uf.followee")
                     ->where("uf.follower = :user")
                     ->andWhere("u.userType = :sellerType")
                     ->andWhere("u.firstName LIKE :keyword OR u.lastName LIKE :keyword OR u.email LIKE :keyword")
                     ->orderBy("u.firstName")
                     ->setParameter(":user", $user)
                     ->setParameter(":keyword", "%".$keyword."%")
                     ->setParameter(":sellerType", User::USER_TYPE_SELLER)
                     ->setFirstResult($offset)
                     ->setMaxResults($limit);

        $followedSellers = $queryBuilder->getQuery()->getResult();

        return $followedSellers;
    }

    /**
     * Get Seller followers
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param int $page
     * @param int $limit
     * @param null $searchKeyword
     * @return array
     */
    public function getFollowers (User $user, $page = self::PAGE_LIMIT, $limit = self::PAGE_NUMBER, $searchKeyword = null, $userType = User::USER_TYPE_BUYER)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("u")
                     ->from("YilinkerCoreBundle:UserFollow", "uf")
                     ->innerJoin("YilinkerCoreBundle:User", "u", 'WITH', "u = uf.follower")
                     ->where("uf.followee = :user")
                     ->andWhere("u.userType = :userType");

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("(CONCAT(u.firstName, ' ', u.lastName) LIKE :searchKeyword OR u.email LIKE :searchKeyword)")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        $queryBuilder->orderBy("u.firstName")
                     ->setParameter(":user", $user)
                     ->setParameter(":userType", $userType)
                     ->setFirstResult($page)
                     ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
