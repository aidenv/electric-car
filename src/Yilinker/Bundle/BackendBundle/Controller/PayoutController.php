<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchDetail;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchFile;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;
use Symfony\Component\HttpFoundation\Response;
/**
 * Class PayoutController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_ACCOUNTING')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class PayoutController extends Controller
{
    const PAGE_LIMIT = 10;

    /**
     * Display Request Payout
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderRequestPayoutAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('Y-m-d');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('Y-m-d');
        $searchBy = $request->get('searchBy', null);
        $dateFrom = $request->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->get('dateTo', $dateToCarbon);
        $paymentMethod = $request->get('payoutRequestMethods', null);
        $paymentStatus = $request->get('payoutRequestStatuses', null);
        $orderBy = $request->get('orderBy', 'asc');
        $page = $request->get('page', 1);
        $limit = self::PAGE_LIMIT;

        $em = $this->getDoctrine()->getManager();
        $payoutRequestRepository = $em->getRepository('YilinkerCoreBundle:PayoutRequest');
        $payoutRequestManager = $this->get('yilinker_backend.payout_request_manager');
        $payoutRequestData = $payoutRequestManager->getPayoutRequest (
                                                        $searchBy,
                                                        $dateFrom,
                                                        $dateTo,
                                                        $paymentMethod,
                                                        $paymentStatus,
                                                        $orderBy,
                                                        $page,
                                                        $limit
                                                    );
        $payoutMethods = $payoutRequestRepository->getPayoutMethod();
        $payoutStatuses = $payoutRequestRepository->getPayoutStatus();
        $payoutBatchStatuses = $this->get('yilinker_backend.batch_payout_manager')->getPayoutBatchStatus();

        $data = compact (
            'payoutMethods',
            'payoutStatuses',
            'payoutRequestData',
            'payoutBatchStatuses',
            'limit'
        );

        return $this->render('YilinkerBackendBundle:Payout:request_payout.html.twig', $data);
    }

    /**
     * Create Batch payout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createBatchPayoutAction (Request $request)
    {
        $isPayoutBatchList = $request->get('isPayoutBatchList', false);
        $payoutRequestIds = $request->get('payoutRequestIds', null);
        $batchPayoutManager = $this->get('yilinker_backend.batch_payout_manager');

        if ($isPayoutBatchList) {
            $response = $batchPayoutManager->createBatchPayoutByPayoutRequest(array(), $this->getUser(), $request->getLocale());
        }
        else {
            $response = array (
                'isSuccessful' => false,
                'message'      => 'Invalid PayoutRequest',
                'data'         => array()
            );
            $payoutRequestIdsArray = explode(',', $payoutRequestIds);

            if (sizeof($payoutRequestIdsArray) > 0) {
                $em = $this->getDoctrine()->getManager();
                $payoutRequestEntities = $em->getRepository('YilinkerCoreBundle:PayoutRequest')
                                            ->getPayoutRequestByIds($payoutRequestIdsArray);

                if (sizeof($payoutRequestEntities) > 0) {
                    $response = $batchPayoutManager->createBatchPayoutByPayoutRequest($payoutRequestEntities, $this->getUser(), $request->getLocale());
                }

            }

        }

        return new JsonResponse($response);
    }

    /**
     * Update Batch payout head
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateBatchPayoutHeadAction (Request $request)
    {
        $payoutBatchHeadId = $request->get('payoutBatchHeadId', 0);
        $remarks = $request->get('remarks', '');
        $payoutBatchStatusId = $request->get('payoutBatchStatusId', 0);
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid payout batch head id.'
        );

        $em = $this->getDoctrine()->getManager();
        $payoutBatchHeadRepository = $em->getRepository('YilinkerCoreBundle:PayoutBatchHead');
        $payoutBatchHeadEntity = $payoutBatchHeadRepository->find($payoutBatchHeadId);

        if ($payoutBatchHeadEntity instanceof PayoutBatchHead) {

            if (in_array($payoutBatchStatusId, array(PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS, PayoutBatchHead::PAYOUT_BATCH_STATUS_DEPOSITED))) {
                $payoutBatchHeadEntity->setRemarks($remarks);
                $payoutBatchHeadEntity->setPayoutBatchStatus($payoutBatchStatusId);
                $payoutBatchHeadEntity->setDateLastModified(Carbon::now());
                $em->flush();

                if ($payoutBatchStatusId == PayoutBatchHead::PAYOUT_BATCH_STATUS_DEPOSITED) {
                    $payoutBatchDetailEntities = $em->getRepository('YilinkerCoreBundle:PayoutBatchDetail')->findByPayoutBatchHead($payoutBatchHeadEntity);

                    if (sizeof($payoutBatchDetailEntities) > 0) {

                        foreach ($payoutBatchDetailEntities as $payoutBatchDetailEntity) {
                            $payoutRequest = $payoutBatchDetailEntity->getPayoutRequest();
                            $payoutRequest->setPayoutRequestStatus(PayoutRequest::PAYOUT_STATUS_PAID);
                            $this->get('yilinker_backend.batch_payout_manager')->earn($payoutRequest);
                        }

                    }

                }

                $response = array (
                    'isSuccessful' => true,
                    'message'      => 'Successfully Saved!'
                );
            }
            else {
                $response = array (
                    'isSuccessful' => false,
                    'message'      => 'Invalid status'
                );
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Hard Delete Batch payout detail
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removePayoutBatchDetailsAction (Request $request)
    {
        $payoutBatchDetailId = $request->get('payoutBatchDetailId', 0);
        $em = $this->getDoctrine()->getManager();
        $payoutBatchDetailRepository = $em->getRepository('YilinkerCoreBundle:PayoutBatchDetail');
        $payoutBatchDetailEntity = $payoutBatchDetailRepository->find($payoutBatchDetailId);
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid payout batch id.'
        );

        if ($payoutBatchDetailEntity instanceof PayoutBatchDetail) {
            $this->get('yilinker_backend.batch_payout_manager')->deletePayoutBatchDetail($payoutBatchDetailEntity);
            $response = array (
                'isSuccessful' => true,
                'message'      => 'Successfully removed.'
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Render Payout batch list
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBatchPayoutAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('Y-m-d');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('Y-m-d');
        $searchBy = $request->get('searchBy', null);
        $dateFrom = $request->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->get('dateTo', $dateToCarbon);
        $page = $request->get('page', 1);
        $limit = self::PAGE_LIMIT;

        $payoutBatchManager = $this->get('yilinker_backend.batch_payout_manager');
        $payoutBatchStatuses = $payoutBatchManager->getPayoutBatchStatus();
        $payoutBatchData = $payoutBatchManager->getPayoutBatchData (
                                                    $searchBy,
                                                    $dateFrom,
                                                    $dateTo,
                                                    $page,
                                                    $limit
                                                );
        $data = compact (
            'payoutBatchData',
            'payoutBatchStatuses',
            'limit'
        );

        return $this->render('YilinkerBackendBundle:Payout:batch_payout.html.twig', $data);
    }

    /**
     * Hard delete payoutBatch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removePayoutBatchHeadAction (Request $request)
    {
        $batchPayoutHeadId = $request->get('payoutBatchHeadId', 0);
        $em = $this->getDoctrine()->getManager();
        $payoutBatchHeadRepository = $em->getRepository('YilinkerCoreBundle:PayoutBatchHead');
        $payoutBatchHeadEntity = $payoutBatchHeadRepository->find($batchPayoutHeadId);
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid payout batch id.'
        );

        if ($payoutBatchHeadEntity instanceof PayoutBatchHead) {
            $response = $this->get('yilinker_backend.batch_payout_manager')->deletePayoutBatch($payoutBatchHeadEntity);
        }

        return new JsonResponse($response);
    }

    /**
     * Upload PayoutBatchFile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadPayoutBatchFileAction (Request $request)
    {
        $receiptFile = $request->files->get('receipt', null);
        $payoutBatchHeadId = $request->get('payoutBatchHeadId', 0);
        $formFiles = $this->createForm('payout_batch_file_upload', null, array('csrf_protection' => false));
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $em = $this->getDoctrine()->getManager();
        $formData = array (
            'receipt'         => array ($receiptFile),
            'payoutBatchHead' => $payoutBatchHeadId
        );
        $formFiles->submit($formData);

        if (!$formFiles->isValid()) {
            $response = array (
                'isSuccessful' => false,
                'message'      => implode($formErrorService->throwInvalidFields($formFiles), ' \n'),
            );
        }
        else {
            $payoutBatchHeadEntity = $em->getRepository('YilinkerCoreBundle:PayoutBatchHead')->find($payoutBatchHeadId);
            $response = $this->get('yilinker_backend.batch_payout_manager')
                             ->addBatchPayoutFile ($payoutBatchHeadEntity, $receiptFile);
        }

        return new JsonResponse($response);
    }

    /**
     * Download Payout batch file
     *
     * @param $payoutBatchFileId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function downloadPayoutBatchFileAction ($payoutBatchFileId)
    {
        $em = $this->getDoctrine()->getManager();
        $payoutBatchFileEntity= $em->getRepository('YilinkerCoreBundle:PayoutBatchFile')->find($payoutBatchFileId);
        $payoutBatchHeadId = $payoutBatchFileEntity->getPayoutBatchHead()->getPayoutBatchHeadId();
        $payoutBatchManager = $this->get('yilinker_backend.batch_payout_manager');
        $fileFullPath = 'assets/' . $payoutBatchManager::FILE_DIRECTORY . $payoutBatchHeadId . DIRECTORY_SEPARATOR . $payoutBatchFileEntity->getFileName();

        if (file_exists($fileFullPath)) {
            $pathParts = pathinfo($fileFullPath);
            header('Content-Description: File Transfer');
            header('Content-Type: application/force-download');
            header("Content-Disposition: attachment; filename=\"" . $pathParts['basename'] . "\";");
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileFullPath));
            ob_clean();
            flush();
            readfile($fileFullPath);
        }
        else {
            return $this->redirect($this->generateUrl('admin_home_page'));
        }

    }

    /**
     * Remove PayoutBatchFile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removePayoutBatchFileAction (Request $request)
    {
        $batchPayoutFileId = $request->get('batchPayoutFileId', 0);
        $em = $this->getDoctrine()->getManager();
        $payoutBatchFileRepository = $em->getRepository('YilinkerCoreBundle:PayoutBatchFile');
        $payoutBatchFileEntity = $payoutBatchFileRepository->find($batchPayoutFileId);
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid payout batch file id.'
        );

        if ($payoutBatchFileEntity instanceof PayoutBatchFile) {
            $this->get('yilinker_backend.batch_payout_manager')->deletePayoutBatchFile($payoutBatchFileEntity);
            $response = array (
                'isSuccessful' => true,
                'message'      => 'Successfully removed.'
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Create payoutBatch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkCreatePayoutBatchAction (Request $request)
    {
        $payoutBatchStatus = $request->get('payoutBatchStatusId', PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS);
        $remarks = $request->get('remarks', '');
        $files = $request->files->get('receiptFiles', array());
        $payoutRequestIdsArray = $request->get('payoutRequestIds', array());
        $response = array (
            'isSuccessful' => true,
            'message'      => '',
            'data'         => array()
        );

        $formFiles = $this->createForm('core_file_image_pdf', null, array('csrf_protection' => false));
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $formData = array (
            'files' => $files
        );
        $formFiles->submit($formData);

        if (!$formFiles->isValid()) {
            $response = array (
                'isSuccessful' => false,
                'message'      => implode($formErrorService->throwInvalidFields($formFiles), ' \n'),
            );
        }

        if (sizeof($payoutRequestIdsArray) === 0) {
            $response = array (
                'isSuccessful' => false,
                'message'      => 'Invalid PayoutRequest',
                'data'         => array()
            );
        }
        else {
            $em = $this->getDoctrine()->getManager();
            $payoutRequestEntities = $em->getRepository('YilinkerCoreBundle:PayoutRequest')
                                        ->getPayoutRequestByIds($payoutRequestIdsArray);

            if (sizeof($payoutRequestEntities) > 0) {
                $batchPayoutManager = $this->get('yilinker_backend.batch_payout_manager');
                $response = $batchPayoutManager->createBulkBatchPayout (
                                                     $payoutRequestEntities,
                                                     $this->getUser(),
                                                     $payoutBatchStatus,
                                                     $remarks,
                                                     $files,
                                                     $request->getLocale()
                                                 );
            }
        }

        return new JsonResponse($response);
    }

    /**
     * Get PayoutBatchDetail
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPayoutBatchDetailAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $payoutRequestRepository = $em->getRepository('YilinkerCoreBundle:PayoutRequest');
        $searchKeyword = $request->get('searchKeyword');
        $payoutRequestEntities = $payoutRequestRepository->getPayoutRequestByKeyword($searchKeyword);
        $payoutRequestContainer = array();

        if ($payoutRequestEntities) {

            foreach ($payoutRequestEntities as $payoutRequest) {
                $payoutRequestContainer[] = $payoutRequest->toArray();
            }

        }

        return new JsonResponse($payoutRequestContainer);
    }

    /**
     * Create Payout batch Detail
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPayoutBatchDetailAction (Request $request)
    {
        $payoutBatchHeadId = $request->get('payoutBatchHeadId', 0);
        $payoutRequestId = $request->get('payoutRequestId', 0);
        $em = $this->getDoctrine()->getManager();
        $payoutBatchManager = $this->get('yilinker_backend.batch_payout_manager');
        $payoutBatchHeadEntity = $em->getRepository('YilinkerCoreBundle:PayoutBatchHead')->find($payoutBatchHeadId);
        $payoutRequestEntity = $em->getRepository('YilinkerCoreBundle:PayoutRequest')->getPayoutRequestByIds(array($payoutRequestId));
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid Payout'
        );

        if ($payoutBatchHeadEntity instanceof PayoutBatchHead && sizeof($payoutRequestEntity) > 0 && $payoutRequestEntity[0] instanceof PayoutRequest) {
            $payoutBatchDetailEntity = $payoutBatchManager->createBatchPayoutDetail($payoutBatchHeadEntity, $payoutRequestEntity[0]);

            if ($payoutBatchDetailEntity instanceof PayoutBatchDetail) {
                $payoutBatchDetailArray = $payoutBatchDetailEntity->toArray();

                $response = array (
                    'isSuccessful' => true,
                    'message'      => 'Successfully saved!',
                    'data'         => $payoutBatchDetailArray
                );
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Get payout batch head and detail by headId
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPayoutBatchDataAction (Request $request)
    {
        $payoutBatchHeadId = $request->get('payoutBatchHeadId', null);
        $em = $this->getDoctrine()->getManager();
        $payoutBatchHeadEntity = $em->getRepository('YilinkerCoreBundle:PayoutBatchHead')
                                    ->find($payoutBatchHeadId);
        $response = array (
            'isSuccessful' => false,
            'data'         => null,
            'message'      => 'Invalid Batch Payout Id'
        );

        if ($payoutBatchHeadEntity instanceof PayoutBatchHead) {

            $response = array (
                'isSuccessful' => true,
                'data'         => $this->get('yilinker_backend.batch_payout_manager')->getBatchPayoutData($payoutBatchHeadEntity),
                'message'      => ''
            );

        }

        return new JsonResponse($response);
    }

    /**
     * Transaction List
     */
    public function getPayoutTransactionAction(Request $request)
    {
        $sellerId = $request->get('seller', null);

        $em = $this->getDoctrine()->getManager();
        $tbUser = $em->getRepository('YilinkerCoreBundle:User');
        $seller = $tbUser->find($sellerId);
        
        $earnings = null;        

        $em = $this->getDoctrine()->getEntityManager();
        $tbEarning = $em->getRepository('YilinkerCoreBundle:Earning');

        if ($seller && $store = $seller->getStore()) {
            $earnings = $tbEarning->getOfStore($store, null, 1,1000000);    
        }        

        $template = $this->renderView('YilinkerBackendBundle:Payout:payout_transaction_list.html.twig',
            array(
                'earnings' => $earnings,
            ));
        
        return new Response($template);

    }
}
