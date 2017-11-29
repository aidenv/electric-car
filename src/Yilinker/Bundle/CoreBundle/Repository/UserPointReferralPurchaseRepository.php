<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\UserPointReferralPurchase;

class UserPointReferralPurchaseRepository extends EntityRepository
{
    public function entityExists(UserPointReferralPurchase $userPointReferralPurchase)
    {
        $orderProduct = $userPointReferralPurchase->getSource();

        $this
            ->qb()
            ->innerJoin('this.source', 'orderProduct')
            ->andWhere('orderProduct.order = :order')
            ->setParameter('order', $orderProduct->getOrder())
            ->andWhere('this.type = :type')
            ->setParameter('type', $userPointReferralPurchase->getType())
            ->andWhere('this.user = :user')
            ->setParameter('user', $userPointReferralPurchase->getUser())
        ;

        return $this->getCount();
    }
}