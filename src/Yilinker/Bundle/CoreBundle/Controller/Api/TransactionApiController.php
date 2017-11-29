<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\PackageStatus;
use Doctrine\Common\Collections\Criteria;
use DateTime;
use Carbon\Carbon;

/**
 * Class TransactionApiController
 *
 * @package Yilinker\Bundle\CoreBundle\Controller\Api
 */
class TransactionApiController extends Controller
{
    /**
     * Retrieve details of an order product id
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getOrderProductDetailAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Order product not found',
            'data'         => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $orderProductId = $request->query->get('orderProductId', null);
        
        $orderProducts = null;
        if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $orderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                               ->getSellerOrderProductById($authenticatedUser, $orderProductId);
            if($orderProduct){
                $partnerDetails = array(
                    'buyer' => array(
                        'fullname'  => $orderProduct->getOrder()->getBuyer()->getFullname(),
                        'buyerId'  => $orderProduct->getOrder()->getBuyer()->getUserId(),
                    )
                );
            }
        }
        else{
            $orderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                               ->getBuyerOrderProductById($authenticatedUser, $orderProductId);
            if($orderProduct){
                $partnerDetails = array(
                    'seller' => array(
                        'fullname'  => $orderProduct->getSeller()->getFullname(),
                        'storeName' => $orderProduct->getSeller()->getStorename(),
                        'sellerId'  => $orderProduct->getSeller()->getUserId(),
                    )
                );
            }
        }

        if($orderProduct){
            $response['isSuccessful'] = true;
            $response['message'] = 'Order Product found';
            $response['data'] = $orderProduct->toArray(true);
            $response['data']['productImage'] = $orderProduct->getFullImagePath();
            if($response['data']['attributes'] !== null){
                $attributes = array();
                foreach($response['data']['attributes'] as $key => $attribute){
                    $attributes[] = array(
                        'attributeName'  => $key, 
                        'attributeValue' => $attribute,
                    );
                }
                $response['data']['attributes'] = $attributes;
            }

            $transactionService = $this->container->get('yilinker_core.service.transaction');
            $cancelleableStatuses = $transactionService->getCancellableOrderProductStatus();
            $shippableStatuses =  $transactionService->getShippableStatuses();
            $response['data']['isCancellable'] = in_array(
                $response['data']['orderProductStatus']['orderProductStatusId'], 
                $cancelleableStatuses
            );
            $response['data']['isShippable'] = in_array(
                $response['data']['orderProductStatus']['orderProductStatusId'], 
                $shippableStatuses
            );

            $packageDetails = $orderProduct->getPackageDetails();
            $deliveryLogs = null;
            if($packageDetails->count() > 0){

                $criteria = Criteria::create()->orderBy(array('dateAdded' => 'DESC'));
                $package = $orderProduct->getPackageDetails()->first()->getPackage();
                $packageHistories = $package->getPackageHistory()->matching($criteria)->first();
                if($packageHistories){
                    $deliveryLogs = array(
                        'lastCheckedInDate'     => $packageHistories->getDateAdded(),
                        'lastCheckedInBy'       => $packageHistories->getPersonInCharge(),
                        'lastActionType'        => $packageHistories->getPackageStatus()->getName(),
                        'lastCheckedinLocation' => $packageHistories->getAddress(),
                        'pickupRider'           => array(
                            'pickupRider'   => '',
                            'contactNumber' => '',
                        ),
                        'deliveryRider'         => array(
                            'pickupRider'   => '',
                            'contactNumber' => '',
                        ),
                    );
                   
                    $pickupPackageHistory = $em->getRepository('YilinkerCoreBundle:PackageHistory')
                                               ->getPackageHistoryByStatus($package, PackageStatus::STATUS_ACKNOWLEDGED_FOR_PICKUP);
                    $deliveryPackageHistory = $em->getRepository('YilinkerCoreBundle:PackageHistory')
                                                 ->getPackageHistoryByStatus($package, PackageStatus::STATUS_CHECKED_IN_BY_RIDER_FOR_DELIVERY);
                    if($pickupPackageHistory){
                        $deliveryLogs['pickupRider']['pickupRider'] = $pickupPackageHistory->getPersonInCharge();
                        $deliveryLogs['pickupRider']['contactNumber'] = $pickupPackageHistory->getContactNumber();
                    }
                    if($deliveryPackageHistory){
                        $deliveryLogs['deliveryRider']['deliveryRider'] = $deliveryPackageHistory->getPersonInCharge();
                        $deliveryLogs['deliveryRider']['contactNumber'] = $deliveryPackageHistory->getContactNumber();
                    }
                }
            }

            $response['data'] = array_merge($response['data'], $partnerDetails);
            $brandData = null;
            if($orderProduct->getBrand()){
                $brandData = $orderProduct->getBrand()->toArray();
                if($orderProduct->getBrandName() !== ""){
                    $brandData['name'] = $orderProduct->getBrandName();
                }
            }

            $response['data'] = array_merge($response['data'], array(
                'brand'                 => $brandData,
                'condition'             => $orderProduct->getCondition() ? $orderProduct->getCondition()->toArray() : null,
                'productCategoryId'     => $orderProduct->getProductCategory() ? $orderProduct->getProductCategory()->toArray() : null,
                'deliveryLogs'          => $deliveryLogs,
            ));

        }
        
        return new JsonResponse($response);
    }

    /**
     * Retrieve transaction detail of a certain transactionId
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getTransactionDetailsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $invoiceNumber = $request->query->get('transactionId');
        
        $orderProducts = array();
        $isSeller = false;
        if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getSellerOrderProductsByInvoice($authenticatedUser->getUserId(), $invoiceNumber);
            $isSeller = true;
        }
        else{
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getBuyerProductsByInvoice($invoiceNumber, $authenticatedUser->getUserId());
        }

        $response = array(
            'isSuccessful' => false,
            'message' => 'Transaction not found.',
            'data' => array(),
        );

        $responseCode = 404;
        $cancellableOrderProducts = array();
        $shippableOrderProducts = array();

        if(count($orderProducts) > 0){
            $order = reset($orderProducts)->getOrder();

            $totalShippingFee = "0.000";
            $totalCost = "0.0000";
            $totalQuantity = 0;

            $transactionItems = array();

            $transactionService = $this->container->get('yilinker_core.service.transaction');
            $cancelleableStatuses = $transactionService->getCancellableOrderProductStatus();
            $shippableStatuses =  $transactionService->getShippableStatuses();

            foreach($orderProducts as $orderProduct){
                $seller = $orderProduct->getSeller();
                if(false === isset($transactionItems[$seller->getUserId()])){
                    $store = $seller->getStore();
                    $transactionItems[$seller->getUserId()] = array(
                        'sellerId'            => $seller->getUserId(),
                        'sellerStore'         => $seller->getStorename(),
                        'sellerContactNumber' => $seller->getContactNumber(),
                        'sellerHasFeedback'   => $store->hasReview($authenticatedUser, $order),
                        'isAffiliate'         => $store->isAffiliate(),
                        'products'            => array(),
                    );
                }

                $orderProductData = $orderProduct->toArray(true);
                $orderProductData['productImage'] = $orderProduct->getFullImagePath();
                if($order->getIsFlagged() === false){
                    $orderProductData['isCancellable'] = in_array(
                        $orderProductData['orderProductStatus']['orderProductStatusId'], 
                        $cancelleableStatuses
                    );
                    $orderProductData['isShippable'] = in_array(
                        $orderProductData['orderProductStatus']['orderProductStatusId'], 
                        $shippableStatuses
                    );

                    if($orderProductData['isCancellable']){
                        $cancellableOrderProducts[] = $orderProductData['orderProductId'];
                    }
                    if($orderProductData['isShippable']){
                        $shippableOrderProducts[] = $orderProductData['orderProductId'];
                    }
                }

                $orderProductData['hasProductReview'] = count($orderProduct->getProductReviews()) > 0;
                $transactionItems[$seller->getUserId()]['products'][] = $orderProductData;
                $totalQuantity += $orderProduct->getQuantity();
                $totalShippingFee = bcadd($totalShippingFee, $orderProduct->getHandlingFee() + $orderProduct->getShippingFee(), 4);
                $totalCost = bcadd($totalCost, $orderProduct->getQuantifiedUnitPrice(), 4);
            }
            $totalCost = $isSeller ? $totalCost: $order->getTotalPrice();

            if($order){
                $responseCode = 200;
                $voucherData = array();
                $orderVouchers = $order->getOrderVouchers();
                $totalVoucherAmount = "0.00";
                foreach($orderVouchers as $orderVoucher){
                    $singleVoucher = $orderVoucher->toArray();
                    $voucherData[] = $singleVoucher;
                    $totalVoucherAmount = bcadd($totalVoucherAmount, $singleVoucher['amount'], 4);
                }

                $response['data'] = array(
                    'transactionInvoice'     => $order->getInvoiceNumber(),
                    'transactionShippingFee' => number_format($totalShippingFee, 2, '.', ','),
                    'transactionDate'        => $order->getDateAdded(),
                    'transactionQuantity'    => $totalQuantity,
                    'transactionPrice'       => number_format($totalCost, 2, '.', ','),
                    'transactionUnitPrice'   => number_format(bcadd(bcsub($totalCost, $totalShippingFee, 4), $totalVoucherAmount, 4), 2, '.', ','),
                    'transactionStatus'      => array(
                        'statusId'   => $order->getOrderStatus()->getOrderStatusId(),
                        'statusName' => $order->getOrderStatus()->getName()
                    ),
                    'transactionPayment'     => $order->getPaymentMethod()->getName(),
                    'transactionItems'       => array_values($transactionItems),
                    'isShippable'            => count($shippableOrderProducts) > 0,
                    'isCancellable'          => count($cancellableOrderProducts) > 0,
                    'hasOrderFeedback'       => count($order->getOrderFeedbacks()) > 0,
                    'vouchers'               => $voucherData,   
                );
                $response['isSuccessful'] = true;
                $response['message'] = "";
            }
        }

        return new JsonResponse($response, $responseCode);
    }

    /**
     * Retrieve consignee details
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getConsigneeDetailsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $invoiceNumber = $request->query->get('transactionId');
        
        $orderProducts = array();
        if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getSellerOrderProductsByInvoice($authenticatedUser->getUserId(), $invoiceNumber);
        }
        else if($authenticatedUser->getUserType() === User::USER_TYPE_BUYER){
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getBuyerProductsByInvoice($invoiceNumber, $authenticatedUser->getUserId());
        }

        $response = array(
            'isSuccessful' => false,
            'message'      => 'Transaction not found.',
            'data'         => array(),
        );

        $responseCode = 404;
        if(count($orderProducts) > 0){
            $order = reset($orderProducts)->getOrder();
            if($order){
                $responseCode = 200;
                $response['data'] = array(
                    "deliveryAddress"       => $order->getAddress(),
                    "consigneeName"         => $order->getBuyer()->getFullName(),
                    "consigneeContactNumber"=> $order->getBuyer()->getContactNumber(),
                    "buyerId"               => $order->getBuyer()->getUserId(),
                    "email"                 => $order->getBuyer()->getEmail(),
                    "isGuest"               => $order->getBuyer()->getUserType() === User::USER_TYPE_GUEST,
                    "isOnline"              => $order->getBuyer()->getIsOnline()
                );
                $response['isSuccessful'] = true;
                $response['message'] = "";
            }

        }

        return new JsonResponse($response, $responseCode);
    }

    /**
     * Get Transaction List for a user
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getTransactionListAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $transactionService = $this->get('yilinker_core.service.transaction');
                
        $invoiceNumber = $request->get('transactionId', null);
        $page = (int) $request->get('page', 1);
        $perPage = $request->get('perPage', null);
        $paymentMethod = $request->get('paymentMethod', null);
        $dateFrom = $request->get('dateFrom', null);
        $dateTo = $request->get('dateTo', null);

        if($dateFrom){
            try{
                $dateFrom = Carbon::createFromFormat("Y-m-d H:i:s", $dateFrom);
            }
            catch(\InvalidArgumentException $e){
                $dateFrom = Carbon::createFromFormat("Y-m-d", $dateFrom)->startOfDay();
            }
        }
        if($dateTo){
            try{
                $dateTo = Carbon::createFromFormat("Y-m-d H:i:s", $dateTo);
            }
            catch(\InvalidArgumentException $e){
                $dateTo = Carbon::createFromFormat("Y-m-d", $dateTo)->endOfDay();
            }
        }
      
        $productString = $request->get('productName', null);
        $riderString = $request->get('riderName', null);
        $type = $request->get('type', null);
        $sortBy = $request->get('sortBy', null);
        $sortDirection = $request->get('sortDirection', null);
        $isBuyer = $authenticatedUser->getUserType() === User::USER_TYPE_BUYER;
        $orderStatuses = $transactionService->getOrderStatusesValid($isBuyer);
        $orderProductStatuses = null;
        $forFeedback = null;
        $isScheduledForPickup = null;

        if($type == 'ongoing'){
            $orderStatuses = $transactionService->getOrderStatusesOnGoing($isBuyer);
        }
        else if($type == 'completed'){
            $orderStatuses = array(UserOrder::ORDER_STATUS_COMPLETED);
        }
        else if($type == 'cancelled'){
            $orderStatuses = array(UserOrder::ORDER_STATUS_FOR_CANCELLATION);
        }
        else if($type == 'for-resolution'){
            $orderProductStatuses = array(OrderProduct::STATUS_ITEM_RECEIVED_BY_BUYER);
        }
        else if($type == 'on-delivery'){
            $orderProductStatuses = array(OrderProductStatus::STATUS_PRODUCT_ON_DELIVERY);
        }
        else if($type == 'not-ondelivery'){
            $isScheduledForPickup = false;
        }
        else if($type == 'for-feedback'){
            /**
             * Filter only applies to buyers
             */
            $forFeedback = true;
        }

        $orderSearchResults = array();
        if($isBuyer === false){
            $orderSearchResults = $transactionService->getSellerTransactions(
                                           $authenticatedUser->getUserId(), 
                                           $dateFrom, 
                                           $dateTo, 
                                           $orderStatuses, 
                                           $paymentMethod, 
                                           $page,
                                           $perPage,
                                           $invoiceNumber,
                                           $productString,
                                           $riderString,
                                           $orderProductStatuses,
                                           $isScheduledForPickup,
                                           $sortBy,
                                           $sortDirection
                                       );
        }
        else{
            $orderSearchResults = $transactionService->getBuyerTransactions(
                                           $authenticatedUser->getUserId(), 
                                           $dateFrom, 
                                           $dateTo, 
                                           $orderStatuses, 
                                           $paymentMethod, 
                                           $page,
                                           $perPage,
                                           $invoiceNumber,
                                           $productString,
                                           $riderString,
                                           $orderProductStatuses,
                                           $forFeedback,
                                           $isScheduledForPickup,
                                           $sortBy,
                                           $sortDirection
                                       );
        }
        
        foreach($orderSearchResults['orders'] as $key => $result){
            $result['total_price'] = number_format($result['total_price'], 2, '.', ',');
            $result['total_unit_price'] = number_format($result['total_unit_price'], 2, '.', ',');
            $result['total_item_price'] = number_format($result['total_item_price'], 2, '.', ',');
            $result['total_handling_fee'] = number_format($result['total_handling_fee'], 2, '.', ',');
            $orderSearchResults['orders'][$key] = $result;
        
            // dont include [product on delivery,Item Received by Buyer]
            if($type == 'ongoing') {
                $productStatus = array_shift($result['unique_order_product_statuses'])['name'];
                if ($productStatus == 'Product on Delivery' || $productStatus == 'Item Received by Buyer') {
                    unset($orderSearchResults['orders'][$key]);
                }
            }
        }

        $response = array(
            'isSuccessful' => true,
            'message'      => '',
            'data'         => $orderSearchResults,
        );

        return new JsonResponse($response, 200);
    }

    /**
     * Retrieve delivery details
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function transactionKeywordSearchAction(Request $request)
    {
        $query = $request->get('query', null);
        $response = array(
            'isSuccessful' => false,
            'message' => 'No keywords found',
            'data' => array(),
        );

        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $sellerId = $authenticatedUser->getUserId();
            $buyerId = null;
        }
        else if($authenticatedUser->getUserType() === User::USER_TYPE_BUYER){
            $buyerId = $authenticatedUser->getUserId();
            $sellerId = null;
        }

        $em = $this->getDoctrine()->getManager();
        $orders = $em->getRepository('YilinkerCoreBundle:UserOrder')
                     ->searchInvoiceNumber($query, $sellerId, $buyerId);

        if(count($orders) > 0){
            $response['message'] = "Invoice number suggestion(s) found.";
            $response['isSuccessful'] = true;
            foreach($orders as $order){
                $response['data'][] = array(
                    'invoiceNumber' => $order->getInvoiceNumber(),
                    'target' => $this->generateUrl(
                        'api_core_get_transaction_details', 
                        array('transactionId' => $order->getInvoiceNumber())
                    ),
                );
            }
        }
        
        return new JsonResponse($response);
    }

    /**
     * Get available cancellation reasons
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getCancellationReasonsAction()
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '0 results found',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $reasons = $em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason')
                      ->findAll();

        $data = array();
        foreach($reasons as $reason){
            $data[] = $reason->toArray();
        }
        
        $numberOfReasons = count($reasons);
        if($numberOfReasons){
            $response['isSuccessful'] = true;
            $response['message'] = $numberOfReasons." results found";
            $response['data'] = $data;
        }

        return new JsonResponse($response);
    }

    /**
     * Cancel an order
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function cancelOrderAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );

        $form = $this->createForm('api_order_product_cancellation');
        $form->submit(array(
            'reasonId' => $request->get('reasonId', null),
            'remark' => $request->get('remark'),
            'invoiceNumber' => $request->get('transactionId', null),
        ));
            
        if ($form->isValid()) {
            $formData = $form->getData();
            $response['message'] = 'Order product cancellation failed';

            $em = $this->getDoctrine()->getManager();
            $transactionId = $formData['invoiceNumber'];
            $reasonId = $formData['reasonId'];
            $remark = $formData['remark'];
            $orderProductIds = $request->request->has('orderProductIds') ? json_decode($request->get('orderProductIds')) : null;

            $tokenStorage = $this->container->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();

            $orderProducts = array();
            if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
                $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                    ->getSellerOrderProductsByInvoice(
                                        $authenticatedUser->getUserId(), 
                                        $transactionId, 
                                        $orderProductIds
                                    );
            }
            else if($authenticatedUser->getUserType() === User::USER_TYPE_BUYER){
                $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                    ->getBuyerProductsByInvoice(
                                        $transactionId,
                                        $authenticatedUser->getUserId(), 
                                        $orderProductIds
                                    );
            }

            $transactionService = $this->container->get('yilinker_core.service.transaction');
            $cancellationReason = $em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason')
                                     ->findOneBy(array('orderProductCancellationReasonId' => $reasonId));
            if(count($orderProducts) > 0){ 
                $response['isSuccessful'] = $transactionService->cancellationRequestTransactionByUser(
                    $orderProducts,
                    $cancellationReason,
                    $remark,
                    $authenticatedUser
                );
                if($response['isSuccessful']){
                    $response['message'] = "Cancellation request successfuly sent.";
                }
            }
            else{
                $response['message'] = "No voidable order product found.";
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }


    /**
     * Schedule product pickup
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function sellerScheduleProductPickupAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );

        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $orderProductIds = $request->get('orderProductIds');
        $invoiceNumber = $request->get('transactionId');
        $orderProductIdChoices = array();
        $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                            ->getSellerOrderProductsByInvoice($authenticatedUser->getUserId(), $invoiceNumber, $orderProductIds);
        /**
         * orderProductIds acts as a filter for the given userOrder
         */
        foreach($orderProducts as $orderProduct){
            $orderProductIdChoices[] = $orderProduct->getOrderProductId();
        }

        $form = $this->createForm('core_product_pickup', null, array(
            'csrf_protection' => false,
            'orderProducts'   => $orderProducts,
        ));

        $currentDatetime = new \DateTime();
        $currentDatetime->modify('+1 day');
        $form->submit(array(
            'orderProducts'  => count($orderProductIds) > 0 ? $orderProductIds : $orderProductIdChoices,
            //'pickupDatetime' => $request->get('pickupSchedule'),
            'pickupDatetime' => $currentDatetime->format('Y-m-d H:i:s'),
            'pickupRemark'   => $request->get('pickupRemark', ''),
        ));
            
        if ($form->isValid()) {
            $formData = $form->getData();

            if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
                $shipmentApiResponse = $this->container->get('yilinker_core.logistics.yilinker.express')
                                            ->schedulePickup(
                                                $formData['pickupDatetime'], 
                                                $formData['pickupRemark'], 
                                                $formData['orderProducts'],
                                                $authenticatedUser
                                            );

                $response = $shipmentApiResponse;
                if($shipmentApiResponse['isSuccessful']){
                    $transactionService = $this->container->get('yilinker_core.service.transaction');
                    $formattedShipmentDetails = $transactionService->formatOrderProductShipmentDetails(
                        reset($formData['orderProducts']), $authenticatedUser
                    );
                    $response['data'] = array_merge($response['data'],$formattedShipmentDetails);
                }
            }
            else{
                $response['message'] = 'Only sellers can schedule product pick-up';
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response, 200);
    }

    /**
     * Retrieve transaction delivery logs
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getTransactionDeliveryLogsAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'Order product not found',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $orderProductId = $request->query->get('orderProductId');
                
        $orderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                           ->findOneBy(array(
                               'orderProductId' => $orderProductId,
                           ));
        if($orderProduct){
            if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
                if($orderProduct->getSeller()->getUserId() !== $authenticatedUser->getUserId()){
                    $orderProduct = null;
                }
            }
            else{
                if($orderProduct->getOrder()->getBuyer()->getUserId() !== $authenticatedUser->getUserId()){
                    $orderProduct = null;
                }
            }
        }

        if($orderProduct !== null){
            $deliveryStatus = array();
            $response['isSuccessful'] = true;
            $response['message'] = "Delivery Logs retrieved";

            $packageDetails = $orderProduct->getPackageDetails();
            $deliveryLogs = array();
            if($packageDetails->count() > 0){

                $criteria = Criteria::create()->orderBy(array('dateAdded' => 'DESC'));
                $packageHistories = $orderProduct->getPackageDetails()->first()->getPackage()
                                                 ->getPackageHistory()->matching($criteria);
                if($packageHistories){
                    foreach($packageHistories as $history){
                        $deliveryLogs[] = $history->toArray();
                    }
                }
            }

            $response['data']= array(
                'orderProductId' => $orderProduct->getOrderProductId(),
                'productName' => $orderProduct->getProductName(),
                'deliveryLogs' => $deliveryLogs,
            );
        }

        return new JsonResponse($response, $response['isSuccessful'] ? 200 : 404);
    }
    
    /**
     * Get package person in charge suggestion
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPackagePersonInChargeSuggestionAction(Request $request)
    {
        $resultsPerPage = 15;         
        $queryString = $request->get('queryString', '');
        $limit = (int) $request->get('perPage', $resultsPerPage);
        $page = (int) $request->get('page', 1);
        $offset = $page > 0 ? ($page - 1) * $limit  : 0;

        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        
        $seller = null;
        $buyer = null;
        if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $seller = $authenticatedUser;
        }
        else{
            $buyer = $authenticatedUser;
        }

        $em = $this->getDoctrine()->getManager();                
        $packageHistories = $em->getRepository('YilinkerCoreBundle:PackageHistory')
                               ->findPackagePersonInCharge($queryString, $limit, $offset, $seller, $buyer);

        $personSuggestion = array();
        foreach($packageHistories as $packageHistory){
            $personSuggestion[] = $packageHistory['personInCharge'];
        }
        $resultsFound = count($personSuggestion);

        return new JsonResponse(array(
            'data'         => $personSuggestion,
            'isSuccessful' => $resultsFound > 0,
            'message'      => $resultsFound > 0 ? "Suggestion found" : "No suggestions found",
        ));
    }
    
    /**
     * Retrieve delivery details
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getTransactionDeliveryOverviewAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Transaction not found',
            'data'         => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $invoiceNumber = $request->query->get('transactionId');

        $orderProducts = array();
        if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getSellerOrderProductsByInvoice($authenticatedUser->getUserId(), $invoiceNumber);
        }
        else{
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getBuyerProductsByInvoice($invoiceNumber, $authenticatedUser->getUserId());
        }

        if(count($orderProducts) > 0){
            
            $packages = $em->getRepository('YilinkerCoreBundle:Package')
                           ->getPackagesByOrderProducts($orderProducts);
            $packagesLogs = array();
            foreach($packages as $package){
                $lastPackageHistory = $package->getMostRecentPackageHistory();

                $packageOrderProducts = $package->getOrderProducts();
                $orderProductDetails = array();
                foreach($packageOrderProducts as $packageOrderProduct){
                    $orderProductDetails[] = array(
                        'orderProductId' => $packageOrderProduct->getOrderProductId(),
                        'productName'    => $packageOrderProduct->getProductName(),
                    );
                }

                $packageDetails = array(
                    'waybillNumber'              => $package->getWaybillNumber(),
                    'packageStatus'              => $package->getPackageStatus()->getName(),
                    'lastCheckedInDate'          => $lastPackageHistory->getDateAdded(),
                    'lastCheckedInBy'            => $lastPackageHistory->getPersonInCharge(),                    
                    'lastCheckedInLocation'      => $lastPackageHistory->getAddress(),
                    'pickupRider'                => "",
                    'pickupRiderContactNumber'   => "",
                    'deliveryRider'              => "",
                    'deliveryRiderContactNumber' => "",
                    'orderProducts'              => $orderProductDetails,
                );

                $pickupPackageHistory = $em->getRepository('YilinkerCoreBundle:PackageHistory')
                                           ->getPackageHistoryByStatus($package, PackageStatus::STATUS_ACKNOWLEDGED_FOR_PICKUP);
                $deliveryPackageHistory = $em->getRepository('YilinkerCoreBundle:PackageHistory')
                                             ->getPackageHistoryByStatus($package, PackageStatus::STATUS_CHECKED_IN_BY_RIDER_FOR_DELIVERY);
                if($pickupPackageHistory){
                    $packageDetails['pickupRider'] = $pickupPackageHistory->getPersonInCharge();
                    $packageDetails['pickupRiderContactNumber'] = $pickupPackageHistory->getContactNumber();
                }
                if($deliveryPackageHistory){
                    $packageDetails['deliveryRider'] = $deliveryPackageHistory->getPersonInCharge();
                    $packageDetails['deliveryRiderContactNumber'] = $deliveryPackageHistory->getContactNumber();
                }
                $packagesLogs[] = $packageDetails;
            }

            if(count($packagesLogs) > 0){
                $response['isSuccessful'] = true;
                $response['message'] = "Package details retrieved";
                $response['data'] = $packagesLogs;
            }
            else{
                $response['message'] = "No package found for this order";
            }
        }

        return new JsonResponse($response, 200);
    }

    /**
     * Get the payment methods
     *
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getPaymentMethodsAction()
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'No available payment methods',
            'data'         => array(),
        );
        
        $em = $this->getDoctrine()->getManager();
        $paymentMethods = $em->getRepository('YilinkerCoreBundle:PaymentMethod')
                             ->findAll();
        $paymentDetails = array();
        foreach($paymentMethods as $paymentMethod){
            $paymentDetails[] = $paymentMethod->toArray();
        }

        if(count($paymentDetails) > 0){
            $response['isSuccessful'] = true;
            $response['message'] = "Payment methods retrieved";
            $response['data'] = $paymentDetails;
        }

        return new JsonResponse($response);;
    }
    
}
