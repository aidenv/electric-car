<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Dispute;
use Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;

class DisputeRepository extends EntityRepository
{

    const PAGE_LIMIT = 30;

    /**
     * Get Cases
     *
     * @param User|null $user
     * @param Dispute|null $dispute
     * @param DisputeStatusType|null $disputeStatusType
     * @param null $searchKeyword
     * @param OrderProductStatus|null $orderProductStatus
     * @param null $dateFrom
     * @param null $dateTo
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getCase (
        User $user = null,
        Dispute $dispute = null,
        DisputeStatusType $disputeStatusType = null,
        $searchKeyword = null,
        OrderProductStatus $orderProductStatus = null,
        $dateFrom = null,
        $dateTo = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT
    )
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array(
                            'Dispute.disputeId',
                            'Dispute.ticket',
                            'Dispute.description',
                            'DisputeStatusType.name AS status',
                            "CONCAT(Disputee.firstName, ' ', Disputee.lastName) AS disputeeFullName",
                            "Disputee.contactNumber AS disputeeContactNumber",
                            "CONCAT(Disputer.firstName, ' ', Disputer.lastName) AS disputerFullName",
                            'Dispute.lastModifiedDate',
                            'Dispute.dateAdded',
                            'DisputeStatusType.disputeStatusTypeId',
                            'OrderProductStatus.orderProductStatusId',
                            'OrderProductStatus.name AS orderProductStatus',
                            'OrderProductCancellationReason.orderProductCancellationReasonId AS reasonId',
                            'OrderProductCancellationReason.reason AS reason',
                        ))
                    ->from('YilinkerCoreBundle:Dispute', 'Dispute')
                    ->leftJoin('YilinkerCoreBundle:DisputeDetail', 'DisputeDetail', 'WITH', 'DisputeDetail.dispute = Dispute.disputeId')
                    ->leftJoin('YilinkerCoreBundle:DisputeStatusType', 'DisputeStatusType', 'WITH', 'DisputeStatusType.disputeStatusTypeId = Dispute.disputeStatusType')
                    ->leftJoin('YilinkerCoreBundle:User', 'Disputee', 'WITH', 'Disputee.userId = DisputeDetail.disputee')
                    ->leftJoin('YilinkerCoreBundle:User', 'Disputer', 'WITH', 'Disputer.userId = Dispute.disputer')
                    ->leftJoin('YilinkerCoreBundle:OrderProductStatus', 'OrderProductStatus', 'WITH', 'OrderProductStatus.orderProductStatusId = DisputeDetail.orderProductStatus')
                    ->leftJoin('YilinkerCoreBundle:OrderProductCancellationReason', 'OrderProductCancellationReason', 'WITH', 'OrderProductCancellationReason.orderProductCancellationReasonId = Dispute.orderProductCancellationReason');

        if ($user !== null) {
            $queryBuilder->where('Disputer.userId = :disputer')
                ->setParameter(':disputer', $user->getUserId());
        }

        if ($dispute !== null) {
            $queryBuilder->where('Dispute.disputeId = :disputeId')
                         ->setParameter(':disputeId', $dispute->getDisputeId());
        }

        if ($disputeStatusType !== null) {
            $queryBuilder->andWhere('DisputeStatusType.disputeStatusTypeId = :disputeStatusTypeId')
                         ->setParameter(':disputeStatusTypeId', $disputeStatusType);
        }

        if ($searchKeyword !== null) {
            $queryBuilder->andWhere("(Dispute.ticket LIKE :searchKeyword OR CONCAT(Disputee.firstName, ' ', Disputee.lastName) LIKE :searchKeyword OR CONCAT(Disputer.firstName, ' ', Disputer.lastName) LIKE :searchKeyword)")
                         ->setParameter('searchKeyword', '%' . $searchKeyword . '%');
        }

        if ($orderProductStatus !== null) {
            $queryBuilder->andWhere('OrderProductStatus.orderProductStatusId = :orderProductStatus')
                         ->setParameter('orderProductStatus', $orderProductStatus);
        }

        if ($dateFrom !== null) {
            $dateFrom = new Carbon($dateFrom);
            $queryBuilder->andWhere('Dispute.dateAdded >= :dateFrom')
                         ->setParameter('dateFrom', $dateFrom->startOfDay());
        }

        if ($dateTo !== null) {
            $dateTo = new Carbon($dateTo);
            $queryBuilder->andWhere('Dispute.dateAdded < :dateTo')
                         ->setParameter('dateTo', $dateTo->endOfDay());
        }

        $count = count($queryBuilder->getQuery()->getResult());

        $qbResult = $queryBuilder->setFirstResult($offset)
                                 ->setMaxResults($limit)
                                 ->orderBy('DisputeStatusType.disputeStatusTypeId', 'ASC')
                                 ->addOrderBy('Dispute.dateAdded', 'DESC')
                                 ->groupBy('Dispute.disputeId')
                                 ->getQuery();

        $cases = $qbResult->getResult();

        $result = compact (
            'cases',
            'count'
        );

        return $result;
    }

    /**
     * Retrieve total number of case
     *
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param null $statuses
     * @return int
     */
    public function getTotalNumberOfCases(\DateTime $dateFrom = null, \DateTime $dateTo = null, $statuses = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select('count(dispute)')
                     ->from('YilinkerCoreBundle:Dispute', 'dispute')
                     ->innerJoin(
                         'YilinkerCoreBundle:DisputeDetail', 
                         'detail', 'WITH', 
                         'detail.dispute = dispute.disputeId'
                     );
        
        if ($dateFrom !== null) {
            $queryBuilder->andWhere('dispute.dateAdded >= :dateFrom')
                         ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder->andWhere('dispute.dateAdded < :dateTo')
                         ->setParameter('dateTo', $dateTo);
        }

        if ($statuses !== null) {

            if (is_array($statuses)) {
                $statuses = array($statuses);
            }

            $queryBuilder->andWhere('dispute.disputeStatusType IN (:statuses)')
                         ->setParameter('statuses', $statuses);

        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getValidStatusesForDispute ()
    {
        return array(
            OrderStatus::PAYMENT_CONFIRMED,
            OrderStatus::ORDER_DELIVERED,
            OrderStatus::ORDER_FOR_PICKUP,
            OrderStatus::COD_WAITING_FOR_PAYMENT
        );
    }

    public function getValidStatusesForDisputeSeller ()
    {
        return array(
            OrderStatus::PAYMENT_CONFIRMED,
            OrderStatus::ORDER_DELIVERED,
            OrderStatus::ORDER_FOR_PICKUP
        );
    }
}
