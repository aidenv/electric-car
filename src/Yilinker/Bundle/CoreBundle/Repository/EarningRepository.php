<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Doctrine\ORM\Query\Expr\Join;

class EarningRepository extends EntityRepository
{
    const FILTER_TRANSACTION_NUMBER = 1;
    const FILTER_PRODUCT_NAME = 2;
    const FILTER_BUYER_NAME = 3;
    const FILTER_AFFILIATE_NAME = 4;

    public static function getFilterCriterias($key = null)
    {
        $filterCriterias = array(
            self::FILTER_TRANSACTION_NUMBER => 'Transaction Number',
            self::FILTER_PRODUCT_NAME       => 'Product Name',
            self::FILTER_BUYER_NAME         => 'Buyer Name',
            self::FILTER_AFFILIATE_NAME     => 'Affiliate Name'
        );

        return $filterCriterias;
    }

    public function getStoreTotal($store, $filter = null)
    {
        $this
            ->ofStoreQB($store, $filter)
            ->select('SUM(this.amount)')
        ;
        $total = $this->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return $total ? $total: 0;
    }

    public function ofStoreQB($store, $filter = null)
    {
        $user = $store->getUser();

        $this
            ->qb()
            ->andWhere('this.user = :user')
            ->setParameter('user', $user)
        ;

        if (is_array($filter)) {
            $validQuery = array_key_exists('q', $filter) && $filter['q'];
            $validQueryCriteria = array_key_exists('qCriteria', $filter) && $filter['qCriteria'];
            if ($validQuery && $validQueryCriteria) {
                $this->searchCriteriaQB($filter['q'], $filter['qCriteria']);
            }
            if (array_key_exists('type', $filter) && $filter['type']) {
                $this
                    ->andWhere('this.earningType IN (:types)')
                    ->setParameter('types', $filter['type'])
                ;
            }
            if (array_key_exists('status', $filter)) {
                if (is_array($filter['status']) === false) {
                    $filter['status'] = array($filter['status']);
                }

                if (!empty($filter['status'])) {
                    $this
                        ->andWhere('this.status IN (:statuses)')
                        ->setParameter('statuses', $filter['status'])
                    ;
                }
            }
            if (array_key_exists('daterange', $filter) && $filter['daterange']) {
                $this->daterangeQB($filter['daterange']);
            }
            $hasStartDate = array_key_exists('startdate', $filter) && $filter['startdate'];
            $hasEndDate = array_key_exists('enddate', $filter) && $filter['enddate'];
            if ($hasStartDate && $hasEndDate) {
                $this->betweenDatesQB($filter['startdate'], $filter['enddate']);
            }
            if (array_key_exists('order', $filter) && $filter['order']) {
                foreach ($filter['order'] as $column => $direction) {
                    $this->addOrderBy('this.'.$column, $direction);
                }
            }
        }

        return $this;
    }

    public function daterangeQB($daterange)
    {
        $dates = explode(' - ', $daterange);
        $startdate = array_shift($dates);
        $enddate = array_shift($dates);

        if ($startdate && $enddate) {
            $startdate = Carbon::createFromFormat('m/d/Y', $startdate)->startOfDay();
            $enddate = Carbon::createFromFormat('m/d/Y', $enddate)->endOfDay();

            $this->betweenDatesQB($startdate, $enddate);
        }

        return $this;
    }

    public function betweenDatesQB($startdate, $enddate)
    {
        $this
            ->andWhere('this.dateAdded >= :startdate')
            ->setParameter('startdate', $startdate)
            ->andWhere('this.dateAdded <= :enddate')
            ->setParameter('enddate', $enddate)
        ;

        return $this;
    }

    public function searchCriteriaQB($q, $criteria)
    {

        if ($criteria == self::FILTER_TRANSACTION_NUMBER) {
            $this
                ->innerJoin('this.earningTransaction', 'earningTransaction')
                ->innerJoin('earningTransaction.orderProduct', 'orderProduct')
                ->innerJoin('orderProduct.order', 'userOrder')
                ->andWhere('userOrder.invoiceNumber LIKE :q')
                ->setParameter('q', "%$q%")
                ->andWhere('this.earningType IN (:earningTypes)')
                ->setParameter('earningTypes', EarningType::SALE)
            ;
        }
        elseif ($criteria == self::FILTER_BUYER_NAME) {
            $this
                ->leftJoin('this.earningTransaction', 'earningTransaction')
                ->leftJoin('earningTransaction.orderProduct', 'orderProduct')
                ->leftJoin('orderProduct.order', 'userOrder')
                ->leftJoin('userOrder.buyer', 'user')

                ->leftJoin('this.earningReview', 'earningReview')
                ->leftJoin('earningReview.productReview', 'productReview')
                ->leftJoin('productReview.reviewer', 'reviewer')

                ->leftJoin('this.earningFollow', 'earningFollow')
                ->leftJoin('earningFollow.userFollowHistory', 'userFollowHistory')
                ->leftJoin('userFollowHistory.follower', 'follower')

                ->andWhere('
                    follower.firstName LIKE :q OR follower.lastName LIKE :q OR
                    reviewer.firstName LIKE :q OR reviewer.lastName LIKE :q OR
                    user.firstName LIKE :q OR user.lastName LIKE :q'
                )
                ->setParameter('q', "%$q%")
            ;
        }
        elseif ($criteria == self::FILTER_PRODUCT_NAME) {
            $this
                ->leftJoin('this.earningTransaction', 'earningTransaction')
                ->leftJoin('earningTransaction.orderProduct', 'orderProduct')

                ->leftJoin('this.earningReview', 'earningReview')
                ->leftJoin('earningReview.productReview', 'productReview')
                ->leftJoin('productReview.orderProduct', 'orderProductReviewed')

                ->andWhere('
                    orderProductReviewed.productName LIKE :q OR
                    orderProduct.productName LIKE :q'
                )
                ->setParameter('q', "%$q%")
            ;
        }
        elseif ($criteria == self::FILTER_AFFILIATE_NAME) {
            $this
                ->leftJoin('this.earningUserRegistration', 'earningUserRegistration')
                ->leftJoin('earningUserRegistration.user', 'user')

                ->leftJoin('this.earningTransaction', 'earningTransaction')
                ->leftJoin('earningTransaction.orderProduct', 'orderProduct')
                ->leftJoin('orderProduct.seller', 'seller')
                ->leftJoin('orderProduct.product', 'product')
                ->leftJoin('product.manufacturerProductMap', 'manufacturerProductMap')

                ->andWhere('
                    (manufacturerProductMap.manufacturerProductMapId IS NOT NULL AND
                    (seller.firstName LIKE :q OR seller.lastName LIKE :q)) OR
                    user.firstName LIKE :q OR user.lastName LIKE :q
                ')
                ->setParameter('q', "%$q%")
            ;
        }

        return $this->groupBy('this.earningId');
    }

    public function getOfStore($store, $filter = null, $page = 1, $count = 10)
    {
        $this
            ->ofStoreQB($store, $filter)
            ->setMaxResults($count)
            ->page($page)
        ;

        return $this->getResult();
    }

    public function getDailyEarning($store, $filter = null)
    {
        $this
            ->ofStoreQB($store, $filter)
            ->select('SUM(this.amount) AS amountEarned')
            ->addSelect("DATE_FORMAT(this.dateLastModified, '%m/%d/%Y') AS dayEarned")
            ->groupBy('dayEarned')
        ;

        return $this->getResult();
    }

    public function getForCompletionInvoiceNumbers($invoiceNumbers)
    {
        $this
            ->qb()
            ->innerJoin('this.earningTransaction', 'earningTransaction')
            ->innerJoin('earningTransaction.order', 'userOrder')
            ->andWhere('userOrder.invoiceNumber IN (:invoiceNumbers)')
            ->setParameter('invoiceNumbers', $invoiceNumbers)
        ;

        return $this->getResult();
    }

    public function getSellerAffiliateEarningForCompletion($days, $page = 1, $perPage = 10)
    {
        $dayGaps = Carbon::now()->subDays($days)->endOfDay();
        $offset = ($page - 1) * $perPage;
        $this
            ->qb()
            ->innerJoin('this.earningTransaction', 'earningTransaction')
            ->innerJoin('earningTransaction.orderProduct', 'orderProduct')
            ->innerJoin('orderProduct.orderProductHistories', 'orderProductHistories')
            ->innerJoin('this.user', 'user')
            ->andWhere('orderProduct.orderProductStatus IN (:orderProductStatuses)')
            ->setParameter('orderProductStatuses', array(OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER, OrderProductStatus::STATUS_SELLER_PAYOUT_UN_HELD))
            ->andWhere('this.status != :status')
            ->setParameter('status', Earning::COMPLETE)
            ->andWhere('orderProductHistories.orderProductStatus = :orderProductStatus')
            ->setParameter('orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
            ->andWhere('orderProductHistories.dateAdded <= :dayGaps')
            ->andWhere('this.earningType != :buyerTrasactionType')
            ->andWhere('user.userType = :userType')
            ->groupBy('this')
            ->setParameter('dayGaps', $dayGaps)
            ->setParameter('userType', User::USER_TYPE_SELLER)
            ->setParameter('buyerTrasactionType', EarningType::BUYER_TRANSACTION)
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
        ;

        return $this->getResult();
    }

    public function getBuyerNetworkEarningForCompletion($days, $page = 1, $perPage = 10)
    {
        $dayGaps = Carbon::now()->subDays($days)->endOfDay();
        $offset = ($page - 1) * $perPage;


        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("Earning")
                     ->from("YilinkerCoreBundle:Earning", "Earning")
                     ->innerJoin('YilinkerCoreBundle:EarningTransaction', 'EarningTransaction', Join::WITH, 'EarningTransaction.earning = Earning.earningId')
                     ->innerJoin('YilinkerCoreBundle:UserOrder', 'UserOrder', Join::WITH, 'EarningTransaction.order = UserOrder.orderId')
                     ->innerJoin('YilinkerCoreBundle:OrderProduct', 'orderProduct', Join::WITH, 'UserOrder.orderId = orderProduct.order')
                     ->innerJoin('YilinkerCoreBundle:OrderProductHistory', 'orderProductHistory', Join::WITH, 'orderProductHistory.orderProduct = orderProduct.orderProductId')
                     ->innerJoin('YilinkerCoreBundle:User', 'User', Join::WITH, 'User.userId = Earning.user')
                     ->andWhere("User.userType = :userType")
                     ->andWhere("Earning.status != :status")
                     ->andWhere('orderProductHistory.orderProductStatus = :orderProductStatus')
                     ->andWhere('orderProduct.orderProductStatus IN (:orderProductStatuses)')
                     ->andWhere('orderProductHistory.dateAdded <= :dayGaps')
                     ->andWhere('Earning.earningType = :buyerTrasactionType')
                     ->groupBy('Earning.earningId')
                     ->setParameter('orderProductStatuses', array(OrderProductStatus::STATUS_SELLER_PAYMENT_RELEASED, OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER, OrderProductStatus::STATUS_SELLER_PAYOUT_UN_HELD))
                     ->setParameter('status', Earning::COMPLETE)
                     ->setParameter('orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
                     ->setParameter('dayGaps', $dayGaps)
                     ->setParameter('userType', User::USER_TYPE_SELLER)
                     ->setParameter('buyerTrasactionType', EarningType::BUYER_TRANSACTION)
                     ->setFirstResult($offset)
                     ->setMaxResults($perPage);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get Total Earning by user id
     *
     * @param User $user
     * @return mixed
     */
    public function getTotalEarningByUser (User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("SUM(Earning.amount) as totalEarning")
                     ->from("YilinkerCoreBundle:Earning", "Earning")
                     ->where("Earning.user = :userId")
                     ->setParameter(":userId", $user->getUserId());

        $result = $queryBuilder->getQuery()->getResult();
        $total = array_shift($result);

        return is_null($total['totalEarning']) ? 0 : $total['totalEarning'];
    }

    public function getUserEarningsIn(
        $user, 
        $orderByField = null, 
        $orderByType = null, 
        $earningGroup = null, 
        $earningType = null, 
        $limit = null, 
        $offset = null,
        $excludedStatus = array()
    ){
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("e")
                     ->from("YilinkerCoreBundle:Earning", "e")
                     ->where("e.user = :user")
                     ->setParameter(":user", $user);

        if(!empty($excludedStatus)){
            $queryBuilder->andWhere("e.status NOT IN (:excludedStatus)")->setParameter(":excludedStatus", $excludedStatus);
        }

        if(!is_null($earningGroup)){
            $queryBuilder->innerJoin("YilinkerCoreBundle:EarningGroupMap", "egm", Join::WITH, "e.earningType = egm.earningType")
                         ->andWhere("egm.earningGroup = :earningGroup")
                         ->setParameter(":earningGroup", $earningGroup);
        }

        if(!is_null($earningType)){
            if(is_array($earningType)){
                $queryBuilder->andWhere("e.earningType IN (:earningType)")->setParameter(":earningType", $earningType);
            }
            else{
                $queryBuilder->andWhere("e.earningType = :earningType")->setParameter(":earningType", $earningType);
            }
        }

        if(!is_null($limit) && !is_null($offset)){
            $queryBuilder->setFirstResult($offset)->setMaxResults($limit);
        }

        if(!is_null($orderByField) && !is_null($orderByType)){
            $queryBuilder->orderBy($orderByField, $orderByType);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getEarningTotal($user = null, $dateFrom = null, $dateTo = null, $status = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("SUM(e.amount) as total")
                     ->from("YilinkerCoreBundle:Earning", "e");

        if(!is_null($user)){
            $queryBuilder->andWhere("e.user = :user")->setParameter(":user", $user);
        }

        if(!is_null($status)){
            $queryBuilder->andWhere("e.status = :status")->setParameter(":status", $status);
        }

        if(!is_null($dateFrom) && !is_null($dateTo)){
            $betweenExpr = $queryBuilder->expr()->between("e.dateLastModified", ":dateFrom", ":dateTo");
            $queryBuilder->andWhere($betweenExpr)->setParameter(":dateFrom", $dateFrom)->setParameter(":dateTo", $dateTo);
        }

        $result = $queryBuilder->getQuery()->getResult();
        if(!empty($result)){
            return array_shift($result)["total"];
        }
        else{
            return 0.00;
        }
    }
}
