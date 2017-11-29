<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\Query\ResultSetMapping;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService;
use Yilinker\Bundle\CoreBundle\Repository\BaseRepository\BaseUserOrderRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DateTime;
use Doctrine\Common\Util\Debug;

/**
 * Class UserOrderRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserOrderRepository extends BaseUserOrderRepository
{
    const PAGE_LIMIT = 10;

    /**
     * Sort by date created
     *
     * @var string
     */
    const SORT_BY_DATE_CREATED = 'create';

    /**
     * Sort by date updated
     *
     * @var string
     */
    const SORT_BY_DATE_UPDATED = 'update';

    /**
     * Sort direction asc
     *
     * @var string
     */
    const SORT_DIRECTION_ASC = 'asc';

    /**
     * Sort direction desc
     *
     * @var string
     */
    const SORT_DIRECTION_DESC = 'desc';

    public function getUserOrdersIn($orderIds = array(), $isFlagged = false)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("uo")
                     ->from("YilinkerCoreBundle:UserOrder", "uo", "uo.orderId")
                     ->where("uo.orderId IN (:orderIds)")->setParameter(":orderIds", $orderIds);

        if($isFlagged){
            $queryBuilder->andWhere();
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getUserOrders($offset = 0, $limit = 10, $beginDate = null, $endDate = null, array $status, $isForReseller = null)
    {
        $queryBuilder = $this->createQueryBuilder("uo");

        if(!is_null($beginDate)){
            $beginDateExpr = $queryBuilder->expr()->gte("uo.lastDateModified", ":beginDate");
            $queryBuilder->andWhere($beginDateExpr)
                         ->setParameter(":beginDate", $beginDate);
        }

        if(!is_null($endDate)){
            $endDateExpr = $queryBuilder->expr()->lte("uo.lastDateModified", ":endDate");
            $queryBuilder->andWhere($endDateExpr)
                         ->setParameter(":endDate", $endDate);
        }

        if(!empty($status)){
            $orderStatusExpr = $queryBuilder->expr()->in("uo.orderStatus", ":status");
            $queryBuilder->andWhere($orderStatusExpr)
                         ->setParameter(":status", $status);
        }

        if(!is_null($offset) && !is_null($limit)){
            $queryBuilder->setFirstResult($offset)
                         ->setMaxResults($limit);
        }

        if(!is_null($isForReseller)){
            if($isForReseller){
//                $queryBuilder->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = uo.orderId AND op.manufacturerProductUnit IS NOT NULL");
                $queryBuilder->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = uo.orderId");
            }
            else{
                $queryBuilder->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = uo.orderId AND op.manufacturerProductUnit IS NULL");
            }
            $queryBuilder->groupBy("uo.orderId");
        }

        $collection["orders"] = $queryBuilder->getQuery()->getResult();

        $queryBuilder->setFirstResult(null)
                     ->setMaxResults(null);

        $paginator = new Paginator($queryBuilder);
        $collection["totalResults"] = $paginator->count();

        $collection["totalPage"] = (int)ceil($paginator->count()/$limit);

        return $collection;
    }

    /**
     * Retrieve all seller orders. OrderProducts from other sellers within the same order are excluded.
     *
     * @param int $sellerId
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int[] $orderStatuses
     * @param int[] $paymentMethod
     * @param string $invoiceString
     * @param string $productString
     * @param string $riderString
     * @param int $offset
     * @param int $limit
     * @param int[] $orderProductStatuses
     * @param boolean $isScheduledForPickup
     * @param string $sortBy
     * @param string $sortDirection
     *
     * @return mixed
     */
    public function getTransactionOrderBySeller (
        $sellerId,
        $dateFrom = null,
        $dateTo = null,
        $orderStatuses = null,
        $paymentMethod = null,
        $invoiceString = null,
        $productString = null,
        $riderString = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT,
        $orderProductStatuses = null,
        $isScheduledForPickup = null,
        $sortBy = null,
        $sortDirection = null
    )
    {
        if($orderStatuses === null){
            $orderStatuses = array(
                UserOrder::ORDER_STATUS_PAYMENT_CONFIRMED,
                UserOrder::ORDER_STATUS_COMPLETED,
            );
        }
        if(is_array($orderStatuses) === false){
            $orderStatuses = array( $orderStatuses );
        }

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array(
                          "DISTINCT o.orderId as order_id",
                          "u.userId as buyer_id",
                          "o.dateAdded as date_added",
                          "o.lastDateModified as date_modified",
                          "o.invoiceNumber as invoice_number",
                          "pm.name as payment_type",
                          "pm.paymentMethodId as payment_method_id",
                          "os.name as order_status",
                          "os.orderStatusId as order_status_id",
                          "sum(op.unitPrice * op.quantity) as total_price",
                          "sum(op.unitPrice) as total_unit_price",
                          "sum(op.totalPrice - op.handlingFee) as total_item_price",
                          "sum(op.handlingFee) as total_handling_fee",
                          "sum(op.quantity) as total_quantity",
                          "group_concat(op.productName) as product_names",
                          "count(p.productId) as product_count",
                          "group_concat(ops.name) as order_product_status_names",
                          "group_concat(ops.class) as order_product_status_classes"
                     ))
                     ->from("YilinkerCoreBundle:UserOrder", "o")
                     ->innerJoin("YilinkerCoreBundle:OrderStatus", "os", "WITH", "o.orderStatus = os.orderStatusId")
                     ->innerJoin("YilinkerCoreBundle:PaymentMethod", "pm", "WITH", "o.paymentMethod = pm.paymentMethodId")
                     ->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
                     ->innerJoin("YilinkerCoreBundle:Product", "p", "WITH", "op.product = p.productId AND op.seller = :sellerId")
                     ->innerJoin("YilinkerCoreBundle:User", "u", "WITH", "u.userId = o.buyer")
                     ->innerJoin("YilinkerCoreBundle:OrderProductStatus", "ops", "WITH", "op.orderProductStatus = ops.orderProductStatusId")
                     ->leftJoin("YilinkerCoreBundle:UserOrderFlagged", "f", "WITH", "o.userOrderFlagged = f.userOrderFlaggedId")
                     ->where("o.orderStatus IN (:orderStatuses)")
                     ->andWhere("o.userOrderFlagged IS NULL OR f.status = :flaggedStatusApproved")
                     ->setParameter('sellerId', $sellerId)
                     ->setParameter('flaggedStatusApproved', UserOrderFlagged::APPROVE)
                     ->setParameter('orderStatuses', $orderStatuses);

        if($paymentMethod !== null){
            $queryBuilder->andWhere("o.paymentMethod = :paymentMethod")
                         ->setParameter('paymentMethod', $paymentMethod);
        }

        if($invoiceString !== null){
            $queryBuilder->andWhere("match_against (o.invoiceNumber) against (:invoiceNumber BOOLEAN) > 0")
                         ->setParameter('invoiceNumber', $invoiceString.'*');
        }

        if($productString !== null && strlen($productString)){
            $queryBuilder->andWhere("match_against (op.productName) against (:productString BOOLEAN) > 0")
                         ->setParameter('productString', $productString.'*');
        }

        if($riderString || $isScheduledForPickup !== null){

            $packageQueryBuilder = $this->_em->createQueryBuilder();
            $packageQueryBuilder->select("p.orderId")
                                ->addSelect("GROUP_CONCAT(ph.personInCharge) as HIDDEN personsInCharges")
                                ->addSelect("count(package.packageId) AS HIDDEN packageCount ")
                                ->from("YilinkerCoreBundle:Package", "package")
                                ->leftJoin("YilinkerCoreBundle:PackageHistory", "ph", "WITH", "ph.package = package.packageId");

            if($riderString){
                $packageQueryBuilder->having("match_against (packageHistory.personInCharge) against (:riderString BOOLEAN) > 0");
            }

            if($isScheduledForPickup !== null){
                if($isScheduledForPickup){
                    $packageQueryBuilder->having("packageCount > 0");
                }
                else{
                    $packageQueryBuilder->having("packageCount = 0");
                }
            }

            $packageQueryBuilder->groupBy("p.orderId");
            $queryBuilder->andWhere("o.orderId IN (:packageDql)")
                         ->setParameter("packageDql", $packageQueryBuilder);
        }

        if($orderProductStatuses !== null) {

            if(is_array($orderProductStatuses) === false){
                $orderProductStatuses = array($orderProductStatuses);
            }

            $queryBuilder->andWhere(" ops.orderProductStatusId IN (:orderProductStatuses)")
                         ->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        $sortByField = 'lastDateModified';
        if($sortBy === self::SORT_BY_DATE_CREATED){
            $sortByField = 'dateAdded';
        }
        $sortDirectionField = "DESC";
        if($sortDirection === self::SORT_DIRECTION_ASC){
            $sortDirectionField = "ASC";
        }

        if($dateFrom !== null){
            $queryBuilder->andWhere("o.".$sortByField." >= :dateFrom ")
                         ->setParameter('dateFrom', $dateFrom->toDateTimeString('Y-m-d H:i:s') . ' 00:00:01');
        }

        if($dateTo !== null){
            $queryBuilder->andWhere("o.".$sortByField." <= :dateTo ")
                         ->setParameter('dateTo', $dateTo->toDateTimeString('Y-m-d H:i:s') . ' 23:59:59');
        }

        $queryBuilder->orderBy("o.".$sortByField, $sortDirectionField);
        $queryBuilder->groupBy("o.orderId")
                     ->setMaxResults($limit)
                     ->setFirstResult($offset);

        $query = $queryBuilder->getquery();
        $results = $query->getScalarResult();

        return $this->tokenizeUniqueOrderProductStatuses($results);
    }

    /**
     * Retrieve seller order by invoice number (exact)
     *
     * @param int $sellerId
     * @param string $invoiceNumber
     * @param boolean $hydrateAsEntity Setting this to true will return the whole order entity (i.e. not limited to the given seller)
     * @return mixed|Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    public function sellerTransactionByInvoice($sellerId, $invoiceNumber, $hydrateAsEntity = false)
    {
        if($hydrateAsEntity){
            $queryBuilder = $this->createQueryBuilder("o");
            $queryBuilder->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
                         ->innerJoin("YilinkerCoreBundle:Product", "p", "WITH", "op.product = p.productId AND op.seller = :sellerId")
                         ->leftJoin("YilinkerCoreBundle:UserOrderFlagged", "f", "WITH", "f.userOrderFlaggedId = o.userOrderFlagged")
                         ->where("o.invoiceNumber = :invoiceNumber")
                         ->andWhere("o.userOrderFlagged IS NULL OR f.status = :flaggedStatusApproved")
                         ->setParameter('flaggedStatusApproved', UserOrderFlagged::APPROVE)
                         ->setParameter('sellerId', $sellerId)
                         ->setParameter('invoiceNumber', $invoiceNumber);

            return $queryBuilder->getquery()->getResult();
        }
        else{
            $queryBuilder = $this->_em->createQueryBuilder();
            $queryBuilder->select(array(
                "o.orderId as order_id",
                "u.userId as buyer_id",
                "o.dateAdded as date_added",
                "o.invoiceNumber as invoice_number",
                "pm.name as payment_type",
                "pm.paymentMethodId as payment_method_id",
                "os.name as order_status",
                "os.orderStatusId as order_status_id",
                "f.userOrderFlaggedId as order_flag_id",
                "sum(op.unitPrice * op.quantity) as total_price",
                "sum(op.unitPrice) as total_unit_price",
                "sum(op.totalPrice - op.handlingFee) as total_item_price",
                "sum(op.handlingFee) as total_handling_fee",
                "sum(op.quantity) as total_quantity",
                "group_concat(op.productName) as product_names",
                "count(p.productId) as product_count",
            ))
            ->from("YilinkerCoreBundle:UserOrder", "o")
            ->innerJoin("YilinkerCoreBundle:OrderStatus", "os", "WITH", "o.orderStatus = os.orderStatusId")
            ->innerJoin("YilinkerCoreBundle:PaymentMethod", "pm", "WITH", "o.paymentMethod = pm.paymentMethodId")
            ->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
            // with inhouse product refactor seller id must be taken from order product
            ->innerJoin("YilinkerCoreBundle:Product", "p", "WITH", "op.product = p.productId AND op.seller = :sellerId")
            ->innerJoin("YilinkerCoreBundle:User", "u", "WITH", "u.userId = o.buyer")
            ->leftJoin("YilinkerCoreBundle:UserOrderFlagged", "f", "WITH", "f.userOrderFlaggedId = o.userOrderFlagged")
            ->where("o.invoiceNumber = :invoiceNumber")
            ->andWhere("o.userOrderFlagged IS NULL OR f.status = :flaggedStatusApproved")
            ->setParameter('flaggedStatusApproved', UserOrderFlagged::APPROVE)
            ->setParameter('sellerId', $sellerId)
            ->setParameter('invoiceNumber', $invoiceNumber);

            $queryBuilder->groupBy("o.orderId")
                         ->setMaxResults(1);

            $query = $queryBuilder->getquery();

            return $query->getScalarResult();
        }
    }


    /**
     * Retrieve all buyer orders
     *
     * @param int $buyerId
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int[] $orderStatuses
     * @param int[] $paymentMethod
     * @param string $invoiceString
     * @param string $productString
     * @param string $riderString
     * @param int[] $orderProductStatuses
     * @param boolean $forFeedback
     * @param boolean $isScheduledForPickup
     * @param int $offset
     * @param int $limit
     * @param string $sortBy
     * @param string $sortDirection
     *
     * @return mixed
     */
    public function getTransactionOrderByBuyer (
        $buyerId,
        $dateFrom = null,
        $dateTo = null,
        $orderStatuses = null,
        $paymentMethod = null,
        $invoiceString = null,
        $productString = null,
        $riderString = null,
        $orderProductStatuses = null,
        $forFeedback = null,
        $isScheduledForPickup = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT,
        $sortBy = null,
        $sortDirection = null
    ){
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array(
                          "DISTINCT o.orderId as order_id",
                          "o.dateAdded as date_added",
                          "o.lastDateModified as date_modified",
                          "o.invoiceNumber as invoice_number",
                          "pm.name as payment_type",
                          "pm.paymentMethodId as payment_method_id",
                          "os.name as order_status",
                          "os.orderStatusId as order_status_id",
                          "sum(op.unitPrice * op.quantity) as total_price",
                          "sum(op.unitPrice) as total_unit_price",
                          "sum(op.totalPrice - op.handlingFee) as total_item_price",
                          "sum(op.handlingFee) as total_handling_fee",
                          "sum(op.quantity) as total_quantity",
                          "group_concat(op.productName) as product_names",
                          "count(p.productId) as product_count",
                          "group_concat(ops.name) as order_product_status_names",
                          "group_concat(ops.class) as order_product_status_classes"
                     ))
                     ->from("YilinkerCoreBundle:UserOrder", "o")
                     ->innerJoin("YilinkerCoreBundle:OrderStatus", "os", "WITH", "o.orderStatus = os.orderStatusId")
                     ->innerJoin("YilinkerCoreBundle:PaymentMethod", "pm", "WITH", "o.paymentMethod = pm.paymentMethodId")
                     ->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
                     ->leftJoin("YilinkerCoreBundle:OrderProductStatus", "ops", "WITH", "op.orderProductStatus = ops.orderProductStatusId")
                     ->innerJoin("YilinkerCoreBundle:Product", "p", "WITH", "op.product = p.productId")
                     ->where("o.buyer = :buyerId")
                     ->setParameter('buyerId', $buyerId);

        if($orderStatuses !== null){
            if(is_array($orderStatuses) === false){
                $orderStatuses = array( $orderStatuses );
            }

            $queryBuilder->andWhere("o.orderStatus IN (:orderStatuses)")
                         ->setParameter('orderStatuses', $orderStatuses);
        }

        if($paymentMethod !== null){
            $queryBuilder->andWhere("o.paymentMethod = :paymentMethod")
                         ->setParameter('paymentMethod', $paymentMethod);
        }

        if($invoiceString !== null && strlen($invoiceString)){
            $queryBuilder->andWhere("match_against (o.invoiceNumber) against (:invoiceNumber BOOLEAN) > 0")
                         ->setParameter('invoiceNumber', $invoiceString.'*');
        }

        if($productString !== null && strlen($productString)){
            $queryBuilder->andWhere("match_against (op.productName) against (:productString BOOLEAN) > 0")
                         ->setParameter('productString', $productString.'*');
        }

        if($riderString || $isScheduledForPickup !== null){

            $packageQueryBuilder = $this->_em->createQueryBuilder();
            $packageQueryBuilder->select("p.orderId")
                                ->addSelect("GROUP_CONCAT(ph.personInCharge) as HIDDEN personsInCharges")
                                ->addSelect("count(package.packageId) AS HIDDEN packageCount ")
                                ->from("YilinkerCoreBundle:Package", "package")
                                ->leftJoin("YilinkerCoreBundle:PackageHistory", "ph", "WITH", "ph.package = package.packageId");

            if($riderString){
                $packageQueryBuilder->having("match_against (packageHistory.personInCharge) against (:riderString BOOLEAN) > 0");
            }

            if($isScheduledForPickup !== null){
                if($isScheduledForPickup){
                    $packageQueryBuilder->having("packageCount > 0");
                }
                else{
                    $packageQueryBuilder->having("packageCount = 0");
                }
            }

            $packageQueryBuilder->groupBy("p.orderId");
            $queryBuilder->andWhere("o.orderId IN (:packageDql)")
                         ->setParameter("packageDql", $packageQueryBuilder);
        }


        if($orderProductStatuses !== null) {
            if(is_array($orderProductStatuses) === false){
                $orderProductStatuses = array($orderProductStatuses);
            }
            $queryBuilder->andWhere(" ops.orderProductStatusId IN (:orderProductStatuses)")
                         ->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        if($forFeedback !== null){
            $queryBuilder->addSelect("count(feedback.userFeedbackId) as feedback_count");
            if($forFeedback){
                $queryBuilder->innerJoin(
                    "YilinkerCoreBundle:OrderProductHistory", "oph", "WITH",
                    "oph.orderProduct = op.orderProductId AND oph.orderProductStatus = :itemReceivedStatus"
                )
                ->leftJoin(
                    "YilinkerCoreBundle:UserFeedback", "feedback", "WITH", "feedback.order = o.orderId"
                )
                ->setParameter('itemReceivedStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
                ->having("feedback_count = 0");
            }
            else{
                $queryBuilder->addSelect("count(oph.orderProductHistoryId) as received_history_count");
                $queryBuilder->leftJoin(
                    "YilinkerCoreBundle:OrderProductHistory", "oph", "WITH",
                    "oph.orderProduct = op.orderProductId AND oph.orderProductStatus = :itemReceivedStatus"
                )
                ->leftJoin(
                    "YilinkerCoreBundle:UserFeedback", "feedback", "WITH", "feedback.order = o.orderId"
                )
                ->setParameter('itemReceivedStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
                ->having($queryBuilder->expr()->orX(
                    $queryBuilder->expr()->gt('feedback_count', '0'),
                    $queryBuilder->expr()->eq('received_history_count', '0')
                ));
            }
        }

        $sortByField = 'lastDateModified';
        if($sortBy === self::SORT_BY_DATE_CREATED){
            $sortByField = 'dateAdded';
        }
        $sortDirectionField = "DESC";
        if($sortDirection === self::SORT_DIRECTION_ASC){
            $sortDirectionField = "ASC";
        }

        if($dateFrom !== null){
            $queryBuilder->andWhere("o.".$sortByField." >= :dateFrom ")
                         ->setParameter('dateFrom', $dateFrom->toDateTimeString('Y-m-d H:i:s') . ' 00:00:01');
        }

        if($dateTo !== null){
            $queryBuilder->andWhere("o.".$sortByField." <= :dateTo ")
                         ->setParameter('dateTo', $dateTo->toDateTimeString('Y-m-d H:i:s') . ' 23:59:59');
        }

        $queryBuilder->orderBy("o.".$sortByField, $sortDirectionField);
        $queryBuilder->groupBy("o.orderId")
                     ->addGroupBy("p.productId")
                     ->setMaxResults($limit)
                     ->setFirstResult($offset);

        $query = $queryBuilder->getquery();

        $results = $query->getScalarResult();

        return $this->tokenizeUniqueOrderProductStatuses($results);
    }

    /**
     * Count number of orders for a user
     *
     * @param int $sellerId
     * @param int $buyerId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param int[] $paymentMethod
     * @param int[] $orderStatuses
     * @param string $invoiceString
     * @param string $productString
     * @param string $riderString
     * @param int[] $orderProductStatuses
     * @param boolean $forFeedback
     * @param boolean $isScheduledForPickup
     * @param string $dateBy
     * @param boolean $isIncludeFlagged
     * @return int
     */
    public function getNumberOfOrdersBy(
        $sellerId = null,
        $buyerId = null,
        $dateFrom = null,
        $dateTo = null,
        $paymentMethod = null,
        $orderStatuses = null,
        $invoiceString = null,
        $productString = null,
        $riderString = null,
        $orderProductStatuses = null,
        $forFeedback = null,
        $isScheduledForPickup = null,
        $dateBy = null,
        $isIncludeFlagged = false
    )
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array("COUNT(DISTINCT o.orderId) as order_count"))
                     ->from("YilinkerCoreBundle:UserOrder", "o")
                     ->leftJoin("YilinkerCoreBundle:Package", "package", "WITH", "o.orderId = package.userOrder")
                     ->leftJoin("YilinkerCoreBundle:UserOrderFlagged", "f", "WITH", "f.userOrderFlaggedId = o.userOrderFlagged")
                     ->innerJoin('o.orderProducts', 'op', 'WITH', 'op.order = o.orderId')
                     ->innerJoin('YilinkerCoreBundle:OrderProductStatus', 'ops', 'WITH', 'op.orderProductStatus = ops.orderProductStatusId');

        if($isIncludeFlagged === false){
            $queryBuilder->andWhere("o.userOrderFlagged IS NULL OR f.status = :flaggedStatusApproved")
                         ->setParameter('flaggedStatusApproved', UserOrderFlagged::APPROVE);
        }

        if(null !== $buyerId){
            $queryBuilder->andWhere('o.buyer = :buyer')
                         ->setParameter('buyer', $buyerId);
        }

        if(null !== $sellerId){
            $queryBuilder->innerJoin('op.product', 'p', 'WITH', 'op.product = p.productId AND p.user = :sellerId')
                         ->setParameter('sellerId', $sellerId);
        }

        $dateByField = "lastDateModified";
        if($dateBy === self::SORT_BY_DATE_CREATED){
            $dateByField = "dateAdded";
        }

        if(null !== $dateFrom){
            $queryBuilder->andWhere('o.'.$dateByField.' >= :dateFrom ');
            $queryBuilder->setParameter('dateFrom', $dateFrom);
        }

        if(null !== $dateTo){
            $queryBuilder->andWhere('o.'.$dateByField.' <= :dateTo ');
            $queryBuilder->setParameter('dateTo', $dateTo);
        }

        if(null !== $paymentMethod){
            $queryBuilder->andWhere('o.paymentMethod = :paymentMethod')
                         ->setParameter('paymentMethod', $paymentMethod);
        }

        if(null !== $orderStatuses){
            $queryBuilder->andWhere('o.orderStatus in (:orderStatuses)')
                         ->setParameter('orderStatuses', $orderStatuses);
        }

        if(null !== $invoiceString && strlen($invoiceString)){
           $queryBuilder->andWhere("match_against (o.invoiceNumber) against (:invoiceNumber BOOLEAN) > 0")
                         ->setParameter('invoiceNumber', $invoiceString.'*');

        }

        if(null !== $productString && strlen($productString)){
            $queryBuilder->andWhere("match_against (op.productName) against (:productString BOOLEAN) > 0")
                         ->setParameter('productString', $productString.'*');
        }

        if(null !== $riderString && strlen($riderString)){
            $queryBuilder->leftJoin("YilinkerCoreBundle:PackageHistory", "packageHistory", "WITH", "package.packageId = packageHistory.package")
                         ->andWhere("match_against (packageHistory.personInCharge) against (:riderString BOOLEAN) > 0")
                         ->setParameter('riderString', $riderString.'*');
        }

        if(null !== $orderProductStatuses){
            if(is_array($orderProductStatuses) === false){
                $orderProductStatuses = array($orderProductStatuses);
            }
            $queryBuilder->andWhere("op.orderProductStatus IN (:orderProductStatuses)")
                         ->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        if($forFeedback !== null){
            $queryBuilder->addSelect("count(feedback.userFeedbackId) as feedback_count");
            if($forFeedback){
                $queryBuilder->innerJoin(
                    "YilinkerCoreBundle:OrderProductHistory", "oph", "WITH",
                    "oph.orderProduct = op.orderProductId AND oph.orderProductStatus = :itemReceivedStatus"
                )
                ->leftJoin(
                    "YilinkerCoreBundle:UserFeedback", "feedback", "WITH", "feedback.order = o.orderId"
                )
                ->setParameter('itemReceivedStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
                ->having("feedback_count = 0");
            }
            else{
                $queryBuilder->addSelect("count(oph.orderProductHistoryId) as received_history_count");
                $queryBuilder->leftJoin(
                    "YilinkerCoreBundle:OrderProductHistory", "oph", "WITH",
                    "oph.orderProduct = op.orderProductId AND oph.orderProductStatus = :itemReceivedStatus"
                )
                ->leftJoin(
                    "YilinkerCoreBundle:UserFeedback", "feedback", "WITH", "feedback.order = o.orderId"
                )
                ->setParameter('itemReceivedStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
                ->having($queryBuilder->expr()->orX(
                    $queryBuilder->expr()->gt('feedback_count', '0'),
                    $queryBuilder->expr()->eq('received_history_count', '0')
                ));
            }
        }

        if($isScheduledForPickup !== null){
            $queryBuilder->addSelect("count(package.packageId) as HIDDEN packageCount")
                         ->groupBy("o.orderId");
            if($isScheduledForPickup){
                $queryBuilder->having("packageCount > 0");
            }
            else{
                $queryBuilder->having("packageCount = 0");
            }
        }

        $result = $queryBuilder->getQuery()->getScalarResult();

        return isset($result[0]['order_count']) ? (int) $result[0]['order_count'] : 0;
    }

    /**
     * Get Transactions
     *
     * @param int $orderId
     * @param string $searchKeyword
     * @param int $orderStatus
     * @param int $paymentMethod
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int $offset
     * @param int $limit
     * @param array $orderProductStatuses
     * @return mixed
     */
    public function getTransactionOrder (
        $orderId = null,
        $searchKeyword = null,
        $orderStatus = null,
        $paymentMethod = null,
        $dateFrom = null,
        $dateTo = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT,
        $orderProductStatuses = null
    )
    {

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('order_id', 'orderId');
        $rsm->addScalarResult('buyer_id', 'buyerId');
        $rsm->addScalarResult('order_status_id', 'orderStatusId');
        $rsm->addScalarResult('date_added', 'dateCreated');
        $rsm->addScalarResult('total_price', 'totalPrice');
        $rsm->addScalarResult('invoice_number', 'invoiceNumber');
        $rsm->addScalarResult('payment_type', 'paymentType');
        $rsm->addScalarResult('payment_type_id', 'paymentTypeId');
        $rsm->addScalarResult('order_status', 'orderStatus');
        $rsm->addScalarResult('buyerName', 'buyerName');
        $rsm->addScalarResult('flag_id', 'flagId');
        $rsm->addScalarResult('flag_reason', 'flagReason');
        $rsm->addScalarResult('flag_status', 'flagStatus');
        $rsm->addScalarResult('flag_remarks', 'flagRemarks');
        $rsm->addScalarResult('flag_user', 'flagUser');
        $rsm->addScalarResult('flag_remark_date', 'flagRemarkDate');
        $rsm->addScalarResult('consignee_name', 'consigneeName');
        $rsm->addScalarResult('consignee_contact_number', 'consigneeContactNumber');
        $rsm->addScalarResult('flag_remark_date', 'flagRemarkDate');
        $rsm->addScalarResult('address', 'address');
        $rsm->addScalarResult('contactNumber', 'contactNumber');
        $rsm->addScalarResult('net', 'net');
        $rsm->addScalarResult('additional_cost', 'additionalCost');
        $rsm->addScalarResult('yilinker_charge', 'yilinkerCharge');
        $rsm->addScalarResult('handling_fee', 'handlingFee');
        $rsm->addScalarResult('total_charges', 'totalCharges');
        $rsm->addScalarResult('totalVoucherAmount', 'totalVoucherAmount');
        
   
        $sql = "
            SELECT
                UserOrder.`order_id`,
                UserOrder.`buyer_id`,
                UserOrder.`order_status_id`,
                UserOrder.`payment_method_id`,
                UserOrder.`date_added`,
                UserOrder.`invoice_number`,
                UserOrder.`net`,
                UserOrder.`total_price`,
                UserOrder.`additional_cost`,
                UserOrder.`yilinker_charge`,
                UserOrder.`handling_fee`,
                UserOrder.`consignee_name`,
                UserOrder.`additional_cost` + UserOrder.`yilinker_charge` + UserOrder.`handling_fee` as `total_charges`,
                UserOrder.`consignee_contact_number`,
                UserOrderFlagged.`flag_reason`,
                UserOrderFlagged.`status` AS `flag_status`,
                UserOrderFlagged.`remarks` AS `flag_remarks`,
                UserOrderFlagged.`user_order_flagged_id` AS `flag_id`,
                CONCAT(`flagUser`.`firstname`, ' ', `flagUser`.`lastname`) AS `flag_user`,
                UserOrderFlagged.`date_remarked` AS `flag_remark_date`,
                PaymentMethod.`name` AS `payment_type`,
                PaymentMethod.`payment_method_id` AS `payment_type_id`,
                OrderStatus.`name` AS `order_status`,
                OrderStatus.`order_status_id` AS `order_status_id`,
                CONCAT(User.`first_name`, ' ', User.`last_name`) AS `buyerName`,
                UserOrder.`address`,
                User.contact_number AS `contactNumber`,
                OrderVoucher.value as `totalVoucherAmount`
            FROM UserOrder
            INNER JOIN OrderStatus
                ON OrderStatus.`order_status_id` = UserOrder.`order_status_id`
            INNER JOIN PaymentMethod
                ON PaymentMethod.`payment_method_id` = UserOrder.`payment_method_id`
            INNER JOIN User
              ON User.`user_id` = UserOrder.`buyer_id`
            LEFT JOIN UserOrderFlagged
              ON UserOrder.`user_order_flagged_id` = `UserOrderFlagged`.`user_order_flagged_id`
            LEFT JOIN AdminUser AS `flagUser`
              ON `flagUser`.`admin_user_id` = UserOrderFlagged.`admin_user_id`
            INNER JOIN OrderProduct
                ON OrderProduct.order_id = UserOrder.order_id
            LEFT JOIN OrderProductStatus
                ON OrderProduct.order_product_status_id = OrderProductStatus.order_product_status_id
            LEFT JOIN OrderVoucher
                ON OrderVoucher.order_id = UserOrder.order_id
            WHERE
                UserOrder.order_id > 0
        ";
     
        if ($orderId !== null) {
            $sql .= " AND UserOrder.order_id = :orderId ";
        }

        if ($dateFrom !== null) {
            $sql .= " AND UserOrder.date_added >= :dateFrom ";
        }

        if ($dateTo !== null) {
            $sql .= " AND UserOrder.date_added <= :dateTo ";
        }

        if ($paymentMethod !== null) {
            $sql .= " AND UserOrder.payment_method_id = :paymentMethod ";
        }

        if ($searchKeyword !== null) {
            $sql .= " AND (UserOrder.invoice_number LIKE :searchKeyword OR
             CONCAT(User.`first_name`, ' ', User.`last_name`) LIKE :searchKeyword OR User.email LIKE :searchKeyword) ";
        }

        if ($orderStatus !== null) {
            $sql .= " AND UserOrder.order_status_id = :orderStatus ";
        }

        if ($orderProductStatuses !== null) {
            $sql .= " AND OrderProduct.order_product_status_id IN (:orderProductStatuses) ";
        }
      
        $sql .= "
            GROUP BY UserOrder.order_id
            ORDER BY UserOrder.`date_added` DESC
            LIMIT :limit OFFSET :offset
        ";
       
        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter('limit', (int) $limit);
        $query->setParameter('offset', (int) $offset);

        if ($orderId !== null) {
            $query->setParameter('orderId', $orderId);
        }

        if ($dateFrom !== null) {

            if (!is_object($dateFrom)) {
                $dateFrom = new Carbon($dateFrom);
            }

            $query->setParameter('dateFrom', $dateFrom->startOfDay()->toDateTimeString('Y-m-d H:i:s'));
        }

        if ($dateTo !== null) {

            if (!is_object($dateTo)) {
                $dateTo = new Carbon($dateTo);
            }

            $query->setParameter('dateTo', $dateTo->endOfDay()->toDateTimeString('Y-m-d H:i:s'));
        }
      
        if ($paymentMethod !== null) {
            $query->setParameter('paymentMethod', $paymentMethod);
        }

        if($searchKeyword !== null) {
            $query->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if($orderStatus !== null) {
            $query->setParameter('orderStatus', $orderStatus);
        }

        if($orderProductStatuses !== null) {

            if (!is_array($orderProductStatuses)) {
                $orderProductStatuses = array($orderProductStatuses);
            }

            $query->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        $result = $query->getResult();
    
        $transactionStatusesThatHasAction = array (
            OrderProductStatus::PAYMENT_CONFIRMED ,
            OrderProductStatus::STATUS_READY_FOR_PICKUP,
            OrderProductStatus::STATUS_PRODUCT_ON_DELIVERY,
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED,
            OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_THE_SPOT,
            OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_INSPECTION,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_INSPECTION ,
            OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED,
        );
        $userAddressRepository = $this->_em->getRepository('YilinkerCoreBundle:UserAddress');
     
        foreach ($result as &$userOrder) {
            $userOrder['flagRemarkDate'] = date('h:i A F j, Y', strtotime($userOrder['flagRemarkDate']));
            $userOrder['hasAction'] = false;
            $userOrder['buyerFullAddress'] = '';

            if ($userOrder['flagReason'] == UserOrder::FIRST_TIME_BUYER_AND_MORE_LIMIT) {
                $userOrder['flagReason'] = 'First time buyer and total amount is more than ' . number_format(UserOrder::FIRST_TIME_BUYER_FLAG_AMOUNT, 2);
            }
            elseif ($userOrder['flagReason'] == UserOrder::PREVIOUS_ORDER_HAS_CANCEL_BEFORE_DELIVERY) {
                $userOrder['flagReason'] = 'Previous Order has one or more products that was cancelled before the user received the product';
            }
            elseif ($userOrder['flagReason'] == UserOrder::CANCEL_FREQUENCY_GREATER_THAN_50_PERCENT) {
                $userOrder['flagReason'] = 'Cancel Frequency of buyer is more than 50%';
            }
            elseif ($userOrder['flagReason'] == UserOrder::FIRST_TIME_BUYER_AND_USING_CREDIT_CARD) {
                $userOrder['flagReason'] = 'First time buyer using credit card';
            }
            elseif ($userOrder['flagReason'] == UserOrder::PREVIOUS_ORDER_FLAGGED_REJECTED) {
                $userOrder['flagReason'] = 'Previous Order has been flagged and was rejected';
            }
            
            if($userOrder['orderId']== 33610 || $userOrder['orderId']== 33609  )  continue;
            
           $orderProducts = $this->_em->getRepository('YilinkerCoreBundle:OrderProduct')
                                       ->findBy(array(
                                           'order' => $userOrder['orderId'],
                                           'orderProductStatus' => $transactionStatusesThatHasAction
                                       ));

            if ($orderProducts) {
                  $userOrder['hasAction'] = true;
            } 
          
            $buyerAddress = $userAddressRepository->findOneBy(array('user' => $userOrder['buyerId'], 'isDefault' => 1));

            if ($buyerAddress instanceof UserAddress) {               
                $userOrder['buyerFullAddress'] = $buyerAddress->getAddressString();
            }
       
        }
  
        return $result;
    }

    /**
     * Get Transaction count
     *
     * @param int $orderId
     * @param string $searchKeyword
     * @param int $orderStatus
     * @param int $paymentMethod
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param array $orderProductStatuses
     * @return mixed
     */
    public function getTransactionOrderCount (
        $orderId = null,
        $searchKeyword = null,
        $orderStatus = null,
        $paymentMethod = null,
        $dateFrom = null,
        $dateTo = null,
        $orderProductStatuses = null
    )
    {

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('order_id', 'orderId');

        $sql = "
            SELECT
                UserOrder.`order_id`
            FROM UserOrder
            INNER JOIN OrderStatus
                ON OrderStatus.`order_status_id` = UserOrder.`order_status_id`
            INNER JOIN PaymentMethod
                ON PaymentMethod.`payment_method_id` = UserOrder.`payment_method_id`
            INNER JOIN User
              ON User.`user_id` = UserOrder.`buyer_id`
            LEFT JOIN UserOrderFlagged
              ON UserOrder.`user_order_flagged_id` = `UserOrderFlagged`.`user_order_flagged_id`
            LEFT JOIN AdminUser AS `flagUser`
              ON `flagUser`.`admin_user_id` = UserOrderFlagged.`admin_user_id`
            INNER JOIN OrderProduct
                ON OrderProduct.order_id = UserOrder.order_id
            LEFT JOIN OrderProductStatus
                ON OrderProduct.order_product_status_id = OrderProductStatus.order_product_status_id
            WHERE
                UserOrder.order_id > 0
        ";

        if ($orderId !== null) {
            $sql .= " AND UserOrder.order_id = :orderId ";
        }

        if ($dateFrom !== null) {
            $sql .= " AND UserOrder.date_added >= :dateFrom ";
        }

        if ($dateTo !== null) {
            $sql .= " AND UserOrder.date_added <= :dateTo ";
        }

        if ($paymentMethod !== null) {
            $sql .= " AND UserOrder.payment_method_id = :paymentMethod ";
        }

        if ($searchKeyword !== null) {
            $sql .= " AND (UserOrder.invoice_number LIKE :searchKeyword OR
             CONCAT(User.`first_name`, ' ', User.`last_name`) LIKE :searchKeyword) ";
        }

        if ($orderStatus !== null) {
            $sql .= " AND UserOrder.order_status_id = :orderStatus ";
        }

        if ($orderProductStatuses !== null) {
            $sql .= " AND OrderProduct.order_product_status_id IN (:orderProductStatuses) ";
        }

        $sql .= "
            GROUP BY UserOrder.order_id
            ORDER BY UserOrder.`date_added` DESC
        ";

        $query = $this->_em->createNativeQuery($sql, $rsm);

        if ($orderId !== null) {
            $query->setParameter('orderId', $orderId);
        }

        if ($dateFrom !== null) {

            if (!is_object($dateFrom)) {
                $dateFrom = new Carbon($dateFrom);
            }

            $query->setParameter('dateFrom', $dateFrom->startOfDay()->toDateTimeString('Y-m-d H:i:s'));
        }

        if ($dateTo !== null) {

            if (!is_object($dateTo)) {
                $dateTo = new Carbon($dateTo);
            }

            $query->setParameter('dateTo', $dateTo->endOfDay()->toDateTimeString('Y-m-d H:i:s'));
        }

        if ($paymentMethod !== null) {
            $query->setParameter('paymentMethod', $paymentMethod);
        }

        if($searchKeyword !== null) {
            $query->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if($orderStatus !== null) {
            $query->setParameter('orderStatus', $orderStatus);
        }

        if($orderProductStatuses !== null) {

            if (!is_array($orderProductStatuses)) {
                $orderProductStatuses = array($orderProductStatuses);
            }

            $query->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        return sizeof($query->getResult());
    }

    /**
     * Get Transaction Order Product by orderId
     *
     * @param null $orderId
     * @param null $orderProductIds
     * @param null $sellerId
     * @param null $buyerId
     * @param null $orderProductStatuses
     * @return array
     */
    public function getTransactionOrderProducts (
        $orderId = null,
        $orderProductIds = null,
        $sellerId = null,
        $buyerId = null,
        $orderProductStatuses = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('order_product_id', 'orderProductId');
        $rsm->addScalarResult('product_id', 'productId');
        $rsm->addScalarResult('user_id', 'userId');
        $rsm->addScalarResult('product_name', 'productName');
        $rsm->addScalarResult('fullName', 'fullName');
        $rsm->addScalarResult('contactNumber', 'contactNumber');
        $rsm->addScalarResult('quantity', 'quantity');
        $rsm->addScalarResult('unit_price', 'unitPrice');
        $rsm->addScalarResult('orig_price', 'origPrice');
        $rsm->addScalarResult('handling_fee', 'handlingFee');
        $rsm->addScalarResult('total_price', 'totalPrice');
        $rsm->addScalarResult('name', 'orderProductStatus');
        $rsm->addScalarResult('isItemReceivedByBuyer', 'isItemReceivedByBuyer');
        $rsm->addScalarResult('order_id', 'orderId');
        $rsm->addScalarResult('invoice_number', 'invoiceNumber');
        $rsm->addScalarResult('order_product_status_id', 'orderProductStatusId');
        $rsm->addScalarResult('attributes', 'attributes');
        $rsm->addScalarResult('store_type', 'storeType');
        $rsm->addScalarResult('buyerFullName', 'buyerFullName');
        $rsm->addScalarResult('buyerContactNumber', 'buyerContactNumber');
        $rsm->addScalarResult('buyerId', 'buyerId');
        $rsm->addScalarResult('supplierPayout', 'supplierPayout');
        $rsm->addScalarResult('supplierName', 'supplierName');
        $rsm->addScalarResult('isAffiliate', 'isAffiliate');
        $rsm->addScalarResult('commission', 'commission');
        $rsm->addScalarResult('sku', 'sku');
        $rsm->addScalarResult('productSlug', 'productSlug');
        $rsm->addScalarResult('productId', 'productId');
        $sqlQuery = "
            SELECT
                COUNT(OrderProductHistory.`order_product_id`) as `isItemReceivedByBuyer`,
                OrderProduct.`order_product_id`,
                OrderProduct.`product_id`,
                Product.`user_id`,
                OrderProduct.`product_name`,
                OrderProduct.`sku`,
                CONCAT(User.`first_name`, ' ', User.`last_name`) AS `fullName`,
                User.`contact_number` AS `contactNumber`,
                OrderProduct.`quantity`,
                OrderProduct.`unit_price`,
                OrderProduct.`orig_price`,
                OrderProduct.`handling_fee`,
                (OrderProduct.`unit_price` * OrderProduct.`quantity`) as total_price,
                OrderProduct.`commission`,
                OrderProductStatus.`name`,
                UserOrder.`order_id`,
                UserOrder.`invoice_number`,
                OrderProduct.`order_product_status_id`,
                OrderProduct.`attributes`,
                Store.`store_type`,
                IF(Store.`store_type` = :affiliateType, true, false) AS isAffiliate,
                CONCAT(Buyer.`first_name`, ' ', Buyer.`last_name`) AS `buyerFullName`,
                Buyer.`contact_number` AS `buyerContactNumber`,
                Buyer.user_id AS `buyerId`,
                COALESCE(ManufacturerProductUnit.unit_price, '0.0000') AS `supplierPayout`,
                COALESCE(Manufacturer.name, '') as `supplierName`,
                Product.`slug` as `productSlug`,
                Product.`product_id` as `productId`
            FROM
                OrderProduct
            JOIN Product
                ON Product.product_id = OrderProduct.product_id
            JOIN User
                ON User.user_id = Product.user_id
            JOIN Store
                ON User.user_id = Store.user_id
            LEFT JOIN OrderProductStatus
                ON OrderProductStatus.order_product_status_id = OrderProduct.order_product_status_id
            LEFT JOIN OrderProductHistory
                ON OrderProduct.order_product_id = OrderProductHistory.order_product_id AND
                   OrderProductHistory.order_product_status_id = :orderProductStatus
            LEFT JOIN UserOrder
                ON UserOrder.order_id = OrderProduct.order_id
            LEFT JOIN ManufacturerProductUnit
                ON ManufacturerProductUnit.manufacturer_product_unit_id = OrderProduct.manufacturer_product_unit_id
            LEFT JOIN ManufacturerProduct
                ON ManufacturerProduct.manufacturer_product_id = ManufacturerProductUnit.manufacturer_product_id
            LEFT JOIN Manufacturer
                ON Manufacturer.manufacturer_id = ManufacturerProduct.manufacturer_id
            JOIN User AS Buyer
                ON Buyer.user_id = UserOrder.buyer_id
            WHERE
                OrderProduct.order_id > 0
        ";

        if ($orderId !== null) {
            $sqlQuery .= " AND OrderProduct.order_id = :orderId ";
        }

        if ($orderProductIds !== null) {
            $sqlQuery .= " AND OrderProduct.order_product_id IN (:orderProductIds) ";
        }

        if ($sellerId !== null) {
            $sqlQuery .= " AND User.user_id = :sellerId ";
        }

        if ($buyerId !== null) {
            $sqlQuery .= " AND UserOrder.buyer_id = :buyerId ";
        }

        if ($orderProductStatuses !== null) {

            if (!is_array($orderProductStatuses)) {
                $orderProductStatuses = array ($orderProductStatuses);
            }

            $sqlQuery .= " AND OrderProduct.order_product_status_id IN (:orderProductStatuses) ";
        }

        $sqlQuery .= "GROUP BY OrderProduct.order_product_id";

        $query = $this->_em->createNativeQuery($sqlQuery, $rsm);


        if ($orderId !== null) {
            $query->setParameter('orderId', $orderId);
        }

        if ($orderProductIds !== null) {

            if (!is_array($orderProductIds)) {
                $orderProductIds = array ($orderProductIds);
            }

            $query->setParameter('orderProductIds', $orderProductIds);
        }

        if ($sellerId !== null) {
            $query->setParameter('sellerId', $sellerId);
        }

        if ($buyerId !== null) {
            $query->setParameter('buyerId', $buyerId);
        }

        if ($orderProductStatuses !== null) {

            if (!is_array($orderProductStatuses)) {
                $orderProductStatuses = array($orderProductStatuses);
            }

            $query->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        $query->setParameter('orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
            ->setParameter('affiliateType', Store::STORE_TYPE_RESELLER);
        $userAddressRepository = $this->_em->getRepository('YilinkerCoreBundle:UserAddress');

        $orderProducts = $query->getResult();

        foreach ($orderProducts as &$userOrder) {
            $userOrder['buyerFullAddress'] = '';
            $userOrder['sellerFullAddress'] = '';

            $buyerAddress = $userAddressRepository->findOneBy(array('user' => $userOrder['buyerId'], 'isDefault' => 1));
            $sellerAddress = $userAddressRepository->findOneBy(array('user' => $userOrder['userId'], 'isDefault' => 1));

            if ($buyerAddress instanceof UserAddress) {
                $userOrder['buyerFullAddress'] = $buyerAddress->getAddressString();
            }

            if ($sellerAddress instanceof UserAddress) {
                $userOrder['sellerFullAddress'] = $sellerAddress->getAddressString();
            }

        }

        return $orderProducts;
    }

    /**
     * Get Buyer refund Order Product
     *
     * @param null $orderId
     * @param null $orderProductIds
     * @param null $sellerId
     * @param null $buyerId
     * @param null $orderProductStatuses
     * @return array
     */
    public function getBuyerRefundOrderProducts ($orderId = null, $orderProductIds = null, $sellerId = null, $buyerId = null, $orderProductStatuses = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('order_product_id', 'orderProductId');
        $rsm->addScalarResult('product_id', 'productId');
        $rsm->addScalarResult('user_id', 'userId');
        $rsm->addScalarResult('product_name', 'productName');
        $rsm->addScalarResult('fullName', 'fullName');
        $rsm->addScalarResult('contactNumber', 'contactNumber');
        $rsm->addScalarResult('quantity', 'quantity');
        $rsm->addScalarResult('unit_price', 'unitPrice');
        $rsm->addScalarResult('orig_price', 'origPrice');
        $rsm->addScalarResult('handling_fee', 'handlingFee');
        $rsm->addScalarResult('total_price', 'totalPrice');
        $rsm->addScalarResult('name', 'orderProductStatus');
        $rsm->addScalarResult('isItemReceivedByBuyer', 'isItemReceivedByBuyer');
        $rsm->addScalarResult('order_id', 'orderId');
        $rsm->addScalarResult('invoice_number', 'invoiceNumber');
        $rsm->addScalarResult('order_product_status_id', 'orderProductStatusId');
        $rsm->addScalarResult('attributes', 'attributes');
        $rsm->addScalarResult('store_type', 'storeType');
        $rsm->addScalarResult('buyerFullName', 'buyerFullName');
        $rsm->addScalarResult('buyerContactNumber', 'buyerContactNumber');
        $rsm->addScalarResult('buyerId', 'buyerId');
        $rsm->addScalarResult('supplierPayout', 'supplierPayout');
        $rsm->addScalarResult('supplierName', 'supplierName');
        $rsm->addScalarResult('isAffiliate', 'isAffiliate');
        $rsm->addScalarResult('commission', 'commission');

        $sqlQuery = "
            SELECT
                COUNT(OrderProductHistory.`order_product_id`) as `isItemReceivedByBuyer`,
                OrderProduct.`order_product_id`,
                OrderProduct.`product_id`,
                Product.`user_id`,
                OrderProduct.`product_name`,
                CONCAT(User.`first_name`, ' ', User.`last_name`) AS `fullName`,
                User.`contact_number` AS `contactNumber`,
                OrderProduct.`quantity`,
                OrderProduct.`unit_price`,
                OrderProduct.`orig_price`,
                OrderProduct.`handling_fee`,
                OrderProduct.`commission`,
                (OrderProduct.`unit_price` * OrderProduct.`quantity`) as total_price,
                OrderProductStatus.`name`,
                UserOrder.`order_id`,
                UserOrder.`invoice_number`,
                OrderProduct.`order_product_status_id`,
                OrderProduct.`attributes`,
                Store.`store_type`,
                IF(Store.`store_type` = :affiliateType, true, false) AS isAffiliate,
                CONCAT(Buyer.`first_name`, ' ', Buyer.`last_name`) AS `buyerFullName`,
                Buyer.`contact_number` AS `buyerContactNumber`,
                Buyer.user_id AS `buyerId`,
                COALESCE(ManufacturerProductUnit.unit_price, '0.0000') AS `supplierPayout`,
                COALESCE(Manufacturer.name, '') as `supplierName`
            FROM
                OrderProduct
            JOIN Product
                ON Product.product_id = OrderProduct.product_id
            JOIN User
                ON User.user_id = Product.user_id
            JOIN Store
                ON User.user_id = Store.user_id
            JOIN OrderProductStatus
                ON OrderProductStatus.order_product_status_id = OrderProduct.order_product_status_id
            LEFT JOIN OrderProductHistory
                ON OrderProduct.order_product_id = OrderProductHistory.order_product_id AND
                   OrderProductHistory.order_product_status_id = :orderProductStatus
            LEFT JOIN UserOrder
                ON UserOrder.order_id = OrderProduct.order_id
            LEFT JOIN ManufacturerProductUnit
                ON ManufacturerProductUnit.manufacturer_product_unit_id = OrderProduct.manufacturer_product_unit_id
            LEFT JOIN ManufacturerProduct
                ON ManufacturerProduct.manufacturer_product_id = ManufacturerProductUnit.manufacturer_product_id
            LEFT JOIN Manufacturer
                ON Manufacturer.manufacturer_id = ManufacturerProduct.manufacturer_id
            JOIN User AS Buyer
                ON Buyer.user_id = UserOrder.buyer_id
            WHERE
                OrderProduct.order_id > 0
        ";

        if ($orderId !== null) {
            $sqlQuery .= " AND OrderProduct.order_id = :orderId ";
        }

        if ($orderProductIds !== null) {
            $sqlQuery .= " AND OrderProduct.order_product_id IN (:orderProductIds) ";
        }

        if ($sellerId !== null) {
            $sqlQuery .= " AND User.user_id = :sellerId ";
        }

        if ($buyerId !== null) {
            $sqlQuery .= " AND UserOrder.buyer_id = :buyerId ";
        }

        if ($orderProductStatuses !== null) {

            if (!is_array($orderProductStatuses)) {
                $orderProductStatuses = array ($orderProductStatuses);
            }

            $sqlQuery .= " AND OrderProduct.order_product_status_id IN (:orderProductStatuses) ";
        }

        $sqlQuery .= " GROUP BY OrderProduct.order_product_id";

        $query = $this->_em->createNativeQuery($sqlQuery, $rsm);


        if ($orderId !== null) {
            $query->setParameter('orderId', $orderId);
        }

        if ($orderProductIds !== null) {

            if (!is_array($orderProductIds)) {
                $orderProductIds = array ($orderProductIds);
            }

            $query->setParameter('orderProductIds', $orderProductIds);
        }

        if ($sellerId !== null) {
            $query->setParameter('sellerId', $sellerId);
        }

        if ($buyerId !== null) {
            $query->setParameter('buyerId', $buyerId);
        }

        if ($orderProductStatuses !== null) {

            if (!is_array($orderProductStatuses)) {
                $orderProductStatuses = array($orderProductStatuses);
            }

            $query->setParameter('orderProductStatuses', $orderProductStatuses);
        }

        $query->setParameter('orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
              ->setParameter('affiliateType', Store::STORE_TYPE_RESELLER);
        $userAddressRepository = $this->_em->getRepository('YilinkerCoreBundle:UserAddress');

        $orderProducts = $query->getResult();

        foreach ($orderProducts as &$userOrder) {
            $userOrder['buyerFullAddress'] = '';
            $userOrder['sellerFullAddress'] = '';

            $buyerAddress = $userAddressRepository->findOneBy(array('user' => $userOrder['buyerId'], 'isDefault' => 1));
            $sellerAddress = $userAddressRepository->findOneBy(array('user' => $userOrder['userId'], 'isDefault' => 1));

            if ($buyerAddress instanceof UserAddress) {
                $userOrder['buyerFullAddress'] = $buyerAddress->getAddressString();
            }

            if ($sellerAddress instanceof UserAddress) {
                $userOrder['sellerFullAddress'] = $sellerAddress->getAddressString();
            }

        }

        return $orderProducts;
    }

    /**
     * Get Seller Payout list
     *
     * @param null $searchKeyword
     * @param null $dateFrom
     * @param null $dateTo
     * @param int $payoutDaysElapsed
     * @param int $sellerTypeId
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getSellerPayoutList(
        $searchKeyword = null,
        $dateFrom = null,
        $dateTo = null,
        $payoutDaysElapsed = 7,
        $sellerTypeId = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT)
    {
        $orderProductStatuses = array(
            OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER,
            OrderProductStatus::STATUS_SELLER_PAYOUT_UN_HELD
        );

        $em = $this->getEntityManager();
        $tbOrderProductHistory = $em->getRepository('YilinkerCoreBundle:OrderProductHistory');
        $qb = $tbOrderProductHistory->createQueryBuilder('orderProductHistorySub')
                                    ->select('IDENTITY(orderProductHistorySub.orderProduct)')
                                    ->where("DATE_DIFF(:dateNow, orderProductHistorySub.dateAdded) >= :payoutDayElapsed")
                                    ->andWhere('orderProductHistorySub.orderProductStatus = :itemReceivedStatus')
                                    ->groupBy('orderProductHistorySub.orderProductHistoryId');

        $subQuery = $qb->getDQL();

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('User.userId, Store.storeName, Bank.bankName, BankAccount.accountName,
                               BankAccount.accountNumber, User.email, User.contactNumber, User.firstName, User.lastName,
                               Store.storeType as isAffiliate, GROUP_CONCAT(OrderProduct.orderProductId) AS orderProducts')
                     ->from('YilinkerCoreBundle:OrderProduct', 'OrderProduct')
                     ->leftJoin('YilinkerCoreBundle:UserOrder', 'UserOrder', 'WITH', 'OrderProduct.order = UserOrder.orderId')
                     ->leftJoin('YilinkerCoreBundle:OrderProductStatus', 'OrderProductStatus', 'WITH', 'OrderProduct.orderProductStatus = OrderProductStatus.orderProductStatusId')
                     ->leftJoin('YilinkerCoreBundle:User', 'User', 'WITH', 'User.userId = OrderProduct.seller')
                     ->leftJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'Store.user = User.userId')
                     ->leftJoin('YilinkerCoreBundle:BankAccount', 'BankAccount', 'WITH', 'BankAccount.user = User.userId AND BankAccount.isDefault = 1')
                     ->leftJoin('YilinkerCoreBundle:Bank', 'Bank', 'WITH', 'BankAccount.bank = Bank.bankId')
                     ->where( 'OrderProduct.orderProductId IN ('.$subQuery.')')
                     ->andWhere('OrderProductStatus.orderProductStatusId IN (:orderProductStatuses)')
                     ->setParameter('orderProductStatuses', $orderProductStatuses)
                     ->setParameter('dateNow', Carbon::now()->format('Y-m-d'))
                     ->setParameter('payoutDayElapsed', $payoutDaysElapsed)
                     ->setParameter('itemReceivedStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER);

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere('(Store.storeName LIKE :searchKeyword OR User.email LIKE :searchKeyword)')
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($dateFrom !== null) {
            $queryBuilder->andWhere('UserOrder.dateAdded >= :dateFrom ')
                         ->setParameter('dateFrom', new Carbon($dateFrom));
        }

        if ($dateTo !== null) {
            $queryBuilder->andWhere('UserOrder.dateAdded < :dateTo ')
                         ->setParameter('dateTo', new Carbon($dateTo));
        }

        if ($sellerTypeId !== null) {
            $queryBuilder->andWhere('Store.storeType = :storeType ')
                         ->setParameter('storeType', $sellerTypeId);
        }

        $queryBuilder->groupBy('User.userId');

        $sellerCount = count($queryBuilder->getQuery()->getResult());

        $qbResult = $queryBuilder->getQuery();
        $sellers = $qbResult->getResult();

        $result = compact (
            'sellers',
            'sellerCount'
        );

        return $result;
    }

    /**
     * Get Manufacturer Payout list
     *
     * @param null $searchKeyword
     * @param null $dateFrom
     * @param null $dateTo
     * @param int $payoutDaysElapsed
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getManufacturerPayoutList (
        $searchKeyword = null,
        $dateFrom = null,
        $dateTo = null,
        $payoutDaysElapsed = 7,
        $offset = 0,
        $limit = self::PAGE_LIMIT)
    {
        $orderProductStatuses = array (
            OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER,
            OrderProductStatus::STATUS_SELLER_PAYOUT_UN_HELD
        );

        $em = $this->getEntityManager();
        $tbOrderProductHistory = $em->getRepository('YilinkerCoreBundle:OrderProductHistory');
        $qb = $tbOrderProductHistory->createQueryBuilder('orderProductHistorySub')
                                    ->select('IDENTITY(orderProductHistorySub.orderProduct)')
                                    ->where("DATE_DIFF(:dateNow, orderProductHistorySub.dateAdded) >= :payoutDayElapsed")
                                    ->andWhere('orderProductHistorySub.orderProductStatus = :itemReceivedStatus')
                                    ->groupBy('orderProductHistorySub.orderProductHistoryId');

        $subQuery = $qb->getDQL();

        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select('Manufacturer.manufacturerId, Manufacturer.name, Manufacturer.contactNumber, Manufacturer.referenceId,
                               GROUP_CONCAT(OrderProduct.orderProductId) AS orderProducts, SUM(OrderProduct.totalPrice) as totalAmount')
                     ->from('YilinkerCoreBundle:OrderProduct', 'OrderProduct')
                     ->leftJoin('YilinkerCoreBundle:UserOrder', 'UserOrder', 'WITH', 'OrderProduct.order = UserOrder.orderId')
                     ->leftJoin('YilinkerCoreBundle:OrderProductStatus', 'OrderProductStatus', 'WITH', 'OrderProduct.orderProductStatus = OrderProductStatus.orderProductStatusId')
                     ->innerJoin('YilinkerCoreBundle:ManufacturerProductUnit', 'ManufacturerProductUnit', 'WITH', 'ManufacturerProductUnit.manufacturerProductUnitId = OrderProduct.manufacturerProductUnit')
                     ->innerJoin('YilinkerCoreBundle:ManufacturerProduct', 'ManufacturerProduct', 'WITH', 'ManufacturerProduct.manufacturerProductId = ManufacturerProductUnit.manufacturerProduct')
                     ->innerJoin('YilinkerCoreBundle:Manufacturer', 'Manufacturer', 'WITH', 'Manufacturer.manufacturerId = ManufacturerProduct.manufacturer')
                     ->where('OrderProduct.orderProductId IN (' . $subQuery . ')')
                     ->andWhere('OrderProductStatus.orderProductStatusId IN (:orderProductStatuses)')
                     ->setParameter('orderProductStatuses', $orderProductStatuses)
                     ->setParameter('dateNow', Carbon::now()->format('Y-m-d'))
                     ->setParameter('payoutDayElapsed', $payoutDaysElapsed)
                     ->setParameter('itemReceivedStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER);

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere('(Manufacturer.name LIKE :searchKeyword OR Manufacturer.referenceId LIKE :searchKeyword)')
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($dateFrom !== null) {
            $queryBuilder->andWhere('UserOrder.dateAdded >= :dateFrom ')
                         ->setParameter('dateFrom', new Carbon($dateFrom));
        }

        if ($dateTo !== null) {
            $queryBuilder->andWhere('UserOrder.dateAdded < :dateTo ')
                         ->setParameter('dateTo', new Carbon($dateTo));
        }

        $queryBuilder->groupBy('Manufacturer.manufacturerId');

        $manufacturerCount = count($queryBuilder->getQuery()->getResult());

        $qbResult = $queryBuilder->setFirstResult($offset)
                                 ->setMaxResults($limit)
                                 ->getQuery();
        $manufacturers = $qbResult->getResult();

        $result = compact (
            'manufacturers',
            'manufacturerCount'
        );

        return $result;
    }

    /**
     * Get buyer refund list
     * @param null $searchKeyword
     * @param null $dateFrom
     * @param null $dateTo
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getBuyerRefundList()
    {
        $orderProductStatuses = TransactionService::getRefundOrderProductStatus();

        $subQueryBuilder = $this->_em->createQueryBuilder('SubOrderHistory')
                                ->select('SubOrderHistory')
                                ->from('YilinkerCoreBundle:OrderHistory', 'SubOrderHistory')
                                ->where('SubOrderHistory.order = this')
                                ->andWhere('SubOrderHistory.orderStatus = :orderStatus');

        $this->qb()
             ->leftJoin('this.buyer', 'User')
             ->leftJoin('this.orderHistories', 'OrderHistory')
             ->leftJoin('this.orderProducts', 'OrderProduct')
             ->leftJoin('OrderProduct.orderProductHistories', 'OrderProductHistory')
             ->leftJoin('YilinkerCoreBundle:OrderProductStatus', 'OrderProductStatus', 'WITH', 'OrderProductStatus.orderProductStatusId = OrderProduct.orderProductStatus')
             ->leftJoin('YilinkerCoreBundle:DisputeDetail', 'DisputeDetail', 'WITH', 'DisputeDetail.orderProduct = OrderProduct')
             ->leftJoin('DisputeDetail.dispute', 'Dispute')
             ->andWhere($this->getQB()->expr()->orX(
                 $this->getQB()->expr()->eq('OrderHistory.orderStatus', OrderStatus::PAYMENT_CONFIRMED),
                 $this->getQB()->expr()->andX(
                     $this->getQB()->expr()->eq('this.paymentMethod', PaymentMethod::PAYMENT_METHOD_COD),
                     $this->getQB()->expr()->eq('OrderProductHistory.orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
                 )
             ))
             ->andWhere($this->getQB()->expr()->orX(
                 $this->getQB()->expr()->in('OrderProduct.orderProductStatus', $orderProductStatuses),
                 $this->getQB()->expr()->exists($subQueryBuilder->getDQL())
             ))
             ->setParameter('orderStatus', OrderStatus::ORDER_REJECTED_FOR_FRAUD)
             ->groupBy('Dispute.disputeId');

        return $this;
    }

    public function filterBy(array $args)
    {
        if (isset($args['name']) && trim($args['name'])) {
            $this->getQB()->andWhere("(CONCAT(User.firstName, ' ', User.lastName) LIKE :nameOrEmail OR User.email LIKE :nameOrEmail)")
                          ->setParameter('nameOrEmail', '%' . $args['name'] . '%');
        }

        if (isset($args['dateFrom']) && trim($args['dateFrom'])) {
            $this->getQB()->andWhere('this.dateAdded >= :dateFrom ')
                          ->setParameter('dateFrom', new Carbon($args['dateFrom']));
        }

        if (isset($args['dateTo']) && trim($args['dateTo'])) {
            $this->getQB()->andWhere('this.dateAdded <= :dateTo ')
                          ->setParameter('dateTo', new Carbon($args['dateTo']));
        }

        return $this;
    }

    public function getOrderByStatus(User $buyer, $orderId, $orderStatuses)
    {
        $queryBuilder = $this->createQueryBuilder("uo");

        $orx = $queryBuilder->expr()->orX();

        foreach($orderStatuses as $orderStatus){
            $orx->add($queryBuilder->expr()->eq("uo.orderStatus", $orderStatus));
        }

        $queryBuilder->where("uo.buyer = :buyer")
                     ->andWhere("uo.orderId = :orderId")
                     ->andWhere($orx)
                     ->setParameter(":buyer", $buyer)
                     ->setParameter(":orderId", $orderId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function approvedFlagged($userOrder, $count = false, $reverse = false)
    {
        $this
            ->qb()
            ->innerJoin('this.userOrderFlagged', 'userOrderFlagged')
            ->andWhere('this.buyer = :buyer')
            ->andWhere('userOrderFlagged.status '.($reverse ? '<>': '=').' :flagStatus')
            ->setParameter('buyer', $userOrder->getBuyer())
            ->setParameter('flagStatus', UserOrderFlagged::APPROVE)
            ->setMaxResults(1)
        ;

        if (!$count) {
            $result = $this->getResult();
            return array_shift($result);
        }

        return $this->getCount();
    }

    public function checkIfFlagged(&$userOrder)
    {
        $orders = $this->qb()
             ->andWhere('this.buyer = :buyer')
             ->setParameter('buyer', $userOrder->getBuyer())
             ->orderBy('this.orderId', 'DESC')
             ->setMaxResults(1)
             ->getResult()
        ;
        $previousOrder = array_shift($orders);
        $userOrderFlagged = new UserOrderFlagged;

        if ($previousOrder) {
            $orderProductStatus = $this->_em->getReference(
                'YilinkerCoreBundle:OrderProductStatus',
                OrderProduct::STATUS_BUYER_CANCELLATION_BEFORE_DELIVERY_APPROVED
            );
            $canceledOrders = $previousOrder->getOrderProductWithStatus($orderProductStatus, 1);
            if ($canceledOrders->count()) {
                //$userOrderFlagged->setFlagReason(UserOrder::PREVIOUS_ORDER_HAS_CANCEL_BEFORE_DELIVERY);
            }
            else {
                $tbOrderProduct = $this->_em->getRepository('YilinkerCoreBundle:OrderProduct');
                $fraudFrequency = $tbOrderProduct->fraudFrequency($userOrder->getBuyer());
                if ($fraudFrequency >= 50) {
                    $userOrderFlagged->setFlagReason(UserOrder::CANCEL_FREQUENCY_GREATER_THAN_50_PERCENT);
                }
                else {
                    $orderFlagged = $previousOrder->getUserOrderFlagged();
                    if ($orderFlagged) {
                        $flagStatus = $orderFlagged->getStatus();
                        if ($flagStatus == UserOrderFlagged::REJECT) {
                            $userOrderFlagged->setFlagReason(UserOrder::PREVIOUS_ORDER_FLAGGED_REJECTED);
                        }
                    }
                }
            }
        }
        else {
            if (bccomp($userOrder->getTotalPrice(), UserOrder::FIRST_TIME_BUYER_FLAG_AMOUNT) === 1){
                $userOrderFlagged->setFlagReason(UserOrder::FIRST_TIME_BUYER_AND_MORE_LIMIT);
            }
            elseif ($paymentMethod = $userOrder->getPaymentMethod()) {
                if ($paymentMethod->getPaymentMethodId() == PaymentMethod::PAYMENT_METHOD_PESOPAY) {
                    $userOrderFlagged->setFlagReason(UserOrder::FIRST_TIME_BUYER_AND_USING_CREDIT_CARD);
                }
            }
        }
        if ($userOrderFlagged->getFlagReason()) {
            $userOrder->setUserOrderFlagged($userOrderFlagged);
            $this->_em->persist($userOrderFlagged);
        }

        return $userOrder;
    }

    /**
     * Search available invoice numbers by a full text search
     *
     * @param string $invoiceNumberQuery
     * @param int $sellerId
     * @param int $buyerId
     * @return array
     */
    public function searchInvoiceNumber($invoiceNumberQuery, $sellerId = null, $buyerId = null)
    {
        $queryBuilder = $this->createQueryBuilder("o");
        $queryBuilder->where("match_against (o.invoiceNumber) against (:keyword BOOLEAN) > 0")
                     ->setParameter("keyword", $invoiceNumberQuery.'*');

        if($buyerId !== null){
            $queryBuilder->andWhere("o.buyer = :buyerId")
                         ->setParameter("buyerId", $buyerId);
        }

        if($sellerId !== null){
            $queryBuilder->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
                         ->innerJoin("YilinkerCoreBundle:Product", "p", "WITH", "op.product = p.productId AND p.user = :sellerId")
                         ->setParameter("sellerId", $sellerId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retrieve the seller transaction count per day.
     *
     * @param int $sellerId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param int[] $orderProductStatuses Checks historical order product status
     * @param int[] $excludeOrderProductStatuses Checks current order product status
     * @return array
     */
    public function getSellerTransactionCountPerDay($sellerId, DateTime $dateFrom = null, DateTime $dateTo = null, $orderProductStatuses = null, $excludeOrderProductStatuses = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('numberOfOrders', 'numberOfOrders');
        $rsm->addScalarResult('date', 'date');

        $historicalCondition = "";
        if($orderProductStatuses !== null){
            $historicalCondition .= " AND OrderProductHistory.order_product_status_id IN (:statuses)";
        }
        $currentCondition = "";
        if($excludeOrderProductStatuses !== null){
            $currentCondition .= " AND OrderProduct.order_product_status_id NOT IN (:excludeStatuses)";
        }

        $baseQuery = "
            SELECT
                UserOrder.order_id, UserOrder.date_added
            FROM
                UserOrder
            INNER JOIN OrderProduct
                ON OrderProduct.order_id = UserOrder.order_id ".$currentCondition."
            INNER JOIN Product
                ON Product.product_id = OrderProduct.product_id AND Product.user_id = :seller
            INNER JOIN OrderProductHistory
                ON OrderProductHistory.order_product_id = OrderProduct.order_product_id ".$historicalCondition."
            LEFT JOIN UserOrderFlagged ON UserOrder.user_order_flagged_id = UserOrderFlagged.user_order_flagged_id
            WHERE
                UserOrder.user_order_flagged_id IS NULL OR (UserOrder.user_order_flagged_id IS NOT NULL AND UserOrderFlagged.status = :approvedFlaggedStatus)
        ";

        if($dateFrom !== null){
            $baseQuery .= " AND UserOrder.date_added >= :dateFrom ";
        }
        if($dateTo !== null){
            $baseQuery .= " AND UserOrder.date_added <= :dateTo ";
        }

        $baseQuery .= " GROUP BY UserOrder.order_id";
        $fullQuery = "
            SELECT
                DATE(o.date_added) as `date`, COUNT(o.order_id) as `numberOfOrders`
            FROM (".$baseQuery.") o
            GROUP BY
                DATE(o.date_added)
        ";

        $query = $this->_em->createNativeQuery($fullQuery, $rsm)
                      ->setParameter('seller', $sellerId)
                      ->setParameter('approvedFlaggedStatus', UserOrderFlagged::APPROVE);

        if($orderProductStatuses !== null){
            $orderProductStatuses = is_array($orderProductStatuses) ? $orderProductStatuses : array($orderProductStatuses);
            $query->setParameter('statuses', $orderProductStatuses);
        }
        if($excludeOrderProductStatuses !== null){
            $excludeOrderProductStatuses = is_array($excludeOrderProductStatuses) ? $excludeOrderProductStatuses : array($excludeOrderProductStatuses);
            $query->setParameter('excludeStatuses', $excludeOrderProductStatuses);
        }
        if($dateFrom !== null){
            $query->setParameter('dateFrom', $dateFrom->format('Y-m-d') . ' 00:00:01');
        }
        if($dateTo !== null){
            $query->setParameter('dateTo', $dateTo->format('Y-m-d') . ' 23:59:59');
        }

        return $query->getResult();
    }

    public function getReviewsOfUser($userOrder, $userId)
    {
        $tbProductReview = $this->_em->getRepository('YilinkerCoreBundle:ProductReview');
        $tbUserFeedback = $this->_em->getRepository('YilinkerCoreBundle:UserFeedback');

        $reviews = array();
        $orderProductsBySellerId = $userOrder->getOrderProductsBySellerId();
        foreach ($orderProductsBySellerId as $sellerId => $orderProducts) {
            $orderProduct = null;
            foreach ($orderProducts as $orderProduct) {
                $orderProductId = $orderProduct->getOrderProductId();
                $productReview = $tbProductReview->findOneBy(array(
                    'reviewer'      => $userId,
                    'orderProduct'  => $orderProductId
                ));

                if ($productReview) {
                    $reviews['product'][$orderProductId] = $productReview;
                }
            }

            if ($orderProduct) {
                $store = $orderProduct->getSeller()->getStore();
                if ($store) {
                    $sellerReview = $tbUserFeedback->findOneBy(array(
                        'reviewer'  => $userId,
                        'reviewee'  => $store->getStoreId(),
                        'order'     => $userOrder->getOrderId()
                    ));

                    if ($sellerReview) {
                        $reviews['seller'][$store->getStoreId()] = $sellerReview;
                    }
                }
            }
        }

        return $reviews;
    }

    public function activityLoggable($userOrder)
    {
        $metadata = $this->_em->getClassMetadata(get_class($userOrder));
        $uow = $this->_em->getUnitOfWork();
        $uowEntityChanges = $uow->getEntityChangeSet($userOrder);
        $loggable = false;

        if(!$userOrder->getIsFlagged()){
            if (array_key_exists('orderStatus', $uowEntityChanges)) {
                $change = $uowEntityChanges['orderStatus'];
                $afterOrderStatus = array_pop($change);

                $statusId = $afterOrderStatus->getOrderStatusId();
                $checkoutStatus = array(
                    UserOrder::ORDER_STATUS_PAYMENT_CONFIRMED,
                    UserOrder::ORDER_STATUS_COD_WAITING_FOR_PAYMENT
                );

                $loggable = in_array($statusId, $checkoutStatus);
            }
            elseif (array_key_exists('invoiceNumber', $uowEntityChanges)) {
                $orderStatus = $userOrder->getOrderStatus();
                $statusId = $orderStatus->getOrderStatusId();

                $loggable = $statusId == UserOrder::ORDER_STATUS_COD_WAITING_FOR_PAYMENT;
            }
        }

        return $loggable;
    }

    public function isReviewable($userOrder, $sellerId = null)
    {
        $this
            ->qb()
            ->innerJoin('this.orderProducts', 'orderProducts')
            ->leftJoin(
                'orderProducts.orderProductHistories',
                'orderProductHistories',
                'WITH',
                'orderProductHistories.orderProductStatus = :orderProductStatus'
            )
            ->andWhere('this = :userOrder')
            ->andWhere('orderProductHistories.orderProductStatus IS NULL')
            ->setParameter('userOrder', $userOrder)
            ->setParameter('orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
        ;
        if ($sellerId) {
            $this
                ->andWhere('orderProducts.seller = :seller')
                ->setParameter('seller', $sellerId)
            ;
        }

        return !$this->getCount();
    }

    public function getOrdersWithVoucher($voucher)
    {
        if (!$voucher) {
            return array();
        }

        $this
            ->qb()
            ->innerJoin('this.orderVouchers', 'orderVouchers')
            ->innerJoin('orderVouchers.voucherCode', 'voucherCode')
            ->innerJoin('voucherCode.voucher', 'voucher')
            ->andWhere('voucher.voucherId = :voucherId')
            ->setParameter('voucherId', $voucher->getVoucherId())
        ;

        return $this->getResult();
    }

    /**
     * Convert group concatenated order_product_status_names and order_product_status_classes
     * to array
     *
     * @param mixed $results
     * @return mixed
     */
    private function tokenizeUniqueOrderProductStatuses($results)
    {
        $returnedResults = array();

        foreach($results as $key => $result){
            $orderProductStatusNames = explode(',',$result['order_product_status_names']);
            $orderProductStatusClasses = explode(',',$result['order_product_status_classes']);
            $orderProductStatuses = array();
            foreach($orderProductStatusNames as $statuskey => $statusName){
                $orderProductStatuses[$statusName] = array(
                    'name'  => $statusName,
                    'class' => strlen($orderProductStatusClasses[$statuskey]) ?
                               $orderProductStatusClasses[$statuskey] : OrderProductStatus::DEFAULT_CLASS,
                );
            }
            unset($result['order_product_status_names']);
            unset($result['order_product_status_classes']);
            $result['unique_order_product_statuses'] = array_values($orderProductStatuses);
            $returnedResults[$key] = $result;
        }

        return $returnedResults;
    }

}
