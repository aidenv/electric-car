<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;

class DisputeApiController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCaseAction (Request $request)
    {
        $page = $request->query->get('page', 1) - 1;
        $disputeStatusType = $request->query->get('disputeStatusType', null);
        $orderProductStatus = $request->query->get('orderProductStatus', null);
        $dateFrom = $request->query->get('dateFrom', null);
        $dateTo = $request->query->get('dateTo', null);
        $em = $this->getDoctrine()->getManager();
        $disputeRepository = $em->getRepository('YilinkerCoreBundle:Dispute');

        $disputeStatusTypeReference = null;
        $orderProductStatusReference = null;

        if ($disputeStatusType !== null) {
            $disputeStatusTypeReference = $em->getReference('YilinkerCoreBundle:DisputeStatusType', $disputeStatusType);
        }

        if ($orderProductStatus) {
            $orderProductStatusReference = $em->getReference('YilinkerCoreBundle:OrderProductStatus', $orderProductStatus);
        }

        $response = array (
            'isSuccessful' => false,
            'message' => '',
            'data' => array()
        );

        $listOfComplainedTransaction = $disputeRepository->getCase (
                                                               $this->getUser(),
                                                               null,
                                                               $disputeStatusTypeReference,
                                                               null,
                                                               $orderProductStatusReference,
                                                               $dateFrom,
                                                               $dateTo,
                                                               $page,
                                                               PHP_INT_MAX
                                                           );

        if ($listOfComplainedTransaction['cases']) {
            $caseArray = array ();

            foreach ($listOfComplainedTransaction['cases'] as $case) {
                $caseArray[] = array (
                    'disputeId' => $case['disputeId'],
                    'ticketId' => $case['ticket'],
                    'disputeStatusType' => $case['status'],
                    'orderProductStatus' => $case['orderProductStatus'],
                    'dateAdded' => date_format($case['dateAdded'], 'Y/m/d h:m:s'),
                    'disputeeFullName' => $case['disputeeFullName'],
                    'disputeeContactNumber' => $case['disputeeContactNumber']
                );
            }

            $response = array (
                'isSuccessful' => true,
                'message' => 'Successfully retrieved case',
                'data' => $caseArray
            );

        }

        return new JsonResponse($response);
    }

    /**
     * Get Case (detailed)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCaseByDetailAction (Request $request)
    {
        $disputeId = $request->query->get('disputeId', null);
        $em = $this->getDoctrine()->getManager();
        $response = array (
            'isSuccessful' => false,
            'message' => 'Invalid Dispute Id',
            'data' => array()
        );

        if ($disputeId !== null) {
            $disputeEntity = $em->getRepository('YilinkerCoreBundle:Dispute')->find($disputeId);

            if ($disputeEntity) {
                $disputeContainer = $this->get('yilinker_core.service.dispute_manager')
                                         ->getCaseWithDetail (
                                             null,
                                             null,
                                             $disputeEntity,
                                             null,
                                             1,
                                             PHP_INT_MAX
                                         );
                $disputeArray = array();
                $dispute = $disputeContainer['cases'][0];
                $remarks = array ();
                $products = array ();

                foreach ($dispute['message'] as $disputeMessage) {
                    $remarks[] = array(
                        'isAdmin' => $disputeMessage['isAdmin'],
                        'dateAdded' => date_format($disputeMessage['dateAdded'], 'Y/m/d h:m:s'),
                        'message' => $disputeMessage['message'],
                        'authorFullName' => $disputeMessage['authorEntity']->getFirstName() . ' ' . $disputeMessage['authorEntity']->getLastName()
                    );
                }

                foreach ($dispute['products'] as $product) {
                    $products[] = array (
                        'productName' => $product->getProductName()
                    );
                }

                $disputeArray['ticket'] = $dispute['ticket'];
                $disputeArray['invoiceNumber'] = $dispute['transaction']->getInvoiceNumber();
                $disputeArray['disputeStatusType'] = $dispute['status'];
                $disputeArray['orderProductStatus'] = $dispute['orderProductStatus'];
                $disputeArray['dateAdded'] = date_format($dispute['dateAdded'], 'Y/m/d H:m:s');
                $disputeArray['lastModifiedDate'] = date_format($dispute['lastModifiedDate'], 'Y/m/d H:m:s');
                $disputeArray['disputerFullName'] = $dispute['disputerFullName'];
                $disputeArray['disputeeFullName'] = $dispute['disputeeFullName'];
                $disputeArray['description'] = $dispute['description'];
                $disputeArray['remarks'] = $remarks;
                $disputeArray['products'] = $products;

                $response = array (
                    'isSuccessful' => true,
                    'message' => 'Successfully retrieved case detail',
                    'data' => $disputeArray
                );
            }
        }

        return new JsonResponse($response);
    }

    /**
     * Add Dispute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addCaseAction (Request $request)
    {
        $description = $request->request->get('disputeTitle', null);
        $message = $request->request->get('remarks', null);
        $orderProductStatus = (int) $request->request->get('orderProductStatus', null);
        $reasonId = (int) $request->request->get('reasonId', null);
        $orderProductIds = json_decode($request->request->get('orderProductIds', '{}'), true);
        $formData = array (
            'description' => $description,
            'message' => $message,
            'orderProductStatus' => $orderProductStatus,
            'orderProductCancellationReasonId' => $reasonId,
            'orderProductIds' => $orderProductIds,
        );
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->createForm('api_create_new_case', null, array('csrf_protection' => false));
        $form->submit($formData);

        $validOrderProductStatus = array(
            OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED => '',
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED => ''
        );

        if (!$form->isValid()) {
            $response = array (
                'isSuccessful' => false,
                'message' => implode($formErrorService->throwInvalidFields($form), ' \n '),
                'data' => array()
            );
        }
        else if (!isset($validOrderProductStatus[$orderProductStatus])) {
            $response = array (
                'isSuccessful' => false,
                'message' => 'Invalid Order Product Status',
                'data' => array()
            );
        }
        else if (sizeof($orderProductIds) === 0) {
            $response = array (
                'isSuccessful' => false,
                'message' => 'Invalid Order Product',
                'data' => array()
            );
        }
        else {
            $em = $this->getDoctrine()->getManager();
            $orderProductEntities = $em->getRepository('YilinkerCoreBundle:OrderProduct')->findByOrderProductId($orderProductIds);
            $disputeManager = $this->get('yilinker_core.service.dispute_manager');
            $dispute = $disputeManager->addNewCase (
                                            $orderProductEntities,
                                            $this->getUser(),
                                            $description,
                                            $message,
                                            $orderProductStatus,
                                            $reasonId
                                        );
            $response = array (
                'isSuccessful' => true,
                'message' => 'Successfully added new case',
                'data' => $dispute->getDisputeId()
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Get Seller Dispute Reason by reason type
     *
     * @return JsonResponse
     */
    public function getSellerDisputeReasonAction ()
    {
        $disputeManager = $this->get('yilinker_core.service.dispute_manager');

        $reasonForReplacement = $disputeManager->getOrderProductReasonByType (
            OrderProductCancellationReason::REASON_TYPE_REPLACEMENT,
            OrderProductCancellationReason::USER_TYPE_SELLER
        );

        $reasonForRefund = $disputeManager->getOrderProductReasonByType (
            OrderProductCancellationReason::REASON_TYPE_REFUND,
            OrderProductCancellationReason::USER_TYPE_SELLER
        );

        $reasons = array (
            0 => array (
                'key' => 'Replacement',
                'reasons' => $reasonForReplacement['seller']
            ),
            1 => array (
                'key' => 'Refund',
                'reasons' => $reasonForRefund['seller']
            ),
        );

        $response = array (
            'isSuccessful' => true,
            'data' => $reasons,
            'message' => ''
        );

        return new JsonResponse($response);
    }

    /**
     * Get Buyer Dispute Reason by reason type
     *
     * @return JsonResponse
     */
    public function getBuyerDisputeReasonAction ()
    {
        $disputeManager = $this->get('yilinker_core.service.dispute_manager');

        $reasonForReplacement = $disputeManager->getOrderProductReasonByType (
            OrderProductCancellationReason::REASON_TYPE_REPLACEMENT,
            OrderProductCancellationReason::USER_TYPE_BUYER
        );

        $reasonForRefund = $disputeManager->getOrderProductReasonByType (
            OrderProductCancellationReason::REASON_TYPE_REFUND,
            OrderProductCancellationReason::USER_TYPE_BUYER
        );

        $reasons = array (
            0 => array (
                'key' => 'Replacement',
                'reasons' => $reasonForReplacement['buyer']
            ),
            1 => array (
                'key' => 'Refund',
                'reasons' => $reasonForRefund['buyer']
            ),
        );

        $response = array (
            'isSuccessful' => true,
            'data' => $reasons,
            'message' => ''
        );

        return new JsonResponse($response);
    }
}
