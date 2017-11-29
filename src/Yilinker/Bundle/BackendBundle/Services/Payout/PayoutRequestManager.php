<?php

namespace Yilinker\Bundle\BackendBundle\Services\Payout;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;

/**
 * Class PayoutRequestManager
 */
class PayoutRequestManager
{

    /**
     * @var Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct (EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Get Payout Request
     *
     * @param null $searchBy
     * @param null $dateFrom
     * @param null $dateTo
     * @param null $payoutRequestMethods
     * @param null $payoutRequestStatuses
     * @param string $orderBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getPayoutRequest (
        $searchBy = null,
        $dateFrom = null,
        $dateTo = null,
        $payoutRequestMethods = null,
        $payoutRequestStatuses = null,
        $orderBy = 'asc',
        $page = 1,
        $limit = 10
    )
    {
        $payoutRequestRepository = $this->em->getRepository('YilinkerCoreBundle:PayoutRequest');
        $payoutBatchDetailRepository = $this->em->getRepository('YilinkerCoreBundle:PayoutBatchDetail');
        $payoutRequestMethodIds = array();
        $payoutRequestStatusIds = array();
        $payoutRequestList = array();

        if (!is_null($payoutRequestMethods)) {
            $payoutRequestMethodIds = explode(',', $payoutRequestMethods);
        }

        if (!is_null($payoutRequestStatuses)) {
            $payoutRequestStatusIds = explode(',', $payoutRequestStatuses);
        }

        $orderBy = in_array($orderBy, array('asc','desc')) ? $orderBy : 'asc';
        $offset = $this->__getOffset($limit, $page);

        $payoutRequests = $payoutRequestRepository->getPayoutRequest (
                                                           $searchBy,
                                                           new Carbon($dateFrom),
                                                           new Carbon($dateTo),
                                                           $payoutRequestMethodIds,
                                                           $payoutRequestStatusIds,
                                                           $orderBy,
                                                           $offset,
                                                           $limit
                                                        );
        $rowCount = $offset;

        if (sizeof($payoutRequests) > 0) {

            foreach($payoutRequests as $payoutRequest) {
                $inProcess = !is_null($payoutRequest['inProcess']);
                $payoutRequest = $payoutRequest[0];
                $payoutRequest->payoutRequestMethodName = null;
                $payoutRequest->payoutRequestStatusName = null;
                $payoutRequest->rowCount = ++$rowCount;
                $payoutRequest->inProcess = $inProcess;

                if ( (int) $payoutRequest->getPayoutRequestMethod() === PayoutRequest::PAYOUT_METHOD_BANK) {
                    $payoutRequest->payoutRequestMethodName = 'Bank';
                }
                else if ((int) $payoutRequest->getPayoutRequestMethod() === PayoutRequest::PAYOUT_METHOD_CHEQUE) {
                    $payoutRequest->payoutRequestMethodName = 'Cheque';
                }

                if ((int) $payoutRequest->getPayoutRequestStatus() === PayoutRequest::PAYOUT_STATUS_PENDING) {
                    $payoutRequest->payoutRequestStatusName = 'Pending';
                }
                else if ((int) $payoutRequest->getPayoutRequestStatus() === PayoutRequest::PAYOUT_STATUS_PAID) {
                    $payoutRequest->payoutRequestStatusName = 'Paid';
                }

                $payoutRequestList[] = $payoutRequest;
            }

        }

        $payoutRequestCount = $payoutRequestRepository->getPayoutRequestCount (
                                                           $searchBy,
                                                           new Carbon($dateFrom),
                                                           new Carbon($dateTo),
                                                           $payoutRequestMethodIds,
                                                           $payoutRequestStatusIds
                                                       );

        return compact('payoutRequestList', 'payoutRequestCount');
    }

    /**
     * Calculate offset
     *
     * @param int $limit
     * @param int $page
     * @return int
     */
    private function __getOffset ($limit = 10, $page = 0)
    {
        return (int) $page > 1 ? (int) $limit * ((int) $page-1) : 0;
    }

}
