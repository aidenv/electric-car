<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;
use DateTime;
use Carbon\Carbon;

/**
 * Class OrderProductRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class OrderProductRepository extends EntityRepository
{
    /**
     * Retrieve total sales of a seller
     *
     * @param int $sellerId
     * @param int[] $orderStatuses
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @return string
     */
    public function getSellerTransactionSales($userId, $orderStatuses = null, DateTime $dateFrom = null, DateTime $dateTo = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("COALESCE(SUM(op.totalPrice), 0)")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin('op.product', 'p', 'WITH', 'p.productId  = op.product')
                     ->where('p.user = :sellerId')
                     ->setParameter('sellerId', $userId);
        if($orderStatuses !== null){
            $queryBuilder->andWhere('op.orderProductStatus IN (:orderStatuses)')
                         ->setParameter('orderStatuses', $orderStatuses);
        }

        if($dateFrom !== null){
            $queryBuilder->andWhere('op.dateAdded >= :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
        }

        if($dateTo !== null){
            $queryBuilder->andWhere('op.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
        }

        $total = $queryBuilder->getQuery()
                              ->getSingleScalarResult();

        return $total;
    }

    /**
     * Retrieve total net sales of a seller
     *
     * @param int $userId
     * @param int[] $orderStatuses
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @return string
     */
    public function getSellerTransactionNetSales($userId, $orderStatuses = null, DateTime $dateFrom = null, DateTime $dateTo = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("COALESCE(SUM(op.net), 0)")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin('op.product', 'p', 'WITH', 'p.productId  = op.product')
                     ->where('p.user = :sellerId')
                     ->setParameter('sellerId', $userId);
        if($orderStatuses !== null){
            $queryBuilder->andWhere('op.orderProductStatus IN (:orderStatuses)')
                         ->setParameter('orderStatuses', $orderStatuses);
        }

        if($dateFrom !== null){
            $queryBuilder->andWhere('op.dateAdded >= :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
        }

        if($dateTo !== null){
            $queryBuilder->andWhere('op.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
        }

        $total = $queryBuilder->getQuery()
                              ->getSingleScalarResult();

        return $total;
    }

    public function whereStatus($statuses)
    {
        $operator = is_array($statuses) ? 'IN (:statuses)': '= :statuses';
        $this->andWhere("this.orderProductStatus $operator")
             ->setParameter('statuses', $statuses);

        return $this;
    }

    public function whereUser($user)
    {
        $this->innerJoin('this.order', 'order')
             ->innerJoin('order.buyer', 'buyer')
             ->andWhere('buyer.userId = :userId')
             ->setParameter('userId', $user->getUserId());

        return $this;
    }

    public function whereQuery(array $query)
    {
        if (isset($query['seller'])) {
            $this->getQB()
                 ->andWhere('this.seller = :sellerId')
                 ->setParameter('sellerId', $query['seller']);
        }

        if (isset($query['lastDateModified.dateFrom']) && trim($query['lastDateModified.dateFrom']) !== "") {
            $this->getQB()
                 ->andWhere('this.lastDateModified >= :lastDateModifiedDateFrom')
                 ->setParameter('lastDateModifiedDateFrom', $query['lastDateModified.dateFrom']." 00:00:00");
        }

        if (isset($query['lastDateModified.dateTo']) && trim($query['lastDateModified.dateTo']) !== "") {
            $this->getQB()
                 ->andWhere('this.lastDateModified <= :lastDateModifiedDateTo')
                 ->setParameter('lastDateModifiedDateTo', $query['lastDateModified.dateTo']." 23:59:59");
        }

        if (isset($query['orderProductStatus']) && is_array($query['orderProductStatus']) && !empty($query['orderProductStatus'])) {
            $this->getQB()
                 ->andWhere('this.orderProductStatus IN (:orderProductStatus)')
                 ->setParameter('orderProductStatus', $query['orderProductStatus']);
        }

        if (isset($query['productName']) && $query['productName']) {
            $this->getQB()
                 ->andWhere('this.productName LIKE :productName')
                 ->setParameter('productName', '%'.$query['productName'].'%');
        }

        return $this;
    }

    public function joinInOrder(array $filter = array())
    {
        $this->getQB()->leftJoin('this.order', 'userOrder');
        $this->getQB()->leftJoin('userOrder.buyer', 'user');

        if (is_array($filter) && empty($filter) === false) {
            if (isset($filter['invoiceNumber']) && $filter['invoiceNumber']) {
                $this->getQB()
                     ->andWhere('userOrder.invoiceNumber LIKE :invoiceNumber')
                     ->setParameter('invoiceNumber', '%'.$filter['invoiceNumber'].'%');
            }

            if (isset($filter['buyer']) && $filter['buyer']) {
                $this->getQB()
                     ->andWhere('user.firstName LIKE :buyer OR user.lastName LIKE :buyer')
                     ->setParameter('buyer', '%'.$filter['buyer'].'%');
            }

            if(isset($filter['isFlagged'])){
                
                if($filter['isFlagged']){
                    $this->getQB()
                         ->innerJoin('userOrder.userOrderFlagged', 'userOrderFlagged')
                         ->andWhere('userOrderFlagged != :approved')
                         ->setParameter('approved', UserOrderFlagged::APPROVE);
                }
                else{
                    $this->getQB()
                         ->leftJoin('userOrder.userOrderFlagged', 'userOrderFlagged')
                         ->andWhere("userOrderFlagged IS NULL OR userOrderFlagged.status = :approved")
                         ->setParameter('approved', UserOrderFlagged::APPROVE);                        
                }
            }            
        }

        return $this;
    }

    /**
     * Retrieve orderproducts belonging to a buyer for a given invoice number
     *
     * @param string $invoiceNumber
     * @param int $productId
     * @return Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    public function getBuyerProductsByInvoice($invoiceNumber, $buyerId, $orderProductIds = null)
    {        
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("op")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin('op.order', 'o', 
                                 'WITH', 
                                 'op.order = o.orderId AND o.invoiceNumber = :invoiceNumber AND o.buyer = :buyer')
                     ->setParameter('buyer', $buyerId)
                     ->setParameter('invoiceNumber', $invoiceNumber);
        
        if($orderProductIds !== null){
            $queryBuilder->andWhere('op.orderProductId IN (:orderProductIds)')
                         ->setParameter('orderProductIds', $orderProductIds);
        }

        $orderProducts = $queryBuilder->getQuery()->getResult();

        return $orderProducts;
    }

    public function fraudFrequency($user)
    {
        $fraudStatuses = array(
            OrderProduct::STATUS_BUYER_CANCELLATION_BEFORE_DELIVERY_APPROVED,
            OrderProduct::STATUS_BUYER_REFUND_RELEASED
        );
        $fraudelentCount = $this->qb()
                                ->whereStatus($fraudStatuses)
                                ->whereUser($user)
                                ->getCount();
        $orderProductCount = $this->qb()
                                  ->whereUser($user)
                                  ->getCount();
        $frequency = $orderProductCount > 0 ? (($fraudelentCount / $orderProductCount) * 100): 0;

        return $frequency;
    }
    
    /**
     * Get seller order products by invoiceNumber
     *
     * @param int $sellerId
     * @param string $invoiceNumber
     * @param int[] $orderProductIds
     * @return Yilinker/Bundler/CoreBundle/Entity/OrderProduct
     */
    public function getSellerOrderProductsByInvoice($sellerId, $invoiceNumber, $orderProductIds = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("op")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin(
                         'op.order', 'o', 
                         'WITH', 
                         'op.order = o.orderId AND o.invoiceNumber = :invoiceNumber'
                     )
                     ->innerJoin(
                         'op.product', 'p', 
                         'WITH', 
                         'op.product = p.productId AND op.seller  = :sellerId'
                     )
                     ->setParameter('sellerId', $sellerId)
                     ->setParameter('invoiceNumber', $invoiceNumber);
        
        if($orderProductIds !== null){
            $queryBuilder->andWhere('op.orderProductId IN (:orderProductIds)')
                         ->setParameter('orderProductIds', $orderProductIds);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get seller order products by id
     *
     * @param int $sellerId
     * @param int $orderProductId
     * @return Yilinker/Bundler/CoreBundle/Entity/OrderProduct
     */
    public function getSellerOrderProductById($sellerId, $orderProductId)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("op")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin(
                         'op.product', 'p', 
                         'WITH', 
                         'op.product = p.productId AND p.user  = :sellerId'
                     )
                     ->where('op.orderProductId = :orderProductId')
                     ->setParameter('sellerId', $sellerId)
                     ->setParameter('orderProductId', $orderProductId)
                     ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Get seller order products by id
     *
     * @param int $sellerId
     * @param int[] $orderProductIds
     * @return Yilinker/Bundler/CoreBundle/Entity/OrderProduct[]
     */
    public function getSellerOrderProductsByIds($sellerId, $orderProductIds)
    {
        if(is_array($orderProductIds) === false){
            $orderProductIds = array($orderProductIds);
        }
               
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("op")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin(
                         'op.product', 'p', 
                         'WITH', 
                         'op.product = p.productId AND p.user  = :sellerId'
                     )
                     ->where('op.orderProductId IN (:orderProductIds)')
                     ->setParameter('sellerId', $sellerId)
                     ->setParameter('orderProductIds', $orderProductIds);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get buyer order products by id
     *
     * @param int $buyerId
     * @param int $orderProductId
     * @return Yilinker/Bundler/CoreBundle/Entity/OrderProduct
     */
    public function getBuyerOrderProductById($buyerId, $orderProductId)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("op")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin(
                         'op.order', 'o', 
                         'WITH', 
                         'op.order = o.orderId AND o.buyer = :buyerId'
                     )
                     ->where('op.orderProductId = :orderProductId')
                     ->setParameter('buyerId', $buyerId)
                     ->setParameter('orderProductId', $orderProductId)
                     ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Get multiple buyer order products by id
     *
     * @param int $buyerId
     * @param int[] $orderProductIds
     * @return Yilinker/Bundler/CoreBundle/Entity/OrderProduct[]
     */
    public function getBuyerOrderProductsByIds($buyerId, $orderProductIds)
    {
        if(is_array($orderProductIds) === false){
            $orderProductIds = array($orderProductIds);
        }
       
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("op")
                     ->from("YilinkerCoreBundle:OrderProduct", "op")
                     ->innerJoin(
                         'op.order', 'o',
                         'WITH',
                         'op.order = o.orderId AND o.buyer = :buyerId'
                     )
                     ->where('op.orderProductId IN (:orderProductIds)')
                     ->setParameter('buyerId', $buyerId)
                     ->setParameter('orderProductIds', $orderProductIds);

        return $queryBuilder->getQuery()->getResult();
    }

    public function isReviewable($orderProduct)
    {
        $this
            ->qb()
            ->innerJoin('this.orderProductHistories', 'orderProductHistories')
            ->andWhere('this = :orderProduct')
            ->andWhere('orderProductHistories.orderProductStatus = :orderProductStatus')
            ->setParameter('orderProduct', $orderProduct)
            ->setParameter('orderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER)
        ;

        return $this->getCount();
    }
    
    /**
     * Get shippable order products by invoice number
     *
     * @param string $invoiceNumber
     * @param boolean $isAffiliateOnly
     * @param int $offset
     * @param int $limit
     * @param DateTime $dateFrom
     * @return Yilinker\Bundle\CoreBundle\Entity\OrderProduct[]
     */
    public function getShippableOrderProducts(
        $invoiceNumber = null, $isAffiliateOnly = false, $offset = null, $limit = null, $dateFrom = null
    ){
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("orderProductId", "orderProductId")
            ->addScalarResult("packageDetailCount", "packageDetailCount");

        $sql = "
        SELECT 
            op.order_product_id as orderProductId, COUNT(pd.package_detail_id) as packageDetailCount
        FROM OrderProduct op
        LEFT JOIN PackageDetail pd ON pd.order_product_id = op.order_product_id
        LEFT JOIN UserOrder o ON o.order_id = op.order_id
        LEFT JOIN UserOrderFlagged f ON f.user_order_flagged_id = o.user_order_flagged_id
        WHERE
           (f.user_order_flagged_id IS NULL or f.status = :flaggedStatusApproved) AND      
           op.order_product_status_id IN (:orderProductStatuses) AND
           op.is_not_shippable = :isNotShippable
        ";
        if($dateFrom !== null){
            $sql .= " AND op.date_added >= :dateFrom ";
        }
        if($invoiceNumber !== null){
            $sql .= " AND o.invoice_number = :invoiceNumber ";
        }
        if($isAffiliateOnly){
            $sql .= " AND op.manufacturer_product_unit_id IS NOT NULL ";
        }

        $sql .= " GROUP BY op.order_product_id HAVING packageDetailCount = 0 ";

        if($limit !== null){
            $sql .= " LIMIT :limit ";
        }
        if($offset !== null){
            $sql .= " OFFSET :offset ";
        }
        
        $query = $this->_em->createNativeQuery($sql, $rsm);
        if($invoiceNumber !== null){
            $query->setParameter('invoiceNumber', $invoiceNumber);
        }

        $allowedOrderProductStatuses = array(
            OrderProduct::STATUS_PAYMENT_CONFIRMED,
            OrderProduct::STATUS_COD_TRANSACTION_CONFIRMED,
        );
        $query->setParameter('orderProductStatuses', $allowedOrderProductStatuses)
              ->setParameter('flaggedStatusApproved', UserOrderFlagged::APPROVE)
              ->setParameter('isNotShippable', false);
        
        if($limit !== null){
            $query->setParameter("limit", $limit);
        }
        if($offset !== null){
            $query->setParameter("offset", $offset);
        }
        if($dateFrom !== null){
            $query->setParameter("dateFrom", $dateFrom->format("Y-m-d H:i:s"));
        }

        $orderProducts = array();
        $results = $query->getResult();
        if(count($results) > 0){        
            $orderProductIds = array_map(function($result) {
                return $result['orderProductId'];
            }, $results);
            $qb = $this->createQueryBuilder("op");
            $qb->andWhere(
                $qb->expr()->in('op.orderProductId', $orderProductIds)
            );
            $orderProducts = $qb->getQuery()->getResult();
        }
        
        return $orderProducts;
    }

    public function getResellerOrderProducts($offset = 0, $limit = 10, $beginDate = null, $endDate = null, array $status)
    {
        $queryBuilder = $this->createQueryBuilder("op");

        if(!is_null($beginDate)){
            $beginDateExpr = $queryBuilder->expr()->gte("op.lastDateModified", ":beginDate");
            $queryBuilder->andWhere($beginDateExpr)
                         ->setParameter(":beginDate", $beginDate);
        }

        if(!is_null($endDate)){
            $endDateExpr = $queryBuilder->expr()->lte("op.lastDateModified", ":endDate");
            $queryBuilder->andWhere($endDateExpr)
                         ->setParameter(":endDate", $endDate);
        }
        
        $queryBuilder->innerJoin("YilinkerCoreBundle:UserOrder", "o", "WITH", "op.order = o.orderId AND o.orderStatus IN (:orderStatuses)")
                     ->setParameter(":orderStatuses", $status);

        if(!is_null($offset) && !is_null($limit)){
            $queryBuilder->setFirstResult($offset)
                         ->setMaxResults($limit);
        }

        $collection["orderProducts"] = $queryBuilder->getQuery()->getResult();

        $queryBuilder->setFirstResult(null)
                     ->setMaxResults(null);
        
        $paginator = new Paginator($queryBuilder);
        $collection["totalResults"] = $paginator->count();

        $collection["totalPage"] = (int)ceil($paginator->count()/$limit);

        return $collection;
    }

    public function inactiveOrderProducts($page = null, $limit = 10)
    {
        $now = Carbon::now();
        $expiredTime = $now->subMinutes(10);
        $dragonPayExpiration = Carbon::now()->subWeekdays(4);

        $dragonPaymentMethod = $this->_em->getReference('YilinkerCoreBundle:PaymentMethod', PaymentMethod::PAYMENT_METHOD_DRAGONPAY);

        $baseRequirement = '
            this.dateAdded <= :expiredTime AND 
            this.orderProductStatus is NULL AND 
            this.returnableQuantity > :returnableQuantity
        ';
        $dragonPayRequirement = '(
            userOrder.paymentMethod = :dragonPaymentMethod AND 
            (userOrder.paymentMethodStatus IN (:paymentMethodStatus) OR
            this.dateAdded <= :dragonPayExpiration)
        )';
        $this
            ->qb()
            ->innerJoin('this.order', 'userOrder')
            ->andWhere($baseRequirement.' AND userOrder.paymentMethod != :dragonPaymentMethod')
            ->orWhere($baseRequirement.' AND '.$dragonPayRequirement)
            ->setParameter('expiredTime', $expiredTime)
            ->setParameter('dragonPayExpiration', $dragonPayExpiration)
            ->setParameter('returnableQuantity', 0)
            ->setParameter('dragonPaymentMethod', $dragonPaymentMethod)
            ->setParameter('paymentMethodStatus', array('f'))
        ;
        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $this
                ->setFirstResult($offset)
                ->setMaxResults($limit)
            ;
        }

        return $this->getResult();
    }

    public function increaseProductQuantity($orderProduct, $increase = false)
    {
        if (!$increase) {
            $increaseStatuses = array(
                OrderProductStatus::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
                OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED,
                OrderProductStatus::STATUS_CANCELED_BY_ADMIN
            );
            $orderProductStatus = $orderProduct->getOrderProductStatus();
            $orderProductStatusId = $orderProductStatus ? $orderProductStatus->getOrderProductStatusId(): 0;
            $increase = in_array($orderProductStatusId, $increaseStatuses);
        }

        if ($increase) {
            $productUnitRef = $orderProduct->getProductUnitReference();
            if ($productUnitRef) {
                $em = $this->getEntityManager();
                $em->transactional(function($em) use ($productUnitRef, $orderProduct) {
                    $orderedQuantity = $orderProduct->getReturnableQuantity();
                    $orderedQuantity = $orderedQuantity ? $orderedQuantity: 0;

                    $supplyQuantity = $productUnitRef->getQuantity();
                    $supplyQuantity = $supplyQuantity ? $supplyQuantity: 0;

                    $supplyQuantity += $orderedQuantity;

                    $productUnitRef->setQuantity($supplyQuantity);
                    $orderProduct->setReturnableQuantity(0);
                });

                return true;
            }
        }

        return false;
    }

    /**
     * Get Sold order product by date
     *
     * @param null $dateFrom
     * @param null $dateTo
     * @param Product $product
     * @return int
     */
    public function getSoldQuantityByProduct(Product $product, $dateFrom = null, $dateTo = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('SUM(OrderProduct.quantity) as numberOfSold')
                     ->from('YilinkerCoreBundle:OrderProduct', 'OrderProduct')
                     ->leftJoin('YilinkerCoreBundle:UserOrder', 'UserOrder', 'WITH', 'UserOrder.orderId = OrderProduct.order')
                     ->leftJoin('YilinkerCoreBundle:Product', 'Product', 'WITH', 'Product.productId = OrderProduct.product')
                     ->where('OrderProduct.product = :product')
                     ->setParameter('product', $product);

        if ($dateFrom !== null) {
            $queryBuilder->andWhere('UserOrder.dateAdded >= :dateFrom')
                         ->setParameter(':dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder->andWhere('UserOrder.dateAdded < :dateTo')
                         ->setParameter(':dateTo', $dateTo);
        }

        $query = $queryBuilder->getQuery();

        return (int) $query->getScalarResult()[0]['numberOfSold'];
    }

    public function getTotalSoldWithIds($orderProductIds)
    {
        if (empty($orderProductIds)) {
            return array();
        }

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("lastDateModified", "lastDateModified")
            ->addScalarResult("transactionCount", "transactionCount");

        $sql = "
            SELECT 
                DATE(`last_date_modified`) AS `lastDateModified`,
                COUNT(*) as `transactionCount`
            FROM `OrderProduct`
            WHERE order_product_id IN (:orderProductIds)
            GROUP BY DATE(`last_date_modified`)
        ";

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter(":orderProductIds", $orderProductIds);

        return $query->getResult();
    }

    public function getTotalSuccessTransaction($seller)
    {
        $result = $this->qb()
                       ->select('COUNT(DISTINCT this.order)')
                       ->whereQuery(array('seller' => $seller))
                       ->getQB()->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * Retrieve seller/affiliates order products with no earning yet
     *
     * @param int[] $orderProductStatuses
     * @param int[] $earningTypes
     * @param int $limit
     * @param int $offset
     * @param boolean $hydateEntity
     * @return Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    public function getSellerAffiliateOrderProductsWithNoEarning(
        $orderProductStatuses = null, $earningTypes = array(EarningType::SALE, EarningType::AFFILIATE_COMMISSION),
        $limit = null, $offset = null, $hydrateEntity = true
    )        
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("orderProductId", "orderProductId")
            ->addScalarResult("earningTransactionCount", "earningTransactionCount");
        $earningTypeCondition = "";
        if($earningTypes !== null){
            if(is_array($earningTypes) === false){
                $earningTypes = array($earningTypes);
            }
            $earningTypeCondition = " AND e.earning_type_id IN (:earningType) ";
        }
        
        $sql = "
            SELECT 
                op.order_product_id as `orderProductId`,
                COUNT(et.earning_transaction_id) as `earningTransactionCount`
            FROM `OrderProduct` op
            LEFT JOIN `EarningTransaction` et ON et.order_product_id = op.order_product_id
            LEFT JOIN `Earning` e ON e.earning_id = et.earning_id ".$earningTypeCondition."
            WHERE 1
        ";

        if($orderProductStatuses !== null){
            if(is_array($orderProductStatuses) === false){
                $orderProductStatuses = array($orderProductStatuses);
            }
            $sql .= " AND op.order_product_status_id IN (:orderProductStatuses)";
        }

        $sql .= " 
            GROUP BY op.order_product_id
            HAVING earningTransactionCount = 0
            ORDER BY op.order_product_id ASC
        ";

        $aggregateSql = "
           SELECT 
              opAggregate.order_product_id 
           FROM 
              (".$sql.") opAggregate           
        ";

        if($limit !== null){
            $aggregateSql .= " LIMIT :limit ";
        }
        if($offset !== null){
            $aggregateSql .= " OFFSET :offset ";
        }
        
        $query = $this->_em->createNativeQuery($sql, $rsm);
        if($orderProductStatuses !== null){
            $query->setParameter("orderProductStatuses", $orderProductStatuses);
        }
        if($earningTypes !== null){
            $query->setParameter("earningType", $earningTypes);
        }
        if($limit !== null){
            $query->setParameter("limit", $limit);
        }
        if($offset !== null){
            $query->setParameter("offset", $offset);
        }

        $results = $query->getResult();

        if($hydrateEntity && count($results) > 0){
            $orderProductIds = array_map(function($result) {
                return $result['orderProductId'];
            }, $results);
            $qb = $this->createQueryBuilder("op");
            $qb->andWhere(
                $qb->expr()->in('op.orderProductId', $orderProductIds)
            );
            $results = $qb->getQuery()->getResult();                                  
        }

        return $results;
    }
    
}
