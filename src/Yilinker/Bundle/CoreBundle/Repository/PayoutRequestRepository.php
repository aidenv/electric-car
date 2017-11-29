<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query;

class PayoutRequestRepository extends EntityRepository
{

    public function ofStoreQB($store, $filter = null, $descending = false)
    {
        $user = $store->getUser();
        $this
            ->qb()
            ->andWhere('this.requestBy = :user')
            ->setParameter('user', $user)
        ;

        if($descending){
            $this->orderBy('this.dateLastModified', 'DESC');
        }

        if (is_array($filter) && $filter) {
            if (array_key_exists('status', $filter) && $filter['status']) {
                $this
                    ->andWhere('this.payoutRequestStatus = :status')
                    ->setParameter('status', $filter['status'])
                ;
            }
        }

        return $this;
    }

    public function getStoreTotal($store, $filter = null)
    {
        $this
            ->ofStoreQB($store, $filter)
            ->select('SUM(this.requestedAmount)')
        ;
        $total = $this->getResult(Query::HYDRATE_SINGLE_SCALAR);

        return $total;
    }

    /**
     * gets payout request of store
     */
    public function getOfStore($store, $page = 1, $count = 10, $descending = false)
    {
        $this
            ->ofStoreQB($store, null, $descending)
            ->setMaxResults($count)
            ->page($page)
        ;

        return $this->getResult();
    }

    /**
     * Get PayoutRequest
     *
     * @param null $searchBy
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @param array $payoutRequestMethods
     * @param array $payoutRequestStatuses
     * @param string $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getPayoutRequest (
        $searchBy = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null,
        $payoutRequestMethods = array(),
        $payoutRequestStatuses = array(),
        $orderBy = 'asc',
        $offset = 1,
        $limit = 10
    )
    {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select ('PayoutRequest')
                     ->addSelect('PayoutBatchDetail.payoutBatchDetailId as inProcess')
                     ->from('YilinkerCoreBundle:PayoutRequest', 'PayoutRequest')
                     ->leftJoin('YilinkerCoreBundle:User', 'User', 'WITH', 'User.userId = PayoutRequest.requestBy')
                     ->leftJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'User.userId = Store.user')
                     ->leftJoin('YilinkerCoreBundle:PayoutBatchDetail', 'PayoutBatchDetail', 'WITH', 'PayoutBatchDetail.payoutRequest = PayoutRequest.payoutRequestId AND PayoutBatchDetail.isDelete = :deleted')
                     ->where('User.userType != :userTypeBuyer')
                     ->setParameter('userTypeBuyer', User::USER_TYPE_BUYER)
                     ->setParameter('deleted', false);

        if (!is_null($searchBy)) {
            $queryBuilder->andWhere('CONCAT(User.firstName, " ", User.lastName) LIKE :searchBy OR
                                     PayoutRequest.referenceNumber LIKE :searchBy OR
                                     PayoutRequest.bankAccountName LIKE :searchBy OR
                                     PayoutRequest.bankAccountNumber LIKE :searchBy')
                         ->setParameter('searchBy', '%' . $searchBy . '%');
        }

        if (!is_null($dateFrom)) {
            $queryBuilder->andWhere('PayoutRequest.dateAdded > :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->startOfDay()->format('Y-m-d H:i:s'));
        }

        if (!is_null($dateTo)) {
            $queryBuilder->andWhere('PayoutRequest.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo->endOfDay()->format('Y-m-d H:i:s'));
        }

        if (sizeof($payoutRequestMethods) > 0) {
            $queryBuilder->andWhere('PayoutRequest.payoutRequestMethod IN (:payoutRequestMethodIds)')
                         ->setParameter('payoutRequestMethodIds', $payoutRequestMethods);
        }

        if (sizeof($payoutRequestStatuses) > 0) {
            $queryBuilder->andWhere('PayoutRequest.payoutRequestStatus IN (:payoutRequestStatuses)')
                         ->setParameter('payoutRequestStatuses', $payoutRequestStatuses);
        }

        $queryBuilder = $queryBuilder->setFirstResult($offset)
                                     ->setMaxResults($limit)
                                     ->orderBy('PayoutRequest.payoutRequestId', $orderBy)
                                     ->getQuery();
        $result = $queryBuilder->getResult();

        return $result;
    }

    /**
     * Get PayoutRequest Count
     *
     * @param null $searchKeyword
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @param array $payoutRequestMethods
     * @param array $payoutRequestStatuses
     * @return array
     */
    public function getPayoutRequestCount (
        $searchKeyword = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null,
        $payoutRequestMethods = array(),
        $payoutRequestStatuses = array()
    )
    {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select ('count(PayoutRequest) AS PayoutRequestCount')
                     ->from('YilinkerCoreBundle:PayoutRequest', 'PayoutRequest')
                     ->leftJoin('YilinkerCoreBundle:User', 'User', 'WITH', 'User.userId = PayoutRequest.requestBy')
                     ->leftJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'User.userId = Store.user')
                     ->leftJoin('YilinkerCoreBundle:PayoutBatchDetail', 'PayoutBatchDetail', 'WITH', 'PayoutBatchDetail.payoutRequest = PayoutRequest.payoutRequestId AND PayoutBatchDetail.isDelete = :deleted')
                     ->where('User.userType != :userTypeBuyer')
                     ->setParameter('userTypeBuyer', User::USER_TYPE_BUYER)
                     ->setParameter('deleted', false);

        if (!is_null($searchKeyword)) {
            $queryBuilder->andWhere('CONCAT(User.firstName, " ", User.lastName) LIKE :searchKeyword OR
                                     User.email LIKE :searchKeyword OR
                                     PayoutRequest.bankAccountNumber LIKE :searchKeyword OR
                                     PayoutRequest.referenceNumber LIKE :searchKeyword OR
                                     PayoutRequest.bankAccountName LIKE :searchKeyword')
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if (!is_null($dateFrom)) {
            $queryBuilder->andWhere('PayoutRequest.dateAdded > :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->startOfDay()->format('Y-m-d H:i:s'));
        }

        if (!is_null($dateTo)) {
            $queryBuilder->andWhere('PayoutRequest.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo->endOfDay()->format('Y-m-d H:i:s'));
        }

        if (sizeof($payoutRequestMethods) > 0) {
            $queryBuilder->andWhere('PayoutRequest.payoutRequestMethod IN (:payoutRequestMethodId)')
                         ->setParameter('payoutRequestMethodId', $payoutRequestMethods);
        }

        if (sizeof($payoutRequestStatuses) > 0) {
            $queryBuilder->andWhere('PayoutRequest.payoutRequestStatus IN (:payoutRequestStatuses)')
                         ->setParameter('payoutRequestStatuses', $payoutRequestStatuses);
        }

        $queryBuilder = $queryBuilder->getQuery();

        return $queryBuilder->getSingleScalarResult();
    }

    /**
     * Get Payout method by payoutMethodId
     *  If payoutMethodId === null, It will return all payoutMethods
     *
     * @param null $payoutMethodId
     * @return array
     */
    public function getPayoutMethod ($payoutMethodId = null)
    {
        $payoutMethods = array (
            PayoutRequest::PAYOUT_METHOD_BANK => array (
                'id'   => PayoutRequest::PAYOUT_METHOD_BANK,
                'name' => 'Bank'
            ),
            PayoutRequest::PAYOUT_METHOD_CHEQUE => array (
                'id'   => PayoutRequest::PAYOUT_METHOD_CHEQUE,
                'name' => 'Cheque'
            )
        );

        if (!is_null($payoutMethodId) && isset($payoutMethods[$payoutMethodId])) {
            $payoutMethods = array($payoutMethods[$payoutMethodId]);
        }

        return array_values($payoutMethods);
    }

    /**
     * Get Payout status by payoutStatusId
     *  If payoutStatusId ===  null, It will return all payoutStatus
     *
     * @param null $payoutStatusId
     * @return array
     */
    public function getPayoutStatus ($payoutStatusId = null)
    {
        $payoutStatuses = array (
            PayoutRequest::PAYOUT_STATUS_PENDING => array (
                'id'   => PayoutRequest::PAYOUT_STATUS_PENDING,
                'name' => 'Pending'
            ),
            PayoutRequest::PAYOUT_STATUS_PAID => array (
                'id'   => PayoutRequest::PAYOUT_STATUS_PAID,
                'name' => 'Paid'
            )
        );

        if (!is_null($payoutStatusId) && isset($payoutStatuses[$payoutStatusId])) {
            $payoutStatuses = array($payoutStatuses[$payoutStatusId]);
        }

        return array_values($payoutStatuses);
    }

    /**
     * Get Payout requests by payout request ids
     *
     * @param $payoutRequestIds array
     * @return PayoutRequest
     */
    public function getPayoutRequestByIds ($payoutRequestIds = array())
    {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('PayoutRequest')
                     ->from('YilinkerCoreBundle:PayoutRequest', 'PayoutRequest')
                     ->leftJoin('YilinkerCoreBundle:PayoutBatchDetail', 'PayoutBatchDetail', 'WITH', 'PayoutBatchDetail.payoutRequest = PayoutRequest.payoutRequestId')
                     ->where('PayoutRequest.payoutRequestId in (:payoutRequestIds)')
                     //->andWhere('PayoutBatchDetail.payoutBatchDetailId is null OR PayoutBatchDetail.isDelete = true')
                     ->setParameter('payoutRequestIds', $payoutRequestIds);

        return $queryBuilder->getQuery()
                            ->getResult();
    }

    /**
     * Get Payout request by keyword
     *
     * @param string $searchKeyword
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getPayoutRequestByKeyword ($searchKeyword = '', $offset = 0, $limit = 10)
    {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('PayoutRequest')
                     ->from('YilinkerCoreBundle:PayoutRequest', 'PayoutRequest')
                     ->leftJoin('YilinkerCoreBundle:PayoutBatchDetail', 'PayoutBatchDetail', 'WITH', 'PayoutBatchDetail.payoutRequest = PayoutRequest.payoutRequestId AND PayoutBatchDetail.isDelete = :deleted')
                     ->where('PayoutRequest.referenceNumber LIKE :searchKeyword')
                     ->andWhere('PayoutBatchDetail.payoutBatchDetailId is null')
                     ->setParameter('searchKeyword', '%' . $searchKeyword . '%')
                     ->setParameter('deleted', false);

        return $queryBuilder->setFirstResult($offset)
                            ->setMaxResults($limit)
                            ->getQuery()
                            ->getResult();
    }

}
