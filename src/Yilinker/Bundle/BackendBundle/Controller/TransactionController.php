<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Doctrine\ORM\Query;

use Exception;
use Yilinker\Bundle\CoreBundle\Controller\Custom\CustomController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayoutDocument;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\PayoutDocument;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged;
use Yilinker\Bundle\CoreBundle\Entity\RefundNote;
use Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService;
use Yilinker\Bundle\CoreBundle\Entity\Currency;

/**
 * Class TransactionController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_EXPRESS_OPERATIONS') ")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class TransactionController extends Controller
{
    const PAGE_LIMIT = 30;

    const ACTION_BUYER_CANCEL_REQUEST = 1;

    const ACTION_CANCELLEABLE = 2;

    /**
     * Render Transaction list
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderTransactionListAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('m/d/y H:i:s');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('m/d/Y H:i:s');
        $searchKeyword = $request->query->get('searchKeyword', null);
        $orderStatus = $request->query->get('orderStatus', null);
        $paymentMethod = $request->query->get('paymentMethod', null);
        $dateFrom = $request->query->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->query->get('dateTo', $dateToCarbon);
        $hasAction = $request->query->get('hasAction', null);
        $page = $request->query->get('page', 1);
        $pageLimit = self::PAGE_LIMIT;
        $orderProductStatuses = null;

        if ((int) $hasAction === self::ACTION_CANCELLEABLE) {
            $orderProductStatuses = array (
                OrderProductStatus::PAYMENT_CONFIRMED,
                OrderProductStatus::STATUS_READY_FOR_PICKUP,
                OrderProductStatus::STATUS_PRODUCT_ON_DELIVERY,
            );
        }
        else if ((int) $hasAction === self::ACTION_BUYER_CANCEL_REQUEST) {
            $orderProductStatuses = array (
                OrderProductStatus::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY
            );
        }

        $em = $this->getDoctrine()->getManager();
        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $orderStatusRepository = $em->getRepository('YilinkerCoreBundle:OrderStatus');
        $paymentMethodRepository = $em->getRepository('YilinkerCoreBundle:PaymentMethod');
        $cancellationReasonRepository = $em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason');
        $orderStatusEntity = $orderStatusRepository->findAll();
        $paymentMethodEntity = $paymentMethodRepository->findAll();
        $transactions = $userOrderRepository->getTransactionOrder (
                                                 null,
                                                 $searchKeyword,
                                                 $orderStatus,
                                                 $paymentMethod,
                                                 $dateFrom,
                                                 $dateTo,
                                                 $this->getOffset($pageLimit, $page),
                                                 $pageLimit,
                                                 $orderProductStatuses
                                             );

        $transactionCount = $userOrderRepository->getTransactionOrderCount (
                                                      null,
                                                      $searchKeyword,
                                                      $orderStatus,
                                                      $paymentMethod,
                                                      $dateFrom,
                                                      $dateTo,
                                                      $orderProductStatuses
                                                  );

        $cancellationReason = $cancellationReasonRepository->findAll();

        $data = compact(
            'transactions',
            'orderStatusEntity',
            'paymentMethodEntity',
            'pageLimit',
            'transactionCount',
            'cancellationReason'
        );

        return $this->render('YilinkerBackendBundle:Transaction:transaction_list.html.twig', $data);
    }

    /**
     * Export Transaction Lst
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportAction(Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('Y-m-d');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('Y-m-d');
        $dateFrom = $request->query->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->query->get('dateTo', $dateToCarbon);

        $exporter = $this->get('yilinker_core.service.transaction.export');
        $filename = $exporter->export(array('datefrom' => $dateFrom, 'dateto' => $dateTo));

        return $this->redirect($this->getParameter('frontend_hostname'). '/assets/exported/'.$filename);
    }

    /**
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV')")
     */
    public function approveOrRejectOrderAction(Request $request)
    {
        $orderId = $request->get('orderId');
        $approve = $request->get('approve');
        $remarks = $request->get('remarks');

        $em = $this->getDoctrine()->getEntityManager();
        $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $userOrder = $tbUserOrder->find($orderId);
        if ($userOrder) {
            if ($approve == UserOrderFlagged::REJECT) {
                $orderStatusFraud = $em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_REJECTED_FOR_FRAUD);
                $userOrder->setOrderStatus($orderStatusFraud);
            }
            else{
                $notificationService = $this->get('yilinker_core.service.log.user.notification');
                $notificationService->setEntityManager($em);
                $notificationService->recordNotification($userOrder, 'UPDATE');
            }

            $orderFlagged = $userOrder->getUserOrderFlagged();
            $orderFlagged->setRemarks($remarks);
            $orderFlagged->setStatus($approve);
            $orderFlagged->setUser($this->getUser());

            $em->flush();
            $this->addFlash(
                'success',
                'Successfully '.$orderFlagged->getStatusTxt().' transaction with invoice number `'.$userOrder->getInvoiceNumber().'`'
            );
        }
        else {
            $this->addFlash(
                'error',
                'Transaction with order #'.$orderId.' does not exist'
            );
        }

        return $this->redirectBack();
    }

    /**
     * Get Transaction Order Details by Order ID
     * 
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactionOrderDetailAction (Request $request)
    {
        $orderId = $request->query->get('orderId');
        $em = $this->getDoctrine()->getManager();
        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $transactions = $userOrderRepository->getTransactionOrder (
                                                 $orderId,
                                                 null,
                                                 null,
                                                 null,
                                                 null,
                                                 null,
                                                 0,
                                                 PHP_INT_MAX
                                             );


        $orderEntity = $userOrderRepository->find($orderId);

        $transactions[0]['orignalPrice'] = $orderEntity->getOriginalPrice();
        $transactions[0]['voucherDeduction'] = $orderEntity->getVoucherDeduction();
        $transactions[0]['vouchers'] = array();
        foreach ($orderEntity->getOrderVouchers() as $key => $orderVoucher) {
            $transactions[0]['vouchers'][] = $orderVoucher->toArray();
            $transactions[0]['vouchers'][$key]['code'] = $orderVoucher->getVoucherCode()->getCode();
        }

        $orderProducts = $userOrderRepository->getTransactionOrderProducts($orderId);
        $listOfRemarks = $this->get('yilinker_backend.transaction_manager')
                              ->getRemarksByOrder($orderId);
        $cancellableOrderProductStatuses = $this->get('yilinker_core.service.transaction')
                                                ->getOrderProductAdminCancellable($orderId);

        $canCancel = false;
        $canApproveOrDeny = false;
        $cancelRequestByBuyerCount = 0;
        $cancellableOrderProductCount = 0;
        foreach ($orderProducts as &$userOrder) {

            $userOrder['canCancel'] = false;
            $userOrder['canApproveOrDeny'] = false;
            $sellerName = $userOrder['fullName'];
            $userOrder['fullName'] = $sellerName . " (".($userOrder['isAffiliate'] ? 'Affiliate': 'Seller') .")";
            $userOrder['productName'] = $userOrder['productName'] . " (".$userOrder['sku'].")";

            if (in_array((int) $userOrder['orderProductStatusId'], $cancellableOrderProductStatuses)) {
                $userOrder['canCancel'] = true;
                $cancellableOrderProductCount++;
            }

            if ((int) $userOrder['orderProductStatusId'] === OrderProductStatus::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY ) {
                $userOrder['canApproveOrDeny'] = true;
                $cancelRequestByBuyerCount ++;
            }

            if ($userOrder['attributes'] !== '' && $userOrder['attributes'] !== '[]' ) {
                $userOrder['attributes'] = json_decode($userOrder['attributes']);
            }
            else {
                $userOrder['attributes'] = null;
            }
        }

        $canApproveOrDeny = $cancelRequestByBuyerCount > 0;
        $canCancel = $cancellableOrderProductCount > 0 && $canApproveOrDeny === false;

        return new JsonResponse(compact(
            'transactions',
            'orderProducts',
            'canCancel',
            'canApproveOrDeny',
            'listOfRemarks'
        ));
    }

    /**
     * Cancel Order
     *
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV')")
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelTransactionAction (Request $request)
    {
        $isSuccessful = false;
        $orderProductIds = $request->request->get('orderProductIds', null);
        $reasonId = $request->request->get('reasonId', null);
        $remarks = $request->request->get('remarks', null);
        $em = $this->getDoctrine()->getManager();
        $orderProductRepository = $em->getRepository('YilinkerCoreBundle:OrderProduct');
        $transactionManager = $this->get('yilinker_core.service.transaction');
        $orderProductEntities = $orderProductRepository->findByOrderProductId($orderProductIds);
        $orderProductCancellationReasonReference = $em->getReference (
                                                            'YilinkerCoreBundle:OrderProductCancellationReason',
                                                            $reasonId
                                                        );

        if ($orderProductEntities) {
            $isSuccessful = $transactionManager->cancellationTransactionByAdmin (
                                                     $orderProductEntities,
                                                     $orderProductCancellationReasonReference,
                                                     $remarks,
                                                     $this->getUser()
                                                 );
        }

        return new JsonResponse($isSuccessful);
    }

    /**
     * Approve or Deny Cancelled Transaction
     *
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV')")
     * @param Request $request
     * @return JsonResponse
     */
    public function approveOrDenyCancelledTransactionAction (Request $request)
    {
        $orderProductIds = $request->request->get('orderProductIds');
        $isApprove = (bool) $request->request->get('isApprove');
        $remarks = $request->request->get('remarks');
        $em = $this->getDoctrine()->getManager();
        $orderProductRepository = $em->getRepository('YilinkerCoreBundle:OrderProduct');
        $orderProductEntities = $orderProductRepository->findByOrderProductId($orderProductIds);
        $transactionManager = $this->get('yilinker_core.service.transaction');
        $isSuccessful = $transactionManager->approveOrDenyCancelledTransaction(
                                                 $orderProductEntities,
                                                 $remarks,
                                                 $isApprove,
                                                 $this->getUser()
                                             );

        return new JsonResponse($isSuccessful);
    }

    /**
     * Get Order Product Details and history of updates with shipment info
     * 
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderProductDetailAndHistoryAction (Request $request)
    {
        $orderProductId = $request->query->get('orderProductId');
        $em = $this->getDoctrine()->getManager();
        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $orderProductHistoryRepository = $em->getRepository('YilinkerCoreBundle:OrderProductHistory');
        $packageRepository = $em->getRepository('YilinkerCoreBundle:Package');
        $packageDetailRepository = $em->getRepository('YilinkerCoreBundle:PackageDetail');
        $shipmentInformationEntities = $packageRepository->getPackagesByOrderProducts(array($orderProductId));
        $orderProduct = $userOrderRepository->getTransactionOrderProducts(null, $orderProductId);
        $orderProductHistoryEntities = $orderProductHistoryRepository->findByOrderProduct($orderProductId);
        $orderProductHistory = array();
        $shipmentInformation = array();

        if ($orderProductHistoryEntities) {

            foreach ($orderProductHistoryEntities as $orderProductHistoryEntity) {
                $orderProductHistory[] = array (
                    'historyId' => $orderProductHistoryEntity->getOrderProductHistoryId(),
                    'orderProductStatus' => $orderProductHistoryEntity->getOrderProductStatus()->getName(),
                    'dateAdded' => $orderProductHistoryEntity->getDateAdded()->format('Y/m/d H:i:s')
                );
            }

        }

        if ($shipmentInformationEntities) {

            foreach ($shipmentInformationEntities as $shipmentInformationEntity) {
                $packageOrderProduct = $packageDetailRepository->findOneByOrderProduct($orderProductId);
                $warehouse = $shipmentInformationEntity->getWarehouse();

                $shipmentInformation[] = array (
                    'waybillNumber' => $shipmentInformationEntity->getWaybillNumber(),
                    'warehouse' => $warehouse ? $warehouse->getName() : 'Not available',
                    'quantity' => $packageOrderProduct && $packageOrderProduct->getQuantity()
                                  ? $packageOrderProduct->getQuantity()
                                  : $packageOrderProduct->getOrderProduct()->getQuantity(),
                    'dateAdded' => $shipmentInformationEntity->getDateAdded()->format('Y/m/d H:i:s')
                );
            }

        }

        $data = compact (
            'orderProduct',
            'orderProductHistory',
            'shipmentInformation'
        );

        return new JsonResponse($data);
    }

    /**
     * Render Seller Payout
     *
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderSellerPayoutAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('m/d/Y H:i:s');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('m/d/Y H:i:s');
        $em = $this->getDoctrine()->getManager();
        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $searchKeyword = $request->query->get('searchKeyword', null);
        $dateFrom = $request->query->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->query->get('dateTo', $dateToCarbon);

        $sellerTypeId = (int) $request->query->get('sellerType', null) === 99 ? null : $request->query->get('sellerType');       
        $page = $request->query->get('page', 1) - 1;
        $pageLimit = self::PAGE_LIMIT;

        $daysElapsed = \Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService::PAYOUT_DAYS_ELAPSED;
        $sellerPayoutData = $userOrderRepository->getSellerPayoutList(
            $searchKeyword, $dateFrom, $dateTo, 
            $daysElapsed, $sellerTypeId, $page, $pageLimit
        );
        $sellerTypes = array (
            array (
                'id' => 99,
                'name' => 'All'
            ),
            array (
                'id' => Store::STORE_TYPE_MERCHANT,
                'name' => 'Seller'
            ),
            array (
                'id' => Store::STORE_TYPE_RESELLER,
                'name' => 'Affiliate'
            )
        );

        $data = compact (
            'sellerPayoutData',
            'pageLimit',
            'sellerTypes'
        );

        return $this->render('YilinkerBackendBundle:Transaction:seller_payout.html.twig', $data);
    }

    /**
     * Render Seller Payout History
     *
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderSellerPayoutHistoryAction (Request $request)
    {
        $searchKeyword = $request->query->get('searchKeyword', null);
        $dateFrom = $request->query->get('dateFrom', null);
        $dateTo = $request->query->get('dateTo', null);
        $page = $request->query->get('page', 1);
        $sellerTypeId = (int) $request->query->get('sellerType', null) === 99 ? null : (int) $request->query->get('sellerType');

        $pageLimit = self::PAGE_LIMIT;
        $offset = $this->getOffset($pageLimit, $page);

        if(!is_null($dateFrom)){
            try{
              $dateFrom = Carbon::createFromFormat("m/d/Y", $dateFrom)->startOfDay();
            }
            catch(Exception $e){
              $dateFrom = Carbon::now()->addMonth(-1)->format('m/d/Y');
            }
        }
        else{
            $dateFrom = Carbon::now()->startOfDay();
        }

        if(!is_null($dateTo)){
            try{
              $dateTo = Carbon::createFromFormat("m/d/Y", $dateTo)->endOfDay();
            }
            catch(Exception $e){
              $dateTo = Carbon::now()->addDays(1)->format('m/d/Y');
            }
        }
        else{
            $dateTo = Carbon::now()->addMonth()->endOfDay();
        }

        $transactionManager = $this->get('yilinker_core.service.transaction');
        $sellerPayoutData = $transactionManager->getPayoutHistory($searchKeyword, $dateFrom, $dateTo, $sellerTypeId, $pageLimit, $offset);

        $sellerTypes = array (
            array (
                'id' => 99,
                'name' => 'All'
            ),
            array (
                'id' => Store::STORE_TYPE_MERCHANT,
                'name' => 'Seller'
            ),
            array (
                'id' => Store::STORE_TYPE_RESELLER,
                'name' => 'Affiliate'
            )
        );

        $data = compact (
            'sellerPayoutData',
            'pageLimit',
            'sellerTypes'
        );

        return $this->render('YilinkerBackendBundle:Transaction:seller_payout.html.twig', $data);
    }

    /**
     * Render Manufacturer Payout list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function renderManufacturerPayoutAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('m/d/Y H:i:s');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('m/d/Y H:i:s');
        $em = $this->getDoctrine()->getManager();
        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $searchKeyword = $request->query->get('searchKeyword', null);
        $dateFrom = $request->query->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->query->get('dateTo', $dateToCarbon);
        $page = $request->query->get('page', 1);
        $pageLimit = self::PAGE_LIMIT;
        $offset = $this->getOffset($pageLimit, $page);

        $daysElapsed = \Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService::PAYOUT_DAYS_ELAPSED;
        $manufacturerPayoutData = $userOrderRepository->getManufacturerPayoutList (
                                                            $searchKeyword,
                                                            $dateFrom,
                                                            $dateTo,
                                                            $daysElapsed,
                                                            $offset,
                                                            $pageLimit
                                                        );

        $data = compact (
            'manufacturerPayoutData',
            'pageLimit'
        );

        return $this->render('YilinkerBackendBundle:Transaction:manufacturer_payout.html.twig', $data);
    }

    /**
     * Render Manufacturer Payout History
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderManufacturerPayoutHistoryAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('m/d/Y');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('m/d/Y');
        $searchKeyword = $request->query->get('searchKeyword', null);
        $dateFrom = $request->query->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->query->get('dateTo', $dateToCarbon);
        $page = $request->query->get('page', 1);

        $pageLimit = self::PAGE_LIMIT;
        $offset = $this->getOffset($pageLimit, $page);

        if (!is_null($dateFrom)) {
            $dateFrom = Carbon::createFromFormat("m/d/Y", $dateFrom)->startOfDay();
        } else {
            $dateFrom = Carbon::now()->startOfDay();
        }

        if (!is_null($dateTo)) {
            $dateTo = Carbon::createFromFormat("m/d/Y", $dateTo)->endOfDay();
        } else {
            $dateTo = Carbon::now()->addMonth()->endOfDay();
        }

        $transactionManager = $this->get('yilinker_core.service.transaction');
        $manufacturerPayoutData = $transactionManager->getManufacturerPayoutHistory (
                                                            $searchKeyword,
                                                            $dateFrom,
                                                            $dateTo,
                                                            null,
                                                            $pageLimit,
                                                            $offset
                                                        );

        $data = compact (
            'manufacturerPayoutData',
            'pageLimit'
        );

        return $this->render('YilinkerBackendBundle:Transaction:manufacturer_payout.html.twig', $data);
    }

    /**
     * Get Order Product by seller id
     * 
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_MARKETING') or has_role('ROLE_BUSINESS_DEV')")
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransactionOrderProductAction (Request $request)
    {
        $sellerId = $request->query->get('sellerId', null);
        $buyerId = $request->query->get('buyerId', null);
        $orderProductIds = $request->query->get('orderProductIds', null);
        $orderProductStatusIds = $request->query->get('orderProductStatusIds', null);
        $transactionManager = $this->get('yilinker_core.service.transaction');

        $orderProducts = $transactionManager->getTransactionOrderProducts(null, $orderProductIds, $sellerId, $buyerId, $orderProductStatusIds);

        return new JsonResponse($orderProducts);
    }

    /**
     * Change OrderProduct status to Seller Payment Released
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function proceedToManufacturerPaymentAction (Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => '',
        );

        $form = $this->createForm('backend_payout_form');
        $form->submit(array(
            'orderProductIds' => $request->get('orderProductIds'),
            'depositSlips'    => $request->files->get('depositSlips'),
            '_token'          => $request->get('_token'),
        ));

        if ($form->isValid()) {
            $transactionManager = $this->get('yilinker_core.service.transaction');
            $tokenStorage = $this->container->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();
            $em = $this->getDoctrine()->getManager();
            $formData = $form->getData();

            $payoutResult = $transactionManager->manufacturerPayout ($formData['orderProductIds'], $authenticatedUser);
            $response['isSuccessful'] = $payoutResult['isSuccessful'];

            if ($response['isSuccessful'] && $formData['depositSlips']) {

                foreach($payoutResult['data'] as $payout) {

                    foreach($formData['depositSlips'] as $imageFile) {
                        $payoutDocument = new ManufacturerPayoutDocument();
                        $payoutDocument->setManufacturerPayout($payout);
                        $payoutDocument->setFile($imageFile);
                        $payoutDocument->setDateAdded(new \DateTime());
                        $em->persist($payoutDocument);
                    }

                }

                $em->flush();

            }

        } else {
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * Get Buyer refund Order Product by seller id
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBuyerRefundOrderProductAction (Request $request)
    {
        $disputeId = $request->get('disputeId', '');
        $sellerId = $request->query->get('sellerId', null);
        $buyerId = $request->query->get('buyerId', null);
        $orderProductIds = $request->query->get('orderProductIds', null);
        $orderProductStatusIds = TransactionService::getRefundOrderProductStatus();
        $assetHelper = $this->get('templating.helper.assets');

        $defaultImage = $assetHelper->getUrl('/images/logo-icon.png');

        $em = $this->getDoctrine()->getManager();
        $transactionService = $this->get('yilinker_core.service.transaction');

        $orderProducts = $transactionService->getBuyerRefundOrderProducts(null, $orderProductIds, $sellerId, $buyerId, $orderProductStatusIds);

        $disputeMessages = $em->getRepository('YilinkerCoreBundle:DisputeMessage')
                              ->findByDispute($disputeId);

        $disputeRemarks = array();
        foreach ($disputeMessages as $disputeMessage) {
            $disputer = $disputeMessage->getDispute()->getDisputer();
            $author = $disputeMessage->getAuthor();
            $authorName = $author ? $author->getFullName()
                                  : $disputer->getFullName();

            $disputeRemarks[] = array(
                'authorName' => $authorName,
                'message' => $disputeMessage->getMessage(),
                'dateAdded' => $disputeMessage->getDateAdded()->format('Y/m/d h:i A'),
                'isAdmin' => $disputeMessage->getIsAdmin(),
                'image' => $defaultImage,
            );
        }

        return new JsonResponse(compact('orderProducts', 'disputeRemarks'));
    }

    /**
     * Change OrderProduct status to Seller Payment Released
     *
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @param Request $request
     * @return JsonResponse
     */
    public function proceedToPaymentAction (Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => '',
        );

        $form = $this->createForm('backend_payout_form');
        $form->submit(array(
            'orderProductIds' => $request->get('orderProductIds'),
            'depositSlips' => $request->files->get('depositSlips'),
            '_token' => $request->get('_token'),
        ));

        if($form->isValid()){
            $transactionManager = $this->get('yilinker_core.service.transaction');
            $tokenStorage = $this->container->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();
            $em = $this->getDoctrine()->getManager();
            $formData = $form->getData();

            $payoutResult = $transactionManager->updateStatusToPaymentReleased($formData['orderProductIds'], $authenticatedUser);
            $response['isSuccessful'] = $payoutResult['isSuccessful'];
            if($response['isSuccessful']){
                if($formData['depositSlips']){
                    foreach($payoutResult['data'] as $payout){                
                        foreach($formData['depositSlips'] as $imageFile){                    
                            $payoutDocument = new PayoutDocument();
                            $payoutDocument->setPayout($payout);
                            $payoutDocument->setFile($imageFile);
                            $payoutDocument->setDateAdded(new \DateTime());
                            $em->persist($payoutDocument);
                        }
                    }
                    $em->flush();
                }
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * Render Buyer Refund Page
     * 
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBuyerRefundAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->format('m/d/Y');
        $dateToCarbon = Carbon::now()->addDays(1)->format('m/d/Y');

        $em = $this->getDoctrine()->getManager();

        $userOrderRepository = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $refundsQuery = $userOrderRepository->getBuyerRefundList()
                                            ->filterBy(array(
                                                'name' => $request->get('searchKeyword', ''),
                                                'dateFrom' => $request->get('dateFrom', $dateFromCarbon),
                                                'dateTo' => $request->get('dateTo', $dateToCarbon)
                                            ));

        $refundCount = count($refundsQuery->getResult(Query::HYDRATE_SCALAR));

        $refunds = $refundsQuery->select(array(
                                   'Dispute.disputeId',
                                   'Dispute.ticket',
                                   'this.orderId',
                                   'this.invoiceNumber',
                                   'Dispute.disputeId',
                                   'GROUP_CONCAT(OrderProduct.orderProductId) as orderProduct',
                                   'User.userId as buyerUserId',
                                   'User.firstName as buyerFirstName',
                                   'User.lastName as buyerLastName',
                                   'User.email as buyerEmail',
                                   'User.contactNumber as buyerContactNumber'
                                ))
                                ->page($request->get('page', 1))
                                ->getResult(Query::HYDRATE_ARRAY);

        return $this->render('YilinkerBackendBundle:Transaction:buyer_refund.html.twig', array(
            'refunds' => $refunds,
            'refundCount' => $refundCount,
            'pageLimit' => self::PAGE_LIMIT
        ));
    }

    /**
     * Change OrderProduct status to Buyer Refund Released
     *
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @param Request $request
     * @return JsonResponse
     */
    public function proceedToRefundAction (Request $request)
    {
        $isSuccessful = false;
        $payoutId = null;
        
        $form = $this->createForm('backend_payout_form');
        $form->submit(array(
            'orderProductIds' => $request->get('orderProductIds'),
            'depositSlips' => $request->files->get('depositSlips'),
            '_token' => $request->get('_token'),
        ));
        $disputeId = $request->get('disputeId');
        $remark = $request->get('remark');

        if($form->isValid()){

            $em = $this->getDoctrine()->getManager();
            $orderProductRepository = $em->getRepository('YilinkerCoreBundle:OrderProduct');
            $transactionManager = $this->get('yilinker_core.service.transaction');
            $tokenStorage = $this->container->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();
            $formData = $form->getData();
            
            $payoutResult = $transactionManager->updateStatusToRefundReleased(
                $formData['orderProductIds'],
                $authenticatedUser,
                Currency::CURRENCY_PH_PESO,
                $disputeId
            );
            $isSuccessful = $payoutResult['isSuccessful'];
            if($isSuccessful){
                foreach($payoutResult['data'] as $payout){
                    if($formData['depositSlips']){
                        foreach ($formData['depositSlips'] as $imageFile) {
                            $payoutDocument = new PayoutDocument();
                            $payoutDocument->setPayout($payout);
                            $payoutDocument->setFile($imageFile);
                            $payoutDocument->setDateAdded(new \DateTime());
                            $em->persist($payoutDocument);
                        }
                    }
                    if ($remark) {
                        $refundNote = new RefundNote;
                        $refundNote->setNote($remark);
                        $refundNote->setSource($payout);
                        $em->persist($refundNote);
                    }
                    $payoutId = $payout->getPayoutId();
                }
                $em->flush();
            }
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'payoutId' => $payoutId
        ));
    }

    private function getOffset ($limit = 10, $page = 0)
    {
        return (int) $page > 1 ? (int) $limit * ((int) $page-1) : 0;
    }

    /**
     * Render buyer refund history list
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBuyerRefundHistoryAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbPayout = $em->getRepository('YilinkerCoreBundle:Payout');

        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 10);
        $form = $this->createForm('refund_history_filter');
        $form->submit($request->get($form->getName()));
        $filter = $form->getData();
        $refundHistory = $tbPayout->filterQB($filter, $page, $perPage)->getResult();
        $count = $tbPayout->filterQB($filter)->getCount();
        $form = $form->createView();
        $data = compact('form', 'refundHistory', 'perPage', 'count');

        return $this->render('YilinkerBackendBundle:Transaction:buyer_refund_history.html.twig', $data);
    }

    /**
     * @Security("has_role('ROLE_ACCOUNTING')")
     */
    public function refundHistoryDetailAction(Request $request)
    {
        $payoutId = $request->get('payoutId');
        $em = $this->getDoctrine()->getEntityManager();
        $tbPayout = $em->getRepository('YilinkerCoreBundle:Payout');
        $payout = $tbPayout->find($payoutId);

        if (!$payout) {
            throw $this->createNotFoundException('Cannot find payout with id #'.$payoutId);
        }
        $data = compact('payout');

        return $this->render('YilinkerBackendBundle:Transaction/partials:buyer_refund_history_detail.html.twig', $data);
    }

    /**
     * Render buyer refund overview
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBuyerRefundOverviewAction(Request $request)
    {
        $payoutId = (int) $request->get('payout', null);

        $em = $this->getDoctrine()->getManager();

        $payout = $em->find('YilinkerCoreBundle:Payout', $payoutId);

        if (!$payout) {
            throw $this->createNotFoundException('Sorry not existing');
        }

        return $this->render('YilinkerBackendBundle:Transaction:buyer_refund_overview.html.twig', compact('payout'));
    }

    /**
     * Render buyer refund overview
     * @Security("has_role('ROLE_ACCOUNTING')")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBuyerRefundOverviewPrintAction(Request $request)
    {
        $payoutId = (int) $request->get('payout', null);

        $em = $this->getDoctrine()->getManager();

        $payout = $em->find('YilinkerCoreBundle:Payout', $payoutId);

        if (!$payout) {
            throw $this->createNotFoundException('Sorry not existing');
        }

        return $this->render('YilinkerBackendBundle:Transaction:buyer_refund_overview_print.html.twig', array(
            'payout' => $payout,
        ));
    }
}
