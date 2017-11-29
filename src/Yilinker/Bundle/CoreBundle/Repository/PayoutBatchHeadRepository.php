<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

class PayoutBatchHeadRepository extends EntityRepository
{

    /**
     * Get PayoutBatchHead
     *
     * @param null $searchKeyword
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getPayoutBatchHead (
        $searchKeyword = null,
        Carbon $dateFrom = null,
        Carbon $dateTo = null,
        $offset = 1,
        $limit = 10
    ) {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('PayoutBatchHead AS entity, SUM(PayoutRequest.netAmount) AS totalAmount, COUNT(PayoutRequest.payoutRequestId) AS totalRequest')
                     ->from('YilinkerCoreBundle:PayoutBatchHead', 'PayoutBatchHead')
                     ->leftJoin('YilinkerCoreBundle:PayoutBatchDetail', 'PayoutBatchDetail', 'WITH', 'PayoutBatchHead.payoutBatchHeadId = PayoutBatchDetail.payoutBatchHead AND PayoutBatchDetail.isDelete = :deleted')
                     ->leftJoin('YilinkerCoreBundle:AdminUser', 'AdminUser', 'WITH', 'AdminUser.adminUserId = PayoutBatchHead.adminUser')
                     ->leftJoin('YilinkerCoreBundle:PayoutRequest', 'PayoutRequest', 'WITH', 'PayoutRequest.payoutRequestId = PayoutBatchDetail.payoutRequest')
                     ->where('PayoutBatchHead.isDelete = :deleted')
                     ->setParameter('deleted', false);

        if (!is_null($searchKeyword)) {
            $queryBuilder->andWhere('CONCAT(AdminUser.firstName, " ", AdminUser.lastName) LIKE :searchKeyword OR
                                     PayoutBatchHead.batchNumber LIKE :searchKeyword')
                         ->setParameter('searchKeyword', $searchKeyword);
        }

        if (!is_null($dateFrom)) {
            $queryBuilder->andWhere('PayoutBatchHead.dateAdded > :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->startOfDay()->format('Y-m-d'));
        }

        if (!is_null($dateTo)) {
            $queryBuilder->andWhere('PayoutBatchHead.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo->endOfDay()->format('Y-m-d'));
        }

        $queryBuilder = $queryBuilder->setFirstResult($offset)
                                     ->setMaxResults($limit)
                                     ->groupBy('PayoutBatchHead.payoutBatchHeadId')
                                     ->getQuery();
        $result = $queryBuilder->getResult();

        return $result;
    }

    /**
     * Get PayoutBatchHead count
     *
     * @param null $searchKeyword
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @return mixed
     */
    public function getPayoutBatchHeadCount ($searchKeyword = null, Carbon $dateFrom = null, Carbon $dateTo = null)
    {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('COUNT(DISTINCT PayoutBatchHead.payoutBatchHeadId)')
                     ->from('YilinkerCoreBundle:PayoutBatchHead', 'PayoutBatchHead')
                     ->leftJoin('YilinkerCoreBundle:PayoutBatchDetail', 'PayoutBatchDetail', 'WITH', 'PayoutBatchHead.payoutBatchHeadId = PayoutBatchDetail.payoutBatchHead AND PayoutBatchDetail.isDelete = :deleted')
                     ->leftJoin('YilinkerCoreBundle:AdminUser', 'AdminUser', 'WITH', 'AdminUser.adminUserId = PayoutBatchHead.adminUser')
                     ->leftJoin('YilinkerCoreBundle:PayoutRequest', 'PayoutRequest', 'WITH', 'PayoutRequest.payoutRequestId = PayoutBatchDetail.payoutRequest')
                     ->where('PayoutBatchHead.isDelete = :deleted')
                     ->setParameter('deleted', false);

        if (!is_null($searchKeyword)) {
            $queryBuilder->andWhere('CONCAT(AdminUser.firstName, " ", AdminUser.lastName) LIKE :searchKeyword OR
                                     PayoutBatchHead.batchNumber LIKE :searchKeyword')
                         ->setParameter('searchKeyword', $searchKeyword);
        }

        if (!is_null($dateFrom)) {
            $queryBuilder->andWhere('PayoutBatchHead.dateAdded > :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->startOfDay()->format('Y-m-d'));
        }

        if (!is_null($dateTo)) {
            $queryBuilder->andWhere('PayoutBatchHead.dateAdded <= :dateTo')
                         ->setParameter('dateTo', $dateTo->endOfDay()->format('Y-m-d'));
        }

        $queryBuilder = $queryBuilder->getQuery();
        $result = $queryBuilder->getSingleScalarResult();

        return $result;
    }

    /**
     * Get PayoutBatch total amount
     *
     * @param null $searchKeyword
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @return mixed
     */
    public function getPayoutBatchTotalAmount ($searchKeyword = null, Carbon $dateFrom = null, Carbon $dateTo = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('grandTotalAmount', 'grandTotalAmount');

        $sql = "
            SELECT SUM(headTbl.totalRequestedAmountPerHead) AS grandTotalAmount
            FROM
            (
                SELECT
                    SUM(PayoutRequest.`requested_amount`) as totalRequestedAmountPerHead
                FROM PayoutBatchHead
                LEFT JOIN PayoutBatchDetail
                    ON PayoutBatchDetail.`payout_batch_head_id` = PayoutBatchHead.`payout_batch_head_id` AND PayoutBatchDetail.`is_delete` = :deleted
                LEFT JOIN PayoutRequest
                    ON PayoutRequest.`payout_request_id` = PayoutBatchDetail.`payout_request_id`
                LEFT JOIN AdminUser
                  ON AdminUser.`admin_user_id` = PayoutBatchHead.`admin_user_id`
                WHERE
                  AdminUser.`is_active` = :isActive AND
                  PayoutBatchHead.is_delete = :deleted
        ";

        if ($searchKeyword !== null) {
            $sql .= " AND (CONCAT(AdminUser.first_name, ' ', AdminUser.last_name) LIKE :searchKeyword OR
                      PayoutBatchHead.batch_number LIKE :searchKeyword) ";
        }

        if ($dateFrom !== null) {
            $sql .= " AND PayoutBatchHead.date_added >= :dateFrom ";
        }

        if ($dateTo !== null) {
            $sql .= " AND PayoutBatchHead.date_added <= :dateTo ";
        }

        $sql .= "
            GROUP BY PayoutBatchHead.payout_batch_head_id ) AS headTbl
        ";

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter('isActive', true);
        $query->setParameter('deleted', false);

        if (!is_null($searchKeyword)) {
            $query->setParameter('searchKeyword', $searchKeyword);
        }

        if (!is_null($dateFrom)) {
            $query->setParameter('dateFrom', $dateFrom->endOfDay()->toDateTimeString('Y-m-d H:i:s'));
        }

        if (!is_null($dateTo)) {
            $query->setParameter('dateTo', $dateTo->endOfDay()->toDateTimeString('Y-m-d H:i:s'));
        }

        $result = $query->getSingleScalarResult();

        return $result;
    }

}
