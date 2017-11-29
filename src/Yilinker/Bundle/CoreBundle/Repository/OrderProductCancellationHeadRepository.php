<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;

/**
 * Class OrderProductCancellationHeadRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class OrderProductCancellationHeadRepository extends EntityRepository
{

    /**
     * Get Active OrderProductCancellationHead By order product
     *
     * @param OrderProduct $orderProduct
     * @return array
     */
    public function getOrderProductCancellationHeadByOrderProduct (OrderProduct $orderProduct)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('ocpHead')
                     ->from('YilinkerCoreBundle:OrderProductCancellationHead', 'ocpHead')
                     ->leftJoin('YilinkerCoreBundle:OrderProductCancellationDetail', 'ocpDetail', 'WITH', 'ocpHead.orderProductCancellationHeadId = ocpDetail.orderProductCancellationHead')
                     ->where('ocpHead.isOpened = 1')
                     ->andWhere('ocpDetail.orderProduct = :orderProductId')
                     ->groupBy('ocpHead.orderProductCancellationHeadId')
                     ->setParameter('orderProductId', $orderProduct->getOrderProductId());

        $qbResult = $queryBuilder->getQuery();

        return $qbResult->getSingleResult();
    }

}
