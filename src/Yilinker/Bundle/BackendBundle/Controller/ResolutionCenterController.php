<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;

/**
 * Class ResolutionCenterController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_CSR')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class ResolutionCenterController extends Controller
{
    const PAGE_LIMIT = 10;

    /**
     * Render Resolution Center
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderResolutionCenterAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->format('m/d/y');
        $dateToCarbon = Carbon::now()->format('m/d/y');
        $pageLimit = self::PAGE_LIMIT;
        $page = $request->query->get('page', 1) -1;
        $searchKeyword = $request->query->get('searchKeyword', null);
        $disputeStatusType = $request->query->get('disputeStatusTypeId', null);
        $orderProductStatus = $request->query->get('orderProductStatusId', null);
        $dateFrom = $request->query->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->query->get('dateTo', $dateToCarbon);
        $em = $this->getDoctrine()->getManager();
        $disputeRepository = $em->getRepository('YilinkerCoreBundle:Dispute');
        $offset = $page * $pageLimit;
        $orderProductStatuses = array (
            array (
                'status' => 'Refund',
                'orderProductStatusId' => OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED,
            ),
            array (
                'status' => 'Replacement',
                'orderProductStatusId' => OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED,
            ),
            array (
                'status' => 'All',
                'orderProductStatusId' => 0,
            )
        );
        $disputeStatusTypes = $em->getRepository('YilinkerCoreBundle:DisputeStatusType')
                                 ->findAll();
        $disputeStatusTypeReference = null;
        $orderProductStatusReference = null;

        if ($disputeStatusType !== null && (int) $disputeStatusType !== 0) {
            $disputeStatusTypeReference = $em->getReference('YilinkerCoreBundle:DisputeStatusType', $disputeStatusType);
        }

        if ($orderProductStatus !== null && (int) $orderProductStatus !== 0) {
            $orderProductStatusReference = $em->getReference('YilinkerCoreBundle:OrderProductStatus', $orderProductStatus);
        }

        $listOfComplainedTransaction = $disputeRepository->getCase (
                                                                       null,
                                                                       null,
                                                                       $disputeStatusTypeReference,
                                                                       $searchKeyword,
                                                                       $orderProductStatusReference,
                                                                       $dateFrom,
                                                                       $dateTo,
                                                                       $offset,
                                                                       $pageLimit
                                                                    );

        $response = compact (
            'pageLimit',
            'listOfComplainedTransaction',
            'orderProductStatuses',
            'disputeStatusTypes'
        );

        return $this->render('YilinkerBackendBundle:Transaction:resolution_center.html.twig', $response);
    }

    /**
     * Render Resolution Center Detail
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function renderResolutionCenterDetailAction (Request $request)
    {
        $disputeId = $request->query->get('disputeId', null);
        $em = $this->getDoctrine()->getManager();
        $disputeEntity = $em->getRepository('YilinkerCoreBundle:Dispute')->find($disputeId);

        if (!$disputeEntity) {
            return $this->redirect($this->generateUrl('yilinker_backend_resolution_center'));
        }

        $disputeContainer = $this->get('yilinker_core.service.dispute_manager')
                                 ->getCaseWithDetail (
                                     null,
                                     null,
                                     $disputeEntity,
                                     0,
                                     PHP_INT_MAX
                                 );

        return $this->render('YilinkerBackendBundle:Transaction:resolution_center_detail.html.twig', $disputeContainer['cases'][0]);
    }

    /**
     * Add Dispute Message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addDisputeMessageAction (Request $request)
    {
        $disputeId = $request->request->get('disputeId', null);
        $disputeMessage = $request->request->get('disputeMessage', null);
        $em = $this->getDoctrine()->getManager();
        $disputeEntity = $em->getRepository('YilinkerCoreBundle:Dispute')->find($disputeId);
        $isSuccessful = false;

        if ($disputeEntity) {
            $disputeMessageEntity = $this->get('yilinker_core.service.dispute_manager')
                                         ->addDisputeMessage (
                                             $disputeEntity,
                                             $this->getUser(),
                                             $disputeMessage,
                                             1
                                         );

            if ($disputeMessageEntity) {
                $isSuccessful = true;
            }

        }

        return new JsonResponse($isSuccessful);
    }

    /**
     * Approve Dispute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function approveDisputeAction (Request $request)
    {
        $isSuccessful = false;
        $disputeDetailIds = $request->request->get('disputeDetailIds', null);
        $approveAction = $request->get('approveAction');

        if ($disputeDetailIds && !is_null($approveAction)) {
            $disputeService = $this->get('yilinker_core.service.dispute');
            $isSuccessful = $disputeService->approveDisputeDetails($disputeDetailIds, $approveAction);
        }

        return new JsonResponse($isSuccessful);
    }

    /**
     * Reject Dispute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectDisputeAction (Request $request)
    {
        $isSuccessful = false;
        $disputeDetailIds = $request->request->get('disputeDetailIds', null);

        if ($disputeDetailIds !== null) {
            $isSuccessful = $this->get('yilinker_core.service.dispute_manager')->updateDisputeDetail ($disputeDetailIds, false);
        }

        return new JsonResponse($isSuccessful);
    }

}
