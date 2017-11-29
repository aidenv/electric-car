<?php

namespace Yilinker\Bundle\CoreBundle\Repository\BaseRepository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged;

class BaseUserOrderRepository extends EntityRepository
{
    protected $ongoingStatuses = array(
        UserOrder::ORDER_STATUS_PAYMENT_CONFIRMED,
        UserOrder::ORDER_STATUS_DELIVERED,
        UserOrder::ORDER_STATUS_FOR_PICKUP,
        UserOrder::ORDER_STATUS_COD_WAITING_FOR_PAYMENT
    );

    public function getOngoingStatuses() {
        return $this->ongoingStatuses;
    }

    public function getValidDisplayStatuses() {
        $validStatuses = $this->getOngoingStatuses();
        $validStatuses[] = UserOrder::ORDER_STATUS_COMPLETED;
        $validStatuses[] = UserOrder::ORDER_STATUS_FOR_REPLACEMENT;
        $validStatuses[] = UserOrder::ORDER_STATUS_FOR_CANCELLATION;
        $validStatuses[] = UserOrder::ORDER_STATUS_FOR_REFUND;

        return $validStatuses;
    }

    public function searchBy($criteria, $createQB = true) {
        if (isset($criteria['tab'])) {
            if ($criteria['tab'] == 'ongoing') {
                $criteria['orderStatus'] = $this->ongoingStatuses;
            }
            elseif ($criteria['tab'] == 'completed') {
                $criteria['orderStatus'] = array(UserOrder::ORDER_STATUS_COMPLETED);
            }
            else {
                $criteria['orderStatus'] = $this->getValidDisplayStatuses();
            }
        }
        parent::searchBy($criteria, $createQB);
        $criteria = $this->temp['alienCriteria'];

        return $this;
    }

    public function withSeller($sellers)
    {
        if (!is_array($sellers)) {
            $sellers = array($sellers);
        }

        $this
            ->innerJoin('this.orderProducts', 'orderProducts')
            ->andWhere("orderProducts.seller IN (:sellers)")
            ->setParameter('sellers', $sellers)
        ;

        return $this;
    }

    public function notFlagged()
    {
        $this
            ->leftJoin('this.userOrderFlagged', 'userOrderFlagged')
            ->andWhere('userOrderFlagged IS NULL OR userOrderFlagged.status = '.UserOrderFlagged::APPROVE)
        ;

        return $this;
    }
}