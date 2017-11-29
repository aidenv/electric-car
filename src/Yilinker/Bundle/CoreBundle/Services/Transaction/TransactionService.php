<?php

namespace Yilinker\Bundle\CoreBundle\Services\Transaction;

use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayoutOrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationHead;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\Payout;
use Yilinker\Bundle\CoreBundle\Entity\PayoutOrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\Currency;
use Yilinker\Bundle\CoreBundle\Services\Logistics\Yilinker\Express;
use Carbon\Carbon;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;

class TransactionService
{
    const PAYOUT_DAYS_ELAPSED = 7;

    const RECENT_TRANSACTION_AGE_IN_DAYS = 7;
    
    const ORDERS_PER_PAGE = 15;

    const TAX_PERCENTAGE = 12;

    const COMMISSION_MULTIPLIER_PERCENTAGE = 60;

    /** 
     * The entity manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Express logistics service
     *
     * @var Yilinker\Bundle\CoreBundle\Services\Logistics\Yilinker\Express
     */
    private $expressLogistics;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    public function __construct(EntityManager $em, Express $expressLogistics, AssetsHelper $assetsHelper)
    {
        $this->em = $em;
        $this->expressLogistics = $expressLogistics;
        $this->assetsHelper = $assetsHelper;
    }

    public static function getRefundOrderProductStatus()
    {
        return array(
            OrderProductStatus::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED,
            OrderProductStatus::STATUS_CANCELED_BY_ADMIN,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED
        );
    }

    /**
     * Get order product statuses for sold transactions
     *
     * @return int[]
     */
    public static function getOrderProductSalesStatuses()
    {
        return array(
            OrderProduct::STATUS_PAYMENT_CONFIRMED,
            OrderProduct::STATUS_READY_FOR_PICKUP,
            OrderProduct::STATUS_PRODUCT_ON_DELIVERY,
            OrderProduct::STATUS_ITEM_RECEIVED_BY_BUYER,
            OrderProduct::STATUS_SELLER_PAYMENT_RELEASED,
            OrderProduct::STATUS_SELLER_PAYOUT_UN_HELD,
            OrderProduct::STATUS_COD_TRANSACTION_CONFIRMED,
        );
    }

    /**
     * Get order statuses for sold transactions
     *
     * @return int[]
     */
    public function getOrderSalesStatuses()
    {
        return array(
            UserOrder::ORDER_STATUS_PAYMENT_CONFIRMED,
            UserOrder::ORDER_STATUS_COMPLETED,
            UserOrder::ORDER_STATUS_COD_WAITING_FOR_PAYMENT,
        );
    }

    public static function getOrderProductReturnOrderStatuses()
    {
        return array(
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED,
            OrderProductStatus::STATUS_CANCELED_BY_ADMIN,
            OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED,
            OrderProductStatus::STATUS_ITEM_REFUND_BOOKED_FOR_PICKUP,
            OrderProductStatus::STATUS_REFUNDED_ITEM_RECEIVED,
            OrderProductStatus::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED,
            OrderProductStatus::STATUS_ITEM_RETURN_BOOKED_FOR_PICKUP,
            OrderProductStatus::STATUS_RETURNED_ITEM_RECEIVED,
            OrderProductStatus::STATUS_REPLACEMENT_PRODUCT_INSPECTION_APPROVED,
            OrderProductStatus::STATUS_BUYER_REFUND_RELEASED,
        );
    }

    /**
     * Get status related to canceled transaction (before delivery)
     *
     * @return int[]
     */
    public function getOrderProductStatusesCancelled()
    {
        return array(
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED,
            OrderProductStatus::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED,
            OrderProductStatus::STATUS_BUYER_REFUND_RELEASED,
            OrderProductStatus::STATUS_CANCELED_BY_ADMIN,
        );
    }

    /**
     * Get admin cancellable statuses
     *
     * @return int[]
     */
    public function getOrderProductAdminCancellable()
    {
        return array(
            OrderProductStatus::PAYMENT_CONFIRMED ,
            OrderProductStatus::STATUS_READY_FOR_PICKUP,
            OrderProductStatus::STATUS_PRODUCT_ON_DELIVERY,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED,
            OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_THE_SPOT,
            OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_INSPECTION,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_INSPECTION ,
            OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED,
        );    
    }

    /**
     * Return shippable statuses
     *
     * @return int
     */
    public static function getShippableStatuses()
    {
        return array(
            OrderProduct::STATUS_PAYMENT_CONFIRMED,
            OrderProduct::STATUS_COD_TRANSACTION_CONFIRMED,
            OrderProduct::STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED
        );
    }

    /**
     * Get canceleable order products
     *
     * @return int[]
     */
    public function getCancellableOrderProductStatus()
    {
        return array(
            OrderProduct::STATUS_PAYMENT_CONFIRMED,
            OrderProduct::STATUS_COD_TRANSACTION_CONFIRMED,
        );
    }


    /**
     * Get order product statuses for refund
     *
     * @return int[]
     */
    public function getOrderProductStatusesForRefund()
    {
        return array(
            OrderProduct::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
            OrderProduct::STATUS_BUYER_CANCELLATION_BEFORE_DELIVERY_APPROVED,
            OrderProduct::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED
        );
    }

    /**
     * Get completed order product statuses
     *
     * @return int[]
     */
    public function getOrderProductStatusesCompleted()
    {
        return array(
            OrderProduct::STATUS_SELLER_PAYMENT_RELEASED,
            OrderProduct::STATUS_BUYER_REFUND_RELEASED,
        );
    }

    /**
     * Get on-going order statuses
     *
     * @param boolean $isBuyer
     * @return int[]
     */
    public function getOrderStatusesOnGoing($isBuyer = false)
    {
        $statuses = array(
            UserOrder::ORDER_STATUS_PAYMENT_CONFIRMED,
            UserOrder::ORDER_STATUS_DELIVERED,
            UserOrder::ORDER_STATUS_FOR_PICKUP,
            UserOrder::ORDER_STATUS_COD_WAITING_FOR_PAYMENT,
        );
        
        if($isBuyer){
            $statuses[] = UserOrder::ORDER_STATUS_WAITING_FOR_PAYMENT;
        }

        return $statuses;
    }

    /**
     * Get valid order statuses
     *
     * @param boolean $isBuyer
     * @return int[]
     */
    public function getOrderStatusesValid($isBuyer = false)
    {
        $ongoingOrderStatuses = $this->getOrderStatusesOnGoing($isBuyer);
        $ongoingOrderStatuses[] = UserOrder::ORDER_STATUS_COMPLETED;
        $ongoingOrderStatuses[] = UserOrder::ORDER_STATUS_FOR_REPLACEMENT;
        $ongoingOrderStatuses[] = UserOrder::ORDER_STATUS_FOR_CANCELLATION;
        $ongoingOrderStatuses[] = UserOrder::ORDER_STATUS_FOR_REFUND;

        return $ongoingOrderStatuses;
    }

    /**
     *  Retrieve the seller transactions
     *
     * @param int $sellerId
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int[] $orderStatuses
     * @param int $paymentMethod
     * @param int $page
     * @param string $invoiceString
     * @param string $productString
     * @param string $riderString
     * @param int[] $orderProductStatuses
     * @param string $sortBy
     * @param string $sortDirection
     * @return mixed
     */
    public function getSellerTransactions(
        $sellerId, 
        $dateFrom = null, 
        $dateTo = null, 
        $orderStatuses = null, 
        $paymentMethod = null, 
        $page = 1, 
        $perPage = null,
        $invoiceString = null,
        $productString = null,
        $riderString = null,
        $orderProductStatuses = null,
        $isScheduledForPickup = null,
        $sortBy = null,
        $sortDirection = null
    )
    {
        $perPage = null === $perPage ? self::ORDERS_PER_PAGE : (int) $perPage;
        $page--;
        $offset = $page * $perPage;
        $orders = $this->em->getRepository('YilinkerCoreBundle:UserOrder')
                       ->getTransactionOrderBySeller(
                           $sellerId, $dateFrom, $dateTo, $orderStatuses, 
                           $paymentMethod, $invoiceString, $productString, 
                           $riderString, $offset, $perPage, $orderProductStatuses, 
                           $isScheduledForPickup, $sortBy, $sortDirection
                       );

        $totalResults = $this->em->getRepository('YilinkerCoreBundle:UserOrder')
                             ->getNumberOfOrdersBy(
                                 $sellerId, null, $dateFrom, $dateTo, 
                                 $paymentMethod, $orderStatuses, $invoiceString, 
                                 $productString, $riderString, $orderProductStatuses, 
                                 null, $isScheduledForPickup, $sortBy
                             );

        return array(
            'orders' => $orders,
            'totalResultCount' => $totalResults,
        );
    }

    /**
     * Get total seller sales
     *
     * @param int $sellerId
     * @param DataTime $dateFrom
     * @param DataTo $dateTo
     * @return string
     */
    public function getSellerTotalSales($sellerId, DateTime $dateFrom = null, DateTime $dateTo = null)
    {
        $orderProductStatuses = $this->getOrderProductSalesStatuses();
        $totalSales = $this->em->getRepository('YilinkerCoreBundle:OrderProduct')
                           ->getSellerTransactionSales(
                               $sellerId, 
                               $orderProductStatuses,
                               $dateFrom,
                               $dateTo
                           );
        
        return $totalSales;
    }

    /**
     * Get seller total net sales
     *
     * @param int $sellerId
     * @param $dateFrom
     * @param $dateTo
     * @return string
     */
    public function getSellerTotalNetSales($sellerId, DateTime $dateFrom = null, DateTime $dateTo = null)
    {
        $orderProductStatuses = $this->getOrderProductSalesStatuses();
        $totalSales = $this->em->getRepository('YilinkerCoreBundle:OrderProduct')
                               ->getSellerTransactionNetSales(
                                   $sellerId,
                                   $orderProductStatuses,
                                   $dateFrom,
                                   $dateTo
                               );

        return $totalSales;
    }

    
    /**
     * Retrieve the buyer transactions
     *
     * @param int $buyerId
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param int[] $orderStatuses
     * @param int $paymentMethod
     * @param int $page
     * @param string $invoiceNumberString
     * @param string $productString
     * @param string $riderString
     * @param int[] $orderProductStatuses
     * @param boolean $forFeedback
     * @param string $sortBy
     * @param string $sortDirection
     * @return mixed
     */
    public function getBuyerTransactions(
        $buyerId, 
        $dateFrom = null, 
        $dateTo = null, 
        $orderStatuses = null, 
        $paymentMethod = null, 
        $page = 1, 
        $perPage = null,
        $invoiceNumberString = null,
        $productString = null,
        $riderString = null,
        $orderProductStatuses = null,
        $forFeedback = null,
        $isScheduledForPickup = null,
        $sortBy = null,
        $sortDirection = null
    )
    {
        $perPage = null === $perPage ? self::ORDERS_PER_PAGE : (int) $perPage;
        $page--;
        $offset = $page * $perPage;
        $orders = $this->em->getRepository('YilinkerCoreBundle:UserOrder')
                       ->getTransactionOrderByBuyer(
                           $buyerId, $dateFrom, $dateTo, $orderStatuses, 
                           $paymentMethod, $invoiceNumberString, $productString,
                           $riderString, $orderProductStatuses, $forFeedback, 
                           $isScheduledForPickup, $offset, $perPage, $sortBy, 
                           $sortDirection
                       );

        $includeFlagged = true;
        $totalResults = $this->em->getRepository('YilinkerCoreBundle:UserOrder')
                             ->getNumberOfOrdersBy(
                                 null, $buyerId, $dateFrom, $dateTo, 
                                 $paymentMethod, $orderStatuses, $invoiceNumberString,
                                 $productString, $riderString, $orderProductStatuses, 
                                 $forFeedback, $isScheduledForPickup, $sortBy, 
                                 $includeFlagged
                             );

        return array(
            'orders' => $orders,
            'totalResultCount' => $totalResults,
        );
    }

    /**
     * Retrieved number of confirmed transactions per day
     *
     * @param int $sellerId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @return mixed
     */
    public function getCountConfirmedSellerTransactionPerDay($sellerId, DateTime $dateFrom = null, DateTime $dateTo = null)
    {
        $cancelledStatuses = $this->getOrderProductStatusesCancelled();
        $confirmedStatuses = array(
            OrderProduct::STATUS_PAYMENT_CONFIRMED,
            OrderProduct::STATUS_COD_TRANSACTION_CONFIRMED,
        );
       
        $confirmedTransactionPerDay = $this->em->getRepository('YilinkerCoreBundle:UserOrder')
                                           ->getSellerTransactionCountPerDay( 
                                               $sellerId, 
                                               $dateFrom, 
                                               $dateTo, 
                                               $confirmedStatuses,
                                               $cancelledStatuses
                                           );
        $indexedResult = array();
        foreach($confirmedTransactionPerDay as $dailySoldTransactions){
            $indexedResult[$dailySoldTransactions['date']] = $dailySoldTransactions;
        }

        return $indexedResult;
    }

    /**
     * Retrieved  number of canceled transactions per day
     *
     * @param int $sellerId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @return mixed
     */
    public function getCountCancelledSellerTransactionPerDay($sellerId, DateTime $dateFrom = null, DateTime $dateTo = null)
    {
        /**
         * Order Product Statuses that have already been approved for cancellation
         */
        $canceledStatuses = array(
            OrderProduct::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
            OrderProduct::STATUS_BUYER_CANCELLATION_BEFORE_DELIVERY_APPROVED,
            OrderProduct::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED,
            OrderProduct::STATUS_CANCELED_BY_ADMIN,
            OrderProduct::STATUS_BUYER_REFUND_RELEASED,
        );


        $canceledTransactionPerDay = $this->em->getRepository('YilinkerCoreBundle:UserOrder')
                                          ->getSellerTransactionCountPerDay( 
                                              $sellerId, 
                                              $dateFrom, 
                                              $dateTo, 
                                              $canceledStatuses
                                          );
        $indexedResult = array();
        foreach($canceledTransactionPerDay as $dailyCancellation){
            $indexedResult[$dailyCancellation['date']] = $dailyCancellation;
        }

        return $indexedResult;
    }

    /**
     * Update the status of an OrderProduct
     *
     * @param OrderProduct[] $orderProductEntities
     * @param int $orderProductStatus
     * @return bool
     */
    public function changeOrderProductStatus ($orderProductEntities = array(), $orderProductStatus)
    {
        $this->em->getConnection()->beginTransaction();
        $orderProductStatusReference = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', $orderProductStatus);

        if (!is_array($orderProductEntities)) {
            $orderProductEntities = array($orderProductEntities);
        }
        
        try {
            foreach ($orderProductEntities as $orderProductEntity) {
                $orderProductEntity->setOrderProductStatus($orderProductStatusReference);
            }
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();

            return false;
        }

        return true;
    }

    /**
     * Admin cancels an orderproduct
     *
     * @param Yiliker\Bundle\CoreBundle\Entity\OrderProduct[] $orderProducts 
     * @param int $orderProductCancellationReasonEntity
     * @param string $remarks
     * @param Yiliker\Bundle\CoreBundle\Entity\User $user
     * @param Yiliker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return bool
     */
    public function cancellationTransactionByAdmin (
        $orderProducts,
        $orderProductCancellationReasonEntity,
        $remarks = "", 
        $adminUser
    ){

        $orderProductStatus = $this->em->getReference(
            'YilinkerCoreBundle:OrderProductStatus', 
            OrderProductStatus::STATUS_CANCELED_BY_ADMIN
        );

        $cancelledOrderProducts = array();
        $this->em->getConnection()->beginTransaction();
        try {
            $orderProductCancellationHead = $this->addOrderProductCancellationHead($orderProductCancellationReasonEntity, null, $adminUser, false);

            foreach($orderProducts as $orderProduct) {

                if ($orderProduct->getOrderProductStatus() === $orderProductStatus) {
                    continue;
                }

                $orderProduct->setOrderProductStatus($orderProductStatus);
                $this->addOrderProductCancellationDetail(
                    $orderProductCancellationHead, 
                    $orderProduct, 
                    $adminUser, 
                    OrderProductCancellationDetail::DETAIL_STATUS_APPROVED,
                    $remarks
                );
                $cancelledOrderProducts[] = $orderProduct;
            }

            $this->em->flush();
            $this->em->getConnection()->commit();
        }
        catch(\Exception $e){
            $this->em->getConnection()->rollback();

            return false;
        }

        /**
         * If cancellation is done by admin, automatically request express delivery reschedule
         */
        $this->expressLogistics->cancelOrder($cancelledOrderProducts);

        return true;
    }


    /**
     * User requests cancellation of orderproduct
     *
     * @param Yiliker\Bundle\CoreBundle\Entity\OrderProduct[] $orderProducts 
     * @param Yiliker\Bundle\CoreBundle\Entity\OrderProductCancellationReason $cancellationReason
     * @param string $remarks
     * @param Yiliker\Bundle\CoreBundle\Entity\User $user
     * @return bool
     */
    public function cancellationRequestTransactionByUser(
        $orderProducts,
        $cancellationReason, 
        $remarks = "",
        $user
    ){
        if($cancellationReason === null){
            return false;
        }

        if($user->getUserType() === User::USER_TYPE_BUYER){
            $status = OrderProduct::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY;
            $cancellationDetailStatus = OrderProductCancellationDetail::DETAIL_STATUS_OPEN;
            $cancellationHeadIsOpened = true;
        }
        else{
            $status = OrderProduct::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY;
            $cancellationDetailStatus = OrderProductCancellationDetail::DETAIL_STATUS_APPROVED;
            $cancellationHeadIsOpened = false;
        }

        $orderProductStatus = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', $status);
        $this->em->getConnection()->beginTransaction();
        $cancelledOrderProducts = array();
        try{     
            $orderProductCancellationHead = $this->addOrderProductCancellationHead(
                $cancellationReason, $user, null, $cancellationHeadIsOpened, $remarks
            );   
            $cancelleableOrderProductStatuses = $this->getCancellableOrderProductStatus();

            foreach($orderProducts as $orderProduct) {
                if(!in_array($orderProduct->getOrderProductStatus()->getOrderProductStatusId(),$cancelleableOrderProductStatuses)){
                    continue;
                }

                $orderProduct->setOrderProductStatus($orderProductStatus);
                $this->addOrderProductCancellationDetail($orderProductCancellationHead, $orderProduct, null, $cancellationDetailStatus);
                $cancelledOrderProducts[] = $orderProduct;
            }

            if(count($cancelledOrderProducts) === 0){
                /**
                 * throw an exception to rollback
                 */ 
                throw new \Exception();
            }
            $this->em->flush();
            $this->em->getConnection()->commit();
        }
        catch(\Exception $e){
            $this->em->getConnection()->rollback();

            return false;
        }

        /**
         * If cancellation is done by seller, automatically request express delivery reschedule
         */
        if($status === OrderProduct::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY){
            $this->expressLogistics->cancelOrder($cancelledOrderProducts);
        }

        return true;
    }

    /**
     * Add OrderProductCancellationHead
     *
     * @param OrderProductCancellationReason $orderProductCancellationReason
     * @param User $user
     * @param int $isOpen
     * @param string $remarks
     * @return OrderProductCancellationHead
     */
    public function addOrderProductCancellationHead (
        OrderProductCancellationReason $orderProductCancellationReason,
        User $user = null,
        AdminUser $adminUser = null,
        $isOpen = true,
        $remarks = ""
    )
    {
        $orderProductCancellationHead = new OrderProductCancellationHead();
        $orderProductCancellationHead->setOrderProductCancellationReason($orderProductCancellationReason);
        $orderProductCancellationHead->setUser($user);
        $orderProductCancellationHead->setAdmin($adminUser);
        $orderProductCancellationHead->setIsOpened($isOpen);
        $orderProductCancellationHead->setRemarks($remarks);
        $orderProductCancellationHead->setDateAdded(Carbon::now());

        $this->em->persist($orderProductCancellationHead);
        $this->em->flush();

        return $orderProductCancellationHead;
    }

    /**
     * Add OrderProductCancellationDetail
     *
     * @param OrderProductCancellationHead $orderProductCancellationHead
     * @param OrderProduct $orderProduct
     * @param AdminUser $adminUser
     * @param $status
     * @param string $remarks
     * @return OrderProductCancellationDetail
     */
    public function addOrderProductCancellationDetail (
        OrderProductCancellationHead $orderProductCancellationHead,
        OrderProduct $orderProduct,
        AdminUser $adminUser = null,
        $status = OrderProductCancellationDetail::DETAIL_STATUS_OPEN,
        $remarks = ""
    )
    {
        $orderProductCancellationDetail = new OrderProductCancellationDetail();
        $orderProductCancellationDetail->setOrderProduct($orderProduct);
        $orderProductCancellationDetail->setOrderProductCancellationHead($orderProductCancellationHead);
        $orderProductCancellationDetail->setRemarks($remarks);
        $orderProductCancellationDetail->setAdminUser($adminUser);
        $orderProductCancellationDetail->setStatus($status);

        $this->em->persist($orderProductCancellationDetail);
        $this->em->flush();

        return $orderProductCancellationDetail;
    }

    /**
     * Approve Or Deny Cancelled Transaction and add OrderProduct History
     *
     * @param array $orderProductEntities
     * @param $remarks
     * @param $isApprove
     * @param $adminUser
     * @return bool
     */
    public function approveOrDenyCancelledTransaction (
        $orderProductEntities = array(),
        $remarks,
        $isApprove,
        $adminUser
    )
    {
        $status = OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED;
        $detailStatus = OrderProductCancellationDetail::DETAIL_STATUS_APPROVED;

        if ($isApprove === false) {
            $status = OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED;
            $detailStatus = OrderProductCancellationDetail::DETAIL_STATUS_DENIED;
        }

        $orderProductStatusReference = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', $status);
        foreach($orderProductEntities as $orderProductEntity) {
            $orderProductCancellationHead = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationHead')
                                                     ->getOrderProductCancellationHeadByOrderProduct($orderProductEntity);
            $orderProductEntity->setOrderProductStatus($orderProductStatusReference);
            $this->addOrderProductCancellationDetail($orderProductCancellationHead, $orderProductEntity, $adminUser, $detailStatus, $remarks);
            $this->updateIsOpenIfDone($orderProductCancellationHead);
        }

        /**
         * If cancellation is approved, request express delivery reschedule
         */
        if((int) $status === OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED){
            $this->expressLogistics->cancelOrder($orderProductEntities);
        }
        
        return true;
    }

    /**
     * Create new entry in OrderProductHistory
     * @param OrderProductStatus $orderProductStatus
     * @param OrderProduct $orderProduct
     */
    public function addOrderProductHistory (OrderProductStatus $orderProductStatus, OrderProduct $orderProduct)
    {
        $orderProductHistory = new OrderProductHistory();
        $orderProductHistory->setOrderProductStatus($orderProductStatus);
        $orderProductHistory->setOrderProduct($orderProduct);
        $orderProductHistory->setDateAdded(Carbon::now());

        $this->em->persist($orderProductHistory);
        $this->em->flush();
    }

    /**
     * @param OrderProductCancellationHead $orderProductCancellationHead
     */
    public function updateIsOpenIfDone (OrderProductCancellationHead $orderProductCancellationHead)
    {
        $openOrderProductCancellationDetails = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationDetail')
                                                        ->findBy ( array(
                                                            'orderProductCancellationHead' => $orderProductCancellationHead->getOrderProductCancellationHeadId(),
                                                            'status'                       => OrderProductCancellationDetail::DETAIL_STATUS_OPEN
                                                        ));
        $approvedOrDeniedOrderProductCancellationDetails = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationDetail')
                                                                    ->findByOrderProductCancellationHead($orderProductCancellationHead->getOrderProductCancellationHeadId());

        if (sizeof($approvedOrDeniedOrderProductCancellationDetails) - sizeof($openOrderProductCancellationDetails) === sizeof($openOrderProductCancellationDetails)) {
            $orderProductCancellationHead->setIsOpened(0);
            $this->em->persist($orderProductCancellationHead);
            $this->em->flush();
        }

    }

    /**
     * @param $orderId
     * @return bool
     */
    public function getRemarksByOrder ($orderId)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder->select(array (
                            "o.orderId",
                            "op.orderProductId",
                            "opcHead.orderProductCancellationHeadId",
                            "opcDetail.orderProductCancellationDetailId",
                            "op.productName",
                            "opcDetail.remarks AS remarkByCsr",
                            "opcHead.remarks AS remarkBySeller",
                            "COALESCE(au.adminUserId, 0) AS isAdmin",
                            "opcHead.isOpened",
                            "opcHead.dateAdded",
                            "opcReason.reason",
                            "CONCAT(au.firstName, ' ', au.lastName) AS csr",
                            "CONCAT(u.firstName, ' ', u.lastName) AS seller"
                        ) )
            ->from("YilinkerCoreBundle:UserOrder", "o")
            ->join("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
            ->join("YilinkerCoreBundle:OrderProductCancellationDetail", "opcDetail", "WITH", "opcDetail.orderProduct = op.orderProductId")
            ->join("YilinkerCoreBundle:OrderProductCancellationHead", "opcHead", "WITH", "opcHead.orderProductCancellationHeadId = opcDetail.orderProductCancellationHead")
            ->join("YilinkerCoreBundle:OrderProductCancellationReason", "opcReason", "WITH", "opcReason.orderProductCancellationReasonId = opcHead.orderProductCancellationReason")
            ->leftJoin("YilinkerCoreBundle:AdminUser", "au", "WITH", "au.adminUserId = opcDetail.adminUser")
            ->leftJoin("YilinkerCoreBundle:User", "u", "WITH", "u.userId = opcHead.user")
            ->where("o.orderId = :orderId")
            ->setParameter('orderId', $orderId)
            ->groupBy("opcHead.orderProductCancellationHeadId, opcDetail.orderProductCancellationDetailId")
            ->orderBy("opcHead.dateAdded", "DESC");

        $query = $queryBuilder->getquery();
        $unGroupedRemarks = $query->getScalarResult();
        $remarksGroupByHead = array();

        if ($unGroupedRemarks) {

            foreach ($unGroupedRemarks as $unGroupedRemark) {
                $headId = $unGroupedRemark['orderProductCancellationHeadId'];
                $remarks = $unGroupedRemark['remarkBySeller'];
                $user = $unGroupedRemark['seller'];
                $userKey = $unGroupedRemark['isAdmin'] ? 'admin' : 'seller';
                $orderProductId = $unGroupedRemark['orderProductId'];

                if ($unGroupedRemark['isAdmin']) {
                    $remarks = $unGroupedRemark['remarkByCsr'];
                    $user = $unGroupedRemark['csr'];
                }

                $remarksGroupByHead[$headId]['details'][$userKey] = array (
                    'remarks' => $remarks,
                    'user' => $user,
                    'isAdmin' => $unGroupedRemark['isAdmin'],
                    'dateAdded' => $unGroupedRemark['dateAdded']
                );
                $remarksGroupByHead[$headId]['isOpen'] = $unGroupedRemark['isOpened'];
                $remarksGroupByHead[$headId]['reason'] = $unGroupedRemark['reason'];
                $remarksGroupByHead[$headId]['orderId'] = $unGroupedRemark['orderId'];
                $remarksGroupByHead[$headId]['products'][$orderProductId] = $unGroupedRemark['productName'];
            }

        }

        return $remarksGroupByHead;
    }

    /**
     * Retrieve the transaction shipment data
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $shipper
     * @param string $invoiceNumber
     * @retunr mixed
     */
    public function getTransactionShipmentData($shipper, $invoiceNumber)
    {
        $orderProducts = $this->em->getRepository('YilinkerCoreBundle:OrderProduct')
                              ->getSellerOrderProductsByInvoice(
                                  $shipper->getUserId(), 
                                  $invoiceNumber
                              );
        if(count($orderProducts) > 0){
            return $this->formatOrderProductShipmentDetails($orderProducts, $shipper);
        }
    
        return false;
    }

    /**
     * Format order product shipment details
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\Orderproduct[] $orderProducts
     * @return mixed
     */
    public function formatOrderProductShipmentDetails($orderProducts, $shipper)
    {
        $order = reset($orderProducts)->getOrder();
        
        $consigneeLocationTree = !$order->getConsigneeLocation() ? array() :
                                 $order->getConsigneeLocation()
                                       ->getLocalizedLocationTree(true);
        $consigneeDetails = array(
            'firstName' => $order->getConsigneeFirstName(),
            'lastName' => $order->getConsigneeLastName(),
        ); 
        $consigneeDetails = array_merge($consigneeDetails, $consigneeLocationTree);
        
        $shipperLocationTree = !$shipper->getDefaultAddress() ? array() :
                               $shipper->getDefaultAddress()
                                       ->getLocation()
                                       ->getLocalizedLocationTree(true);
        $shipperDetails = array(
            'firstName' => $shipper->getFirstName(),
            'lastName' => $shipper->getLastName(),
        ); 
        $shipperDetails = array_merge($shipperDetails, $shipperLocationTree);
        
        $products = array();
        foreach($orderProducts as $orderProduct){
            $products[] = array(
                'width' =>  $orderProduct->getWidth(),
                'height' => $orderProduct->getHeight(),
                'length' => $orderProduct->getLength(),
                'weight' => $orderProduct->getWeight(),
                'value' => $orderProduct->getQuantifiedUnitPrice(),
                'name' => $orderProduct->getProductName(),
            );
        }
        
        $shipmentData = array(
            'consignee' => $consigneeDetails,
            'shipper' => $shipperDetails,
            'products' => $products,
        );

        return $shipmentData;
    }

    /**
     * Change OrderProductStatus to Buyer Refund Released
     *
     * @param array $orderProductEntities
     * @param Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @param string $currencyCode
     * @return bool
     */
    public function updateStatusToPaymentReleased ($orderProductEntities = array(), $adminUser, $currencyCode = Currency::CURRENCY_PH_PESO)
    {
        $orderProductStatusReference = $this->em->getReference(
            'YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_SELLER_PAYMENT_RELEASED
        );
        $storeRepository = $this->em->getRepository('YilinkerCoreBundle:Store');

        $sellers = array();
        foreach ($orderProductEntities as $orderProductEntity) {
            $orderProductEntity->setOrderProductStatus($orderProductStatusReference);

            /**
             * Build payout data
             */
            $seller = $orderProductEntity->getSeller();
            $sellerId = $seller->getUserId();
            $storeEntity = $storeRepository->findOneByUser($seller);
            $amount = $orderProductEntity->getNet();
            $sellerType = Store::STORE_TYPE_MERCHANT;

            if ( (int) $storeEntity->getStoreType() === Store::STORE_TYPE_RESELLER) {
                $sellerType = Store::STORE_TYPE_RESELLER;
                $amount = $orderProductEntity->getCommission();
            }

            if (!isset($sellers[$sellerId])) {
                $sellers[$sellerId] = array (
                    'seller'        => $seller,
                    'amount'        => "0.0000",
                    'sellerType'    => $sellerType,
                    'orderProducts' => array(),
                );
            }

            $sellers[$sellerId]['amount'] = bcadd($sellers[$sellerId]['amount'], $amount, 4);
            $sellers[$sellerId]['orderProducts'][] = $orderProductEntity;
        }

        $currency = $this->em->getRepository('YilinkerCoreBundle:Currency')
                             ->findOneBy(array (
                                 'code' => $currencyCode,
                             ));

        /**
         * Create payout entities
         */
        $dateTime = new \DateTime('now');
        $payouts = array();
        foreach ($sellers as $seller) {
            $payout = new Payout();
            $payout->setUser($seller['seller']);
            $payout->setAdminUser($adminUser);
            $payout->setPayoutType(Payout::PAYOUT_TYPE_SELLER_PAYOUT);
            $payout->setDateCreated($dateTime);
            $payout->setDateModified($dateTime);
            $payout->setAmount($seller['amount']);
            $payout->setStatus(Payout::PAYOUT_STATUS_COMPLETE);
            $payout->setCurrency($currency);
            $payouts[] = $payout;
            $this->em->persist($payout);

            foreach ($seller['orderProducts'] as $orderProduct) {
                $amount = $orderProduct->getNet();

                if ( (int) $seller['sellerType'] === Store::STORE_TYPE_RESELLER) {
                    $amount = (float) $orderProduct->getCommission();
                }

                $payoutOrderProduct = new PayoutOrderProduct();
                $payoutOrderProduct->setOrderProduct($orderProduct);
                $payoutOrderProduct->setDateCreated($dateTime);
                $payoutOrderProduct->setDateModified($dateTime);
                $payoutOrderProduct->setPayout($payout);
                $payoutOrderProduct->setAmount($amount);
                $this->em->persist($payoutOrderProduct);
            }

        }

        $this->em->flush();

        return array (
            'isSuccessful' => count($payouts) > 0,
            'data' => $payouts,
        ); 
    }

    /**
     * Change OrderProductStatus to Buyer Refund Released
     *
     * @param array $orderProductEntities
     * @param Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @param string $currencyCode
     * @return bool
     */
    public function manufacturerPayout ($orderProductEntities = array(), $adminUser, $currencyCode = Currency::CURRENCY_PH_PESO)
    {
        $orderProductStatusReference = $this->em->getReference(
            'YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_SELLER_PAYMENT_RELEASED
        );
        $storeRepository = $this->em->getRepository('YilinkerCoreBundle:Store');

        $sellers = array();
        foreach ($orderProductEntities as $orderProductEntity) {
            $orderProductEntity->setOrderProductStatus($orderProductStatusReference);

            /**
             * Build payout data
             */
            $seller = $orderProductEntity->getSeller();
            $sellerId = $seller->getUserId();
            $storeEntity = $storeRepository->findOneByUser($seller);
            $amount = $orderProductEntity->getNet();
            $sellerType = Store::STORE_TYPE_MERCHANT;

            if ( (int) $storeEntity->getStoreType() === Store::STORE_TYPE_RESELLER) {
                $sellerType = Store::STORE_TYPE_RESELLER;
                $amount = $orderProductEntity->getCommission();
            }

            if (!isset($sellers[$sellerId])) {
                $sellers[$sellerId] = array (
                    'seller'        => $seller,
                    'amount'        => "0.0000",
                    'sellerType'    => $sellerType,
                    'orderProducts' => array(),
                );
            }

            $sellers[$sellerId]['amount'] = bcadd($sellers[$sellerId]['amount'], $amount, 4);
            $sellers[$sellerId]['orderProducts'][] = $orderProductEntity;
        }

        $currency = $this->em->getRepository('YilinkerCoreBundle:Currency')
                             ->findOneBy(array (
                                 'code' => $currencyCode,
                             ));

        /**
         * Create payout entities
         */
        $dateTime = new \DateTime('now');
        $payouts = array();
        foreach ($sellers as $seller) {
            $payout = new ManufacturerPayout();
            $payout->setUser($seller['seller']);
            $payout->setAdminUser($adminUser);
            $payout->setPayoutType(Payout::PAYOUT_TYPE_SELLER_PAYOUT);
            $payout->setDateCreated($dateTime);
            $payout->setDateModified($dateTime);
            $payout->setAmount($seller['amount']);
            $payout->setStatus(Payout::PAYOUT_STATUS_COMPLETE);
            $payout->setCurrency($currency);
            $payouts[] = $payout;
            $this->em->persist($payout);

            foreach ($seller['orderProducts'] as $orderProduct) {
                $amount = $orderProduct->getNet();
                $manufacturer = $orderProduct->getManufacturerProductUnit()
                                             ->getManufacturerProduct()
                                             ->getManufacturer();

                if ( (int) $seller['sellerType'] === Store::STORE_TYPE_RESELLER) {
                    $amount = (float) $orderProduct->getCommission();
                }

                $payoutOrderProduct = new ManufacturerPayoutOrderProduct();
                $payoutOrderProduct->setOrderProduct($orderProduct);
                $payoutOrderProduct->setDateCreated($dateTime);
                $payoutOrderProduct->setDateModified($dateTime);
                $payoutOrderProduct->setManufacturer($manufacturer);
                $payoutOrderProduct->setManufacturerPayout($payout);
                $payoutOrderProduct->setAmount($amount);
                $this->em->persist($payoutOrderProduct);
            }

        }

        $this->em->flush();

        return array (
            'isSuccessful' => count($payouts) > 0,
            'data' => $payouts,
        );
    }

    /**
     * Change OrderProductStatus to Buyer Refund Released
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\OrderProducts[] $orderProductEntities
     * @param Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @param string $currencyCode
     * @return bool
     */
    public function updateStatusToRefundReleased ($orderProductEntities, $adminUser, $currencyCode = Currency::CURRENCY_PH_PESO, $disputeId = 0)
    {
        $orderProductStatusReference = $this->em->getReference (
            'YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_BUYER_REFUND_RELEASED
        );

        $buyers = array();
        foreach ($orderProductEntities as $orderProductEntity) {
            $orderProductEntity->setOrderProductStatus($orderProductStatusReference);

            /**
             * Build payout data
             */
            $buyer = $orderProductEntity->getOrder()->getBuyer();
            $buyerId = $buyer->getUserId();

            if (!isset($buyers[$buyerId])) {
                $buyers[$buyerId] = array (
                    'buyer'         => $buyer,
                    'amount'        => "0.0000",
                    'orderProducts' => array(),
                );
            }

            $buyers[$buyerId]['amount'] = bcadd($buyers[$buyerId]['amount'], $orderProductEntity->getNet(), 4);
            $buyers[$buyerId]['orderProducts'][] = $orderProductEntity;
        }

        $currency = $this->em->getRepository('YilinkerCoreBundle:Currency')
                             ->findOneBy(array(
                                 'code' => $currencyCode,
                             ));

        $dispute = $disputeId > 0 ? $this->em->getReference('YilinkerCoreBundle:Dispute', $disputeId): null;
        /**
         * Create payout entities
         */
        $dateTime = new \DateTime('now');
        $payouts = array();

        foreach ($buyers as $buyer) {
            $payout = new Payout();
            $payout->setUser($buyer['buyer']);
            $payout->setAdminUser($adminUser);
            $payout->setPayoutType(Payout::PAYOUT_TYPE_BUYER_REFUND);
            $payout->setDateCreated($dateTime);
            $payout->setDateModified($dateTime);
            $payout->setAmount($buyer['amount']);
            $payout->setStatus(Payout::PAYOUT_STATUS_COMPLETE);
            $payout->setCurrency($currency);
            $payout->setDispute($dispute);
            $payouts[] = $payout;
            $this->em->persist($payout);

            foreach ($buyer['orderProducts'] as $orderProduct) {
                $payoutOrderProduct = new PayoutOrderProduct();
                $payoutOrderProduct->setOrderProduct($orderProduct);
                $payoutOrderProduct->setDateCreated($dateTime);
                $payoutOrderProduct->setDateModified($dateTime);
                $payoutOrderProduct->setAmount($orderProduct->getNet())
                                   ->setPayout($payout);
                $this->em->persist($payoutOrderProduct);
            }

        }

        $this->em->flush();

        return array (
            'isSuccessful' => count($payouts) > 0,
            'data' => $payouts,
        ); 
    }

    /**
     * Calculates the affiliate commission
     *
     * @param $itemCost
     * @param $sellingPrice
     * @return string
     */
    public function calculateCommission($itemCost, $sellingPrice)
    {
        $netPrice = bcsub($sellingPrice, $itemCost, 4);
        $inputTax = bcsub($itemCost,bcdiv($itemCost, bcadd("1.0000", bcdiv(self::TAX_PERCENTAGE, "100.00", 4),4)), 4);
        $outputTax = bcsub($sellingPrice, bcdiv($sellingPrice, bcadd("1.0000", bcdiv(self::TAX_PERCENTAGE, "100.00", 4),4)), 4);
        $netTax = bcsub($outputTax, $inputTax, 4);

        $commission = bcmul(bcsub($netPrice, $netTax, 4), bcdiv(self::COMMISSION_MULTIPLIER_PERCENTAGE, "100.00", 4), 4);

        return $commission;
    }

    /**
     * Get Transaction Order Product
     *
     * @param null $orderId
     * @param null $orderProductId
     * @param null $sellerId
     * @param null $buyerId
     * @param null $orderProductStatuses
     * @return mixed
     */
    public function getTransactionOrderProducts (
        $orderId = null,
        $orderProductId = null,
        $sellerId = null,
        $buyerId = null,
        $orderProductStatuses = null )
    {
        $userOrderRepository = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $orderProducts = $userOrderRepository->getTransactionOrderProducts (
            $orderId,
            $orderProductId,
            $sellerId,
            $buyerId,
            $orderProductStatuses
        );

        foreach ($orderProducts as &$orderProduct) {

            if ( (int) $orderProduct['storeType'] === Store::STORE_TYPE_RESELLER) {
                $commission = $orderProduct['commission'];
                $orderProduct['totalPrice'] = $commission;
            }
        }

        return $orderProducts;
    }

    /**
     * Get Buyer Refund Order Product
     *
     * @param null $orderId
     * @param null $orderProductId
     * @param null $sellerId
     * @param null $buyerId
     * @param null $orderProductStatuses
     * @return mixed
     */
    public function getBuyerRefundOrderProducts (
        $orderId = null,
        $orderProductId = null,
        $sellerId = null,
        $buyerId = null,
        $orderProductStatuses = null )
    {
        $userOrderRepository = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $orderProducts = $userOrderRepository->getBuyerRefundOrderProducts (
            $orderId,
            $orderProductId,
            $sellerId,
            $buyerId,
            $orderProductStatuses
        );

        foreach ($orderProducts as &$orderProduct) {

            if ( (int) $orderProduct['storeType'] === Store::STORE_TYPE_RESELLER) {
                $orderProduct['totalPrice'] = $orderProduct["commission"];
            }
        }

        return $orderProducts;
    }

    /**
     * Get Payout History
     *
     * @param $keyword
     * @param $dateFrom
     * @param $dateTo
     * @param $sellerTypeId
     * @param $limit
     * @param $offset
     * @return array
     */
    public function getPayoutHistory ($keyword, $dateFrom, $dateTo, $sellerTypeId, $limit, $offset)
    {
        $payoutHistory = array (
            "payouts" => array (),
            "payoutCount" => 0
        );

        $payoutRepository = $this->em->getRepository("YilinkerCoreBundle:Payout");

        $payoutData = $payoutRepository->getPayouts($keyword, $dateFrom, $dateTo, $sellerTypeId, $limit, $offset);

        $payoutHistory["payoutCount"] = $payoutData["payoutCount"];

        foreach ($payoutData["payouts"] as $payout) {
            $user = $payout->getUser();
            $orderProducts = array();
            $documents = array();

            $payoutOrderProducts = $payout->getPayoutOrderProducts();
            $payoutDocuments = $payout->getPayoutDocuments();

            $currency = $payout->getCurrency()->getSymbol();

            foreach ($payoutOrderProducts as $payoutOrderProduct) {
                $orderProduct = $payoutOrderProduct->getOrderProduct();

                array_push($orderProducts, array (
                    "orderProductId" => $orderProduct->getOrderProductId(),
                    "name" => $orderProduct->getProduct()->getName(),
                    "amount" => $currency . " " . number_format($payoutOrderProduct->getAmount(), 2),
                    "dateCreated" => $payoutOrderProduct->getDateCreated()->format("m/d/Y")
                ));
            }

            foreach ($payoutDocuments as $payoutDocument) {
                array_push ($documents, array (
                    "path" => $this->assetsHelper->getUrl($payoutDocument->getFilepath(), "payout")
                ));
            }

            array_push ($payoutHistory["payouts"], array (
                "referenceNumber" => $payout->getReferenceNumber(),
                "storeName" => $user->getStore()->getStoreName(),
                "email" => $user->getEmail(),
                "supportCsr" => $payout->getAdminUser()->getFullName(),
                "dateCreated" => $payout->getDateCreated()->format("m/d/Y"),
                "dateModified" => $payout->getDateModified()->format("m/d/Y"),
                "currency" => $currency,
                "amount" => $currency." ".number_format($payout->getAmount(), 2),
                "status" => $payout->getStatus() == Payout::PAYOUT_STATUS_INCOMPLETE? "Incomplete" : "Completed",
                "orderProducts" => $orderProducts,
                "documents" => $documents
            ));

        }

        return $payoutHistory;
    }

    /**
     * Get Manufacturer Payout History
     *
     * @param $keyword
     * @param $dateFrom
     * @param $dateTo
     * @param $sellerTypeId
     * @param $limit
     * @param $offset
     * @return array
     */
    public function getManufacturerPayoutHistory ($keyword, $dateFrom, $dateTo, $sellerTypeId, $limit, $offset)
    {
        $payoutHistory = array (
            "manufacturers" => array (),
            "manufacturerCount" => 0
        );

        $payoutRepository = $this->em->getRepository("YilinkerCoreBundle:ManufacturerPayout");
        $manufacturerPayoutOrderProductRepository = $this->em->getRepository("YilinkerCoreBundle:ManufacturerPayoutOrderProduct");
        $manufacturerPayoutDocumentsRepository = $this->em->getRepository("YilinkerCoreBundle:ManufacturerPayoutDocument");

        $payoutData = $payoutRepository->getManufacturerPayouts($keyword, $dateFrom, $dateTo, $sellerTypeId, $limit, $offset);

        $payoutHistory["manufacturerCount"] = $payoutData["manufacturerCount"];
        foreach ($payoutData["manufacturers"] as $payout) {
            $user = $payout->getUser();
            $orderProducts = array();
            $documents = array();
            $totalAmount = 0;
            $manufacturerName = '';
            $manufacturerContactNumber = '';

            $payoutOrderProducts = $manufacturerPayoutOrderProductRepository->findByManufacturerPayout($payout);
            $payoutDocuments = $manufacturerPayoutDocumentsRepository->findByManufacturerPayout($payout);

            $currency = $payout->getCurrency()->getSymbol();

            foreach ($payoutOrderProducts as $payoutOrderProduct) {
                $orderProduct = $payoutOrderProduct->getOrderProduct();

                array_push($orderProducts, array (
                    "orderProductId" => $orderProduct->getOrderProductId(),
                    "name" => $orderProduct->getProduct()->getName(),
                    "amount" => $currency . " " . number_format($payoutOrderProduct->getAmount(), 2),
                    "dateCreated" => $payoutOrderProduct->getDateCreated()->format("m/d/Y")
                ));
                $totalAmount += $payoutOrderProduct->getAmount();
                $manufacturerName = $payoutOrderProduct->getManufacturer()->getName();
                $manufacturerContactNumber = $payoutOrderProduct->getManufacturer()->getContactNumber();
            }

            foreach ($payoutDocuments as $payoutDocument) {
                array_push ($documents, array (
                    "path" => $this->assetsHelper->getUrl($payoutDocument->getFilepath(), "payout")
                ));
            }

            array_push ($payoutHistory["manufacturers"], array (
                "manufacturer" => $manufacturerName,
                "contactNumber" => $manufacturerContactNumber,
                "referenceNumber" => $payout->getReferenceNumber(),
                "storeName" => $user->getStore()->getStoreName(),
                "email" => $user->getEmail(),
                "supportCsr" => $payout->getAdminUser()->getFullName(),
                "dateCreated" => $payout->getDateCreated()->format("m/d/Y"),
                "dateModified" => $payout->getDateModified()->format("m/d/Y"),
                "currency" => $currency,
                "amount" => $currency." ".number_format($payout->getAmount(), 2),
                "status" => $payout->getStatus() == Payout::PAYOUT_STATUS_INCOMPLETE? "Incomplete" : "Completed",
                "orderProducts" => $orderProducts,
                "documents" => $documents
            ));

        }

        return $payoutHistory;
    }

}
