<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Cart;

/**
 * Class CartRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class CartRepository extends EntityRepository
{
    public function fromUser($userId)
    {
        $this->andWhere('this.user = :userId')
             ->setParameter('userId', $userId);

        return $this;
    }

    public function isActive()
    {
        $this->andWhere('this.status = :status')
             ->setParameter('status', Cart::ACTIVE);

        return $this;
    }

    public function isWishlist()
    {
        $this->andWhere('this.status = :status')
             ->setParameter('status', Cart::WISHLIST);

        return $this;
    }

    public function isLatest()
    {
        $this->orderBy('this.id', 'DESC')
             ->setMaxResults(1);

        return $this;
    }

    public function getActiveCartOfUser($userId)
    {
        $carts = $this->qb()
                      ->fromUser($userId)
                      ->isActive()
                      ->isLatest()
                      ->getQB()
                      ->getQuery()
                      ->getResult();
        
        return array_shift($carts);
    }

    public function getWishlist($userId)
    {
        $wishlist = $this->qb()
                         ->fromUser($userId)
                         ->isWishlist()
                         ->isLatest()
                         ->getQB()
                         ->getQuery()
                         ->getResult();

        return array_shift($wishlist);
    }
}