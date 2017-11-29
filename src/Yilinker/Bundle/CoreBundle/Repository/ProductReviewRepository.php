<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class ProductRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ProductReviewRepository extends EntityRepository
{
    /**
     * get product reviews
     *
     * @param $product
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProductReviews($product, $page = 1, $limit = 10)
    {
        $offset = $page > 1? $limit*($page-1) : 0;

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("pr")
                     ->from("YilinkerCoreBundle:ProductReview", "pr")
                     ->where("pr.product = :product")
                     ->andWhere("pr.isHidden = false")
                     ->setParameter("product", $product);

        if(!is_null($page) && !$limit){
            $queryBuilder->setMaxResults($limit)->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getTotalReviewReceived(User $user)
    {
        return $this->qb()
                    ->leftJoin('this.product', 'product')
                    ->andWhere('product.user = :user')
                    ->setParameter('user', $user)
                    ->getCount();
    }
}
