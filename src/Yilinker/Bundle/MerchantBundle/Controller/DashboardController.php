<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\StoreLevel;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Services\Earner\Earnings;
use Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;
use Carbon\Carbon;


/**
 * Class DashboardController
 *
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class DashboardController extends YilinkerBaseController
{
    const FOLLOWER_PAGE_LIMIT = 30;

    const ORDER_PER_PAGE = 15;

    const POINT_ENTRIES_PER_PAGE = 15;

    const DISPUTE_CASE_PER_PAGE = 15;

    /**
     * Render Dashboard Overview Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardOverviewAction (Request $request)
    {
        $baseUri = $this->getParameter('frontend_hostname');
        $em = $this->getDoctrine()->getManager();
        $orderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $orderProductRepository = $em->getRepository('YilinkerCoreBundle:OrderProduct');
        $productReviewRepository = $em->getRepository('YilinkerCoreBundle:ProductReview');
        $tokenStorage = $this->container->get('security.token_storage');

        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $store = $authenticatedUser->getStore();

        $dateTo = $request->get('dateTo', null);
        $dateFrom = $request->get('dateFrom', null);

        $dateTo = $dateTo === null ? Carbon::now() : Carbon::createFromFormat('Y-m-d', $dateTo);
        $dateFrom = $dateFrom === null ?
                    Carbon::now()->subDays(TransactionService::RECENT_TRANSACTION_AGE_IN_DAYS) :
                    Carbon::createFromFormat('Y-m-d', $dateFrom);

        $orderStatuses = $this->container->get('yilinker_core.service.transaction')
                              ->getOrderStatusesValid();
        $sellerOrders = $orderRepository->getTransactionOrderBySeller(
            $authenticatedUser->getUserId(), $dateFrom, $dateTo, $orderStatuses
        );

        $confirmedTransactionCountPerDay = $this->get('yilinker_core.service.transaction')
                                                ->getCountConfirmedSellerTransactionPerDay(
                                                    $authenticatedUser->getUserId(),
                                                    $dateFrom,
                                                    $dateTo
                                                );

        $cancelledTransactionCountPerDay = $this->get('yilinker_core.service.transaction')
                                                ->getCountCancelledSellerTransactionPerDay(
                                                    $authenticatedUser->getUserId(),
                                                    $dateFrom,
                                                    $dateTo
                                                );

        if($store->isAffiliate()){
            $vieweableProductStatuses = $this->get('yilinker_core.service.product.product')
                                             ->viewableAffiliateProductStatuses();
        }
        else{
            $vieweableProductStatuses = $this->get('yilinker_core.service.product.product')
                                             ->viewableSellerProductStatuses();
        }

        $productCount = $em->getRepository("YilinkerCoreBundle:User")
                           ->getUserUploadCount($authenticatedUser->getUserId(), null, null, $vieweableProductStatuses);
        $saleableOrderStatuses  = $this->get('yilinker_core.service.transaction')
                                       ->getOrderStatusesValid();

        $totalOrderCount = $em->getRepository('YilinkerCoreBundle:UserOrder')
                              ->getNumberOfOrdersBy(
                                  $authenticatedUser->getUserId(), null,
                                  null, null, null,
                                  $saleableOrderStatuses
                              );
        $totalSales = $this->get('yilinker_core.service.transaction')
                           ->getSellerTotalSales( $authenticatedUser->getUserId());
        $totalNetSales = $this->get('yilinker_core.service.transaction')
                           ->getSellerTotalNetSales( $authenticatedUser->getUserId());


        $transactionGraphData = array();
        $dayDifference = $dateFrom->diffInDays($dateTo);
        for($i = 0; $i <= $dayDifference; $i++){
            $dateFromCopy = Carbon::createFromFormat('Y-m-d', $dateFrom->format('Y-m-d'));
            $date =  $dateFromCopy->addDays($i);
            $formattedDate = $date->format("Y-m-d");
            $transactionGraphData[$i] = array(
                'date' => $date->format("d M"),
                'confirmedTransactions' => isset($confirmedTransactionCountPerDay[$formattedDate]) ?
                                           (int) $confirmedTransactionCountPerDay[$formattedDate]['numberOfOrders'] :
                                           0,
                'canceledTransactions' => isset($cancelledTransactionCountPerDay[$formattedDate]) ?
                                           (int) $cancelledTransactionCountPerDay[$formattedDate]['numberOfOrders'] :
                                           0,
            );
        }

        $dateFilters = array(
            array(
                'label' => 'Weekly',
                'from'  => Carbon::now()->startOfWeek(),
                'to'    => Carbon::now()->endOfWeek(),
            ),
            array(
                'label' => 'Monthly',
                'from'  => Carbon::now()->startOfMonth(),
                'to'    => Carbon::now()->endOfMonth(),
            ),
        );

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();
        $totalEarning = $store->service->getTotalEarning();
        $availableBalance = $store->service->getAvailableBalance();

        if (!($store->getStoreLevel() instanceof StoreLevel)) {
            $storeLevelSilver = $em->getReference('YilinkerCoreBundle:StoreLevel', StoreLevel::STORE_LEVEL_SILVER);
            $store->setStoreLevel($storeLevelSilver);
            $em->flush();
        }

        $storeLevels = $storeService->getStoreLevel($store->getStoreLevel()->getStoreLevelId());

        $tbEarning = $em->getRepository('YilinkerCoreBundle:Earning');
        $earnings = $tbEarning->getOfStore($store, array('order' => array('dateLastModified'=> 'desc')), 1);
        $totalAffiliateNetwork = $tbEarning->getStoreTotal($authenticatedUser->getStore(), array(
            'status' => array(Earning::COMPLETE, Earning::TENTATIVE),
            'type' => array(EarningType::AFFILIATE_TRANSACTION, EarningType::AFFILIATE_REGISTRATION)
        ));
        $totalBuyerNetwork = $tbEarning->getStoreTotal($authenticatedUser->getStore(), array(
            'status' => array(Earning::COMPLETE, Earning::TENTATIVE),
            'type' => array(EarningType::BUYER_REGISTRATION, EarningType::BUYER_TRANSACTION)
        ));

        $userReferralRepository = $em->getRepository('YilinkerCoreBundle:UserReferral');
        $totalAffiliateReferred = $userReferralRepository->qb()
                                                         ->filterByQuery(array(
                                                            'referrer' => $authenticatedUser
                                                         ))
                                                         ->filterByUserType(true, User::USER_TYPE_SELLER, Store::STORE_TYPE_RESELLER)
                                                         ->getCount();

        $totalBuyerReferred = $userReferralRepository->qb()
                                                     ->filterByQuery(array(
                                                        'referrer' => $authenticatedUser
                                                     ))
                                                     ->filterByUserType(true, User::USER_TYPE_BUYER)
                                                     ->getCount();

        $successfulTransaction = $orderProductRepository->getTotalSuccessTransaction($authenticatedUser);

        $totalEarningOnComment = $tbEarning->getStoreTotal($authenticatedUser->getStore(), array(
            'status' => Earning::COMPLETE,
            'type' => array(EarningType::COMMENT)
        ));

        $totalEarningOnFollow = $tbEarning->getStoreTotal($authenticatedUser->getStore(), array(
            'status' => Earning::COMPLETE,
            'type' => array(EarningType::FOLLOW)
        ));
        $commentReceived = $productReviewRepository->getTotalReviewReceived($authenticatedUser);

        $supportMobile = $this->getParameter("support_contact_number");

        $overViewData = array (
            'baseUri'                => $baseUri,
            'dateFrom'               => $dateFrom,
            'dateTo'                 => $dateTo,
            'seller'                 => $authenticatedUser,
            'sellerOrders'           => $sellerOrders,
            'productCount'           => $productCount,
            'totalTransactionCount'  => $totalOrderCount,
            'totalSales'             => $totalSales,
            'transactionGraphData'   => $transactionGraphData,
            'dateFilters'            => $dateFilters,
            'totalEarning'           => $totalEarning,
            'availableBalance'       => $availableBalance,
            'storeLevels'            => $storeLevels,
            'earnings'               => $earnings,
            'totalAffiliateNetwork'  => $totalAffiliateNetwork,
            'totalBuyerNetwork'      => $totalBuyerNetwork,
            'totalAffiliateReferred' => $totalAffiliateReferred,
            'totalBuyerReferred'     => $totalBuyerReferred,
            'successfulTransaction'  => $successfulTransaction,
            'commentTable'           => $em->getRepository('YilinkerCoreBundle:EarningTypeRange')->findByEarningType(EarningType::COMMENT),
            'totalEarningOnComment'  => $totalEarningOnComment,
            'followTable'            => $em->getRepository('YilinkerCoreBundle:EarningTypeRange')->findByEarningType(EarningType::FOLLOW),
            'totalEarningOnFollow'   => $totalEarningOnFollow,
            'commentReceived'        => $commentReceived,
            'totalNetSales'          => $totalNetSales,
            'supportMobile'          => $supportMobile
        );

        if ($authenticatedUser->getStore()->getStoreType()) {
            return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_overview_affiliate.html.twig', $overViewData);
        }

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_overview.html.twig', $overViewData);
    }


    /**
     * Render Dashboard Transaction Markup
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardTransactionAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getUser();

        $dateToFilters = array(
            'week' => Carbon::now()->startOfWeek()->toDateString('Y-m-d'),
            'month' => Carbon::now()->startOfMonth()->toDateString('Y-m-d'),
            'year' => Carbon::now()->startOfYear()->toDateString('Y-m-d'),
        );

        $page = (int) $request->get('page', 1);
        $dateFrom = $request->get('dateFrom', $dateToFilters['year']);
        $dateTo = $request->get('dateTo', Carbon::now()->toDateString('Y-m-d'));
        $paymentMethod = $request->get('paymentMethod', null);
        $paymentMethod = $paymentMethod === "" ? null : $paymentMethod;

        if($dateFrom){
            $dateFrom = Carbon::createFromFormat("Y-m-d", $dateFrom)->startOfDay();
        }
        if($dateTo){
            $dateTo = Carbon::createFromFormat("Y-m-d", $dateTo)->endOfDay();
        }

        $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $tbUserOrder
            ->searchBy(array(
                'dateAdded.from' => $dateFrom,
                'dateAdded.to'   => $dateTo,
                'tab'            => $request->get('tab'),
                'paymentMethod'  => $paymentMethod
            ))
            ->notFlagged()
            ->withSeller($authenticatedUser)
            ->orderBy('this.dateAdded', 'DESC')
            ->setLimit(self::ORDER_PER_PAGE)
            ->page($page)
        ;
        $orders = $tbUserOrder->getResult();
        
        $paymentMethods = $em->getRepository('YilinkerCoreBundle:PaymentMethod')
                             ->findAll();

        $dateFilters = array(
            'dateFrom' => $dateToFilters,
            'dateTo' => Carbon::now()->toDateString('Y-m-d'),
        );

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_transaction.html.twig', array(
            'seller' => $authenticatedUser,
            'orders' => $orders,
            'totalTransactionCount' => $tbUserOrder->getCount(),
            'perPage' => $tbUserOrder->getQB()->getMaxResults(),
            'paymentMethods' => $paymentMethods,
            'dateFilters' => $dateFilters,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
        ));
    }


    /**
     * Render Dashboard Transaction View Markup
     *
     * @param string $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardTransactionViewAction($invoice)
    {
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getUser();

        $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $order = $tbUserOrder
            ->searchBy(array(
                'invoiceNumber' => $invoice,
                'tab'           => 'valid'
            ))
            ->notFlagged()
            ->withSeller($authenticatedUser)
            ->getSingleResult()
        ;
        $this->throwNotFoundUnless($order, 'Invoice not found');

        return $this->render(
            'YilinkerMerchantBundle:Dashboard:dashboard_transaction_view.html.twig',
            compact('order')
        );
    }

    /**
     * Cancel transaction
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardTransactionCancellationAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );

        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $this->getUser();
        $orderProductIds = $request->get('orderProducts');
        $em = $this->getDoctrine()->getEntityManager();
        $orderProducts = $em->getRepository("YilinkerCoreBundle:OrderProduct")
                            ->getSellerOrderProductsByIds(
                                $authenticatedUser, $orderProductIds
                            );

        $form = $this->createForm('order_product_cancellation', null, array(
            'orderProducts' => $orderProducts,
            'userCancellationType' => OrderProductCancellationReason::USER_TYPE_SELLER
        ));
        $form->submit(array(
            '_token'        => $request->get('_token'),
            'orderProducts' => $request->get('orderProducts'),
            'reason'        => $request->get('reason'),
            'remark'        => $request->get('remark'),
        ));

        if ($form->isValid()) {
            $formData = $form->getData();
            $invoices = array();
            $hasSellerMismatch = false;
            foreach($formData['orderProducts'] as $key => $orderProduct){
                $order = $orderProduct->getOrder();
                $invoices[$order->getInvoiceNumber()] = $order;
                if($orderProduct->getSeller()->getUserId() !== $authenticatedUser->getUserId()){
                    $hasSellerMismatch = true;
                    break;
                }
            }

            if($hasSellerMismatch){
                $response['message'] = "Some items do not belongs to the authenticated user";
            }
            else{
                if(count($invoices) === 1){
                    $transactionService = $this->container->get('yilinker_core.service.transaction');
                    $response['isSuccessful'] = $transactionService->cancellationRequestTransactionByUser(
                        $formData['orderProducts'],
                        $formData['reason'],
                        $formData['remark'],
                        $authenticatedUser
                    );
                    if($response['isSuccessful']){
                        $response['message'] = "Cancellation request successfuly sent.";
                    }
                }
                else{
                    $response['message'] = 'Items do not belong to the same order';
                }
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }


    /**
     * Render Dashboard Followers Page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardFollowersAction (Request $request)
    {
        $page = $request->query->get('page', 1) - 1;
        $pageLimit = self::FOLLOWER_PAGE_LIMIT;
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $userFollowRepository = $em->getRepository('YilinkerCoreBundle:UserFollow');
        $followers = $userFollowRepository->getFollowers($authenticatedUser, $page, $pageLimit);
        $userFollowManager = $this->get('yilinker_merchant.service.user.user_follow');
        $followersContainer = $userFollowManager->constructUser($followers);
        $data = compact (
            'followersContainer'
        );

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_followers.html.twig', $data);
    }

    /**
     * Render Dashboard Activity Log Markup
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardActivityLogAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $tbUserActivities = $em->getRepository('YilinkerCoreBundle:UserActivityHistory');
        $user = $this->getUser();
        $timeline = $tbUserActivities->getTimelinedActivities($user->getId());
        $data = compact('timeline');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_activity_log.html.twig', $data);
    }

    /**
     * Render Dashboard My Points Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardMyPointsAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $user = $this->getUser();
        $page = $request->get('page', 1);
        if($page > 0){
            $page--;
        }
        $offset = $page * self::POINT_ENTRIES_PER_PAGE;

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_my_points.html.twig', array(
            'userPointEntries' => array(),
            'perPage' => self::POINT_ENTRIES_PER_PAGE,
            'totalEntries' => 0,
        ));
    }

    /**
     * Render Dashboard Settings Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardSettingsAction()
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $em = $this->getDoctrine()->getEntityManager();

        $smsSubcription = $em->getRepository('YilinkerCoreBundle:SmsNewsletterSubscription')
                             ->findOneBy(array(
                                 'userId'   => $authenticatedUser->getUserId(),
                                 'isActive' => true,
                             ));

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_settings.html.twig', array(
            'isSubscribed' => $smsSubcription !== null,
        ));
    }

    /**
     * Render Dashboard Account Information Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardMessagesAction(Request $request, $userId = null)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $messageService = $this->get('yilinker_core.service.message.chat');

        $page = 1;
        $limit = 10;

        $messageService->setAuthenticatedUser($authenticatedUser);
        $messages = $messageService->getConversationHead($limit, $page);

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_messages.html.twig', compact('messages', 'userId'));
    }

    /**
     * Render Resolution Center
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardResolutionCenterAction(Request $request)
    {
        $userEntity = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $disputeStatusType = $request->query->get('disputeStatusType', null);
        $searchKeyword = $request->query->get('searchKeyword', null);
        $page = (int) $request->query->get('page', 1);
        $pageLimit = self::DISPUTE_CASE_PER_PAGE;
        $validOrderStatuses = $em->getRepository('YilinkerCoreBundle:Dispute')->getValidStatusesForDisputeSeller();
        $userOrderEntity = $em->getRepository('YilinkerCoreBundle:UserOrder')
                              ->getTransactionOrderBySeller (
                                  $userEntity->getUserId(),
                                  null,
                                  null,
                                  $validOrderStatuses,
                                  null,
                                  null, null, null,
                                  0,
                                  PHP_INT_MAX,
                                  OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER
                              );
        $disputeStatuses = array (
            array (
                'status' => 'Refund',
                'orderProductStatusId' => OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED,
            ),
            array (
                'status' => 'Replacement',
                'orderProductStatusId' => OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED,
            )
        );
        $disputeArray = $this->get('yilinker_core.service.dispute_manager')
                             ->getCaseWithDetail (
                                 $userEntity,
                                 $disputeStatusType,
                                 null,
                                 $searchKeyword,
                                 $page,
                                 $pageLimit
                             );
        $disputeContainer = $disputeArray['cases'];
        $disputeCount = $disputeArray['count'];
        $disputeTypeStatuses = $em->getRepository('YilinkerCoreBundle:DisputeStatusType')->findAll();
        $disputeManager = $this->get('yilinker_core.service.dispute_manager');

        $reasonsForRefund = $disputeManager->getOrderProductReasonByType (
                                                 OrderProductCancellationReason::REASON_TYPE_REFUND,
                                                 OrderProductCancellationReason::USER_TYPE_SELLER
                                             );
        $reasonsForReplacement = $disputeManager->getOrderProductReasonByType (
                                                      OrderProductCancellationReason::REASON_TYPE_REPLACEMENT,
                                                      OrderProductCancellationReason::USER_TYPE_SELLER
                                                  );

        $data = compact (
            'userOrderEntity',
            'disputeStatuses',
            'disputeContainer',
            'disputeTypeStatuses',
            'reasonsForReplacement',
            'reasonsForRefund',
            'pageLimit',
            'disputeCount'
        );

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_resolution_center.html.twig', $data);
    }

    /**
     * Add Dispute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addCaseAction (Request $request)
    {
        $description = $request->request->get('title', null);
        $message = $request->request->get('remarks', null);
        $orderProductStatus = $request->request->get('orderProductStatus', null);
        $orderProductIds = $request->request->get('orderProductIds', array());
        $orderProductReasonId = $request->request->get('reasonId', null);
        $csrfToken = $request->request->get('csrfToken', null);
        $transactionNumber = $request->get('transactionNumber', null);
        $em = $this->getDoctrine()->getManager();
        $userEntity = $this->getUser();
        $validOrderStatuses = $em->getRepository('YilinkerCoreBundle:Dispute')->getValidStatusesForDisputeSeller();
        $userOrderEntity = $em->getRepository('YilinkerCoreBundle:UserOrder')
                              ->getTransactionOrderBySeller (
                                  $userEntity->getUserId(),
                                  null,
                                  null,
                                  $validOrderStatuses,
                                  null,
                                  null, null, null,
                                  0,
                                  PHP_INT_MAX,
                                  OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER
                              );
        $formData = array (
            'description' => $description,
            'message' => $message,
            'orderProductStatus' => $orderProductStatus,
            'orderProductIds' => $orderProductIds,
            'orderProductCancellationReasonId' => $orderProductReasonId,
            '_token' => $csrfToken
        );
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->createForm('create_new_case', null);
        $form->submit($formData);
        $responseMessage = null;
        $isSuccessful = false;
        $validOrderProductStatus = array(
            OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED => '',
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED => ''
        );
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $isMerchant = $em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($authenticatedUser);

        if ($isMerchant instanceof  Store && (int) $isMerchant->getStoreType() === Store::STORE_TYPE_RESELLER) {
            $responseMessage = 'Invalid Access';
        }
        else if (!$form->isValid()) {
            $responseMessage = implode($formErrorService->throwInvalidFields($form), ' \n');
        }
        else if (count($userOrderEntity) == 0 || !in_array($transactionNumber, $userOrderEntity)) {
            $responseMessage = 'Invalid transaction number/Invoice number';
        }
        else if (sizeof($orderProductIds) === 0) {
            $responseMessage = 'Invalid Order Product';
        }
        else if (!isset($validOrderProductStatus[$orderProductStatus])) {
            $responseMessage = 'Invalid Order Product Status';
        }
        else {
            $isSuccessful = true;
            $em = $this->getDoctrine()->getManager();

            $orderProductEntities = $em->getRepository('YilinkerCoreBundle:OrderProduct')->findByOrderProductId($orderProductIds);
            $disputeManager = $this->get('yilinker_core.service.dispute_manager');
            $disputeManager->addNewCase (
                                 $orderProductEntities,
                                 $this->getUser(),
                                 $description,
                                 $message,
                                 $orderProductStatus,
                                 $orderProductReasonId
                             );
        }

        return new JsonResponse(compact('responseMessage', 'isSuccessful'));
    }

    /**
     * Get Order Products by orderId
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderProductAction (Request $request)
    {
        $orderId = (int) $request->query->get('orderId', 0);
        $em = $this->getDoctrine()->getManager();
        $orderProducts = array();
        $userEntity = $this->getUser();

        if ($orderId !== 0) {
            $orderProducts = $em->getRepository('YilinkerCoreBundle:UserOrder')
                                ->getTransactionOrderProducts (
                                    $orderId,
                                    null,
                                    $userEntity->getUserId(),
                                    null,
                                    OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER
                                );
        }

        return new JsonResponse(compact('orderProducts'));
    }

    /**
     * Schedule pickup
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function scheduleOrderProductPickupAction(Request $request)
    {
        $response = array(
            'data' => array(),
            'isSuccessful' => false,
            'message' => '',
        );

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $currentDatetime = new \DateTime();
        $currentDatetime->modify('+1 day');
        $formData = array (
            //'pickupDatetime' => $request->get('datetime'),
            'pickupDatetime' => $currentDatetime->format('Y-m-d H:i:s'),
            'pickupRemark' => $request->get('remark'),
            'orderProducts' => $request->get('orderProductIds'),
            '_token' => $request->get('_token'),
        );

        $form = $this->createForm('core_product_pickup');
        $form->submit($formData);
        if($form->isValid()){
            $formData = $form->getData();
            $tokenStorage = $this->container->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();

            if($authenticatedUser->getDefaultAddress()){
                $response = $this->container->get('yilinker_core.logistics.yilinker.express')
                                 ->schedulePickup(
                                     $formData['pickupDatetime'],
                                     $formData['pickupRemark'],
                                     $formData['orderProducts'],
                                     $authenticatedUser
                                 );
            }
            else{
                $response['message'] = 'You must have a default address to schedule a product pickup.';
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * Render Dashboard Help Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardHelpAction()
    {
        $frontendHostname = $this->getParameter("frontend_hostname");
        $merchantHostname = $this->getParameter("merchant_hostname");
        $supportMobile = $this->getParameter("support_contact_number");
        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_help.html.twig',
            compact(
                "frontendHostname",
                "merchantHostname",
                "supportMobile"
            )
        );
    }

    /**
     * Render Dashboard Earnings Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardEarningsAction(Request $request)
    {
        $startdate = $request->get('startdate', Carbon::now()->subWeek());
        $enddate = $request->get('enddate', Carbon::now());
        if (!$startdate instanceof Carbon) {
            $startdate = Carbon::createFromFormat('m/d/Y', $startdate);
        }
        if (!$enddate instanceof Carbon) {
            $enddate = Carbon::createFromFormat('m/d/Y', $enddate);
        }
        $page = $request->get('page', 1);

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();

        $em = $this->getDoctrine()->getEntityManager();
        $tbEarning = $em->getRepository('YilinkerCoreBundle:Earning');
        $order = array('dateLastModified'=> 'desc');
        $filter = compact('startdate', 'enddate', 'order');
        $earnings = $tbEarning->getOfStore($store, $filter, $page);
        $total = $tbEarning->ofStoreQB($store, $filter)->getCount();
        $earningGroups = $store->service->getEarningGroups($filter, false);
        $data = compact('store', 'earnings', 'total', 'filter', 'earningGroups');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_earnings.html.twig', $data);
    }

    /**
     * Render Dashboard Balance Withdrawal Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardBalanceWithdrawalAction(Request $request)
    {
        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();

        $form = $this->createForm('payout_request');

        $form->handleRequest($request);

        $accreditationStatus = $store->getAccreditationStatus();
        if($store->getStoreType() == Store::STORE_TYPE_MERCHANT){
            if (!$store->ableToWithdraw()) {
                $accreditationStatus = Store::ACCREDITATION_INCOMPLETE;
                $form->addError(new FormError('Please complete your bank information and wait to be accredited.'));
            }
            else{
                $accreditationStatus = Store::ACCREDITATION_COMPLETE;
            }
        }
        else{
            if (!$store->ableToWithdraw()) {
                $form->addError(new FormError('Please complete your bank information and wait to be accredited.'));
            }

            switch($store->getAccreditationStatus()){
                case Store::ACCREDITATION_WAITING:
                    $form->addError(new FormError('Your accreditation application is currently in progress. Please contact our customer service representative for assistance'));
                    break;
                case Store::ACCREDITATION_INCOMPLETE:
                    $form->addError(new FormError('Your accreditation application is incomplete. Please contact our customer service representative for assistance'));
                    break;
            }
        }

        if ($form->isValid()) {
            $payoutRequest = $form->getData();
            $em = $this->getDoctrine()->getEntityManager();
            $payoutRequest = $storeService->bindPayoutRequest($payoutRequest, $store);
            $em->persist($payoutRequest);
            $em->flush();
            $form = $this->createForm('payout_request');
        }

        $form = $form->createView();
        $data = compact('store', 'form', 'accreditationStatus');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_balance_withdrawal.html.twig', $data);
    }

    public function dashboardWithdrawalChargeAction(Request $request)
    {
        $requestAmount = $request->get('requestAmount');
        $payoutRequest = new PayoutRequest;
        $payoutRequest->setRequestedAmount($requestAmount);
        $data = compact('payoutRequest');

        return $this->render('YilinkerMerchantBundle:Dashboard/Partial:payout_request_bank_charge.html.twig', $data);
    }

    public function dashboardBalanceWithdrawalRequestListAction(Request $request)
    {
        $page = $request->get('page');

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();

        $em = $this->getDoctrine()->getEntityManager();
        $tbPayoutRequest = $em->getRepository('YilinkerCoreBundle:PayoutRequest');
        $payoutRequests = $tbPayoutRequest->getOfStore($store, $page);
        $totalRequests = $tbPayoutRequest->ofStoreQB($store)->getCount();
        $data = compact('payoutRequests', 'totalRequests');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_balance_withdrawal_request_list.html.twig', $data);
    }

    /**
     * Render Dashboard Balance Record
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardBalanceRecordAction(Request $request)
    {
        $page = $request->get('page');
        $form = $this->createForm('earning_filter');
        $form->handleRequest($request);
        $filter = $form->getData();
        $form = $form->createView();
        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();

        $em = $this->getDoctrine()->getEntityManager();
        $tbEarning = $em->getRepository('YilinkerCoreBundle:Earning');

        $earnings = $tbEarning->getOfStore($store, $filter, $page);
        $total = $tbEarning->ofStoreQB($store)->getCount();
        $data = compact('earnings', 'total', 'store', 'form');

        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_balance_record.html.twig', $data);
    }

    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }

    /**
     * Render Nnew affliate product selection
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardSelectProductAction()
    {
        $response = $this->render('YilinkerMerchantBundle:Dashboard:dashboard_select_product.html.twig');
        return $response;
    }

    /**
     * Render Nnew affliate product selection
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardLegalInfoAction()
    {
        $user = $this->getUser();
        $applicationManager = $this->get("yilinker_core.service.accreditation_application_manager");
        $applicationDetails = $applicationManager->getApplicationDetailsBySeller($user);

        return $this->render(
            "YilinkerMerchantBundle:User:legal_information.html.twig",
            compact("applicationDetails")
        );
    }
}
