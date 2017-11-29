<?php

namespace Yilinker\Bundle\BackendBundle\Services\Transaction;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\Payout;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellation;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationHead;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;

/**
 * Class TransactionManager
 */
class TransactionManager
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct (EntityManager $entityManager, AssetsHelper $assetsHelper)
    {
        $this->em = $entityManager;
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * Change OrderProductStatus to Buyer Refund Released
     * @param array $orderProductEntities
     * @param int $orderProductStatus
     * @return bool
     */
    public function changeProductStatus ($orderProductEntities = array(), $orderProductStatus)
    {
        $orderProductStatusRepository = $this->em->getRepository('YilinkerCoreBundle:OrderProductStatus');
        $orderProductStatusEntity = $orderProductStatusRepository->findByOrderProductStatusId($orderProductStatus);

        foreach ($orderProductEntities as $orderProductEntity) {
            $orderProductEntity->setOrderProductStatus($orderProductStatusEntity[0]);
            $this->em->persist($orderProductEntity);
            $this->addOrderProductHistory($orderProductStatusEntity[0], $orderProductEntity);
        }

        $this->em->flush();

        return true;
    }

    /**
     * Cancel transaction & create new orderProductHistory
     *
     * @param array $orderProductEntities
     * @param $reasonId
     * @param string $remarks
     * @param OrderProductStatus $orderProductStatusEntity
     * @param User|null $user
     * @param AdminUser|null $adminUser
     * @param int $isOpen
     * @param int $detailStatus
     * @return bool
     */
    public function cancelTransaction (
        $orderProductEntities = array(),
        $reasonId,
        $remarks = '',
        OrderProductStatus $orderProductStatusEntity,
        User $user = null,
        AdminUser $adminUser = null,
        $isOpen = 1,
        $detailStatus = OrderProductCancellationDetail::DETAIL_STATUS_OPEN
    )
    {
        $cancellationReasonRepository = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason');
        $cancellationReasonEntity = $cancellationReasonRepository->findByOrderProductCancellationReasonId($reasonId);
        $orderProductCancellationHead = $this->addOrderProductCancellationHead($cancellationReasonEntity[0], $user, $isOpen);

        foreach($orderProductEntities as $orderProductEntity) {
            $orderProductEntity->setOrderProductStatus($orderProductStatusEntity);
            $this->em->persist($orderProductEntity);
            $this->addOrderProductHistory($orderProductStatusEntity, $orderProductEntity);
            $this->addOrderProductCancellationDetail($orderProductCancellationHead, $orderProductEntity, $adminUser, $detailStatus, $remarks);
        }

        $this->em->flush();

        return true;
    }

    /**
     * Create new entry in OrderProductHistory
     * @param OrderProductStatus $orderProductStatus
     * @param OrderProduct $orderProduct
     */
    public function addOrderProductHistory (OrderProductStatus $orderProductStatus, OrderProduct $orderProduct)
    {
        $orderProductHistory = new OrderProductHistory();
        $orderProductHistory->setOrderProductStatus($orderProductStatus);
        $orderProductHistory->setOrderProduct($orderProduct);
        $orderProductHistory->setDateAdded(Carbon::now());

        $this->em->persist($orderProductHistory);
        $this->em->flush();
    }

    /**
     * Add OrderProductCancellationHead
     *
     * @param OrderProductCancellationReason $orderProductCancellationReason
     * @param User|null $user
     * @param int $isOpen
     * @return OrderProductCancellationHead
     */
    public function addOrderProductCancellationHead (
        OrderProductCancellationReason $orderProductCancellationReason,
        User $user = null,
        $isOpen = 1
    )
    {
        $orderProductCancellationHead = new OrderProductCancellationHead();
        $orderProductCancellationHead->setOrderProductCancellationReason($orderProductCancellationReason);
        $orderProductCancellationHead->setUser($user);
        $orderProductCancellationHead->setIsOpened($isOpen);
        $orderProductCancellationHead->setDateAdded(Carbon::now());

        $this->em->persist($orderProductCancellationHead);
        $this->em->flush();

        return $orderProductCancellationHead;
    }

    /**
     * Add OrderProductCancellationDetail
     *
     * @param OrderProductCancellationHead $orderProductCancellationHead
     * @param OrderProduct $orderProduct
     * @param AdminUser|null $adminUser
     * @param $status
     * @param string $remarks
     * @return OrderProductCancellationDetail
     */
    public function addOrderProductCancellationDetail (
        OrderProductCancellationHead $orderProductCancellationHead,
        OrderProduct $orderProduct,
        AdminUser $adminUser = null,
        $status,
        $remarks = ''
    )
    {
        $orderProductCancellationDetail = new OrderProductCancellationDetail();
        $orderProductCancellationDetail->setOrderProduct($orderProduct);
        $orderProductCancellationDetail->setOrderProductCancellationHead($orderProductCancellationHead);
        $orderProductCancellationDetail->setRemarks($remarks);
        $orderProductCancellationDetail->setAdminUser($adminUser);
        $orderProductCancellationDetail->setStatus($status);

        $this->em->persist($orderProductCancellationDetail);
        $this->em->flush();

        return $orderProductCancellationDetail;
    }

    /**
     * Approve Or Deny Cancelled Transaction and add OrderProduct History
     *
     * @param array $orderProductEntities
     * @param $remarks
     * @param $isApprove
     * @param $adminUser
     * @return bool
     */
    public function approveOrDenyCancelledTransaction (
        $orderProductEntities = array(),
        $remarks,
        $isApprove,
        $adminUser
    )
    {
        $orderProductStatusRepository = $this->em->getRepository('YilinkerCoreBundle:OrderProductStatus');
        $status = OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED;
        $detailStatus = OrderProductCancellationDetail::DETAIL_STATUS_APPROVED;

        if ($isApprove === false) {
            $status = OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED;
            $detailStatus = OrderProductCancellationDetail::DETAIL_STATUS_DENIED;
        }

        $orderProductStatusEntity = $orderProductStatusRepository->findByOrderProductStatusId($status);

        foreach($orderProductEntities as $orderProductEntity) {
            $orderProductCancellationHead = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationDetail')
                                                     ->findByOrderProduct($orderProductEntity->getOrderProductId())[0]
                                                     ->getOrderProductCancellationHead();
            $orderProductEntity->setOrderProductStatus($orderProductStatusEntity[0]);
            $this->em->persist($orderProductEntity);
            $this->addOrderProductHistory($orderProductStatusEntity[0], $orderProductEntity);
            $this->addOrderProductCancellationDetail($orderProductCancellationHead, $orderProductEntity, $adminUser, $detailStatus, $remarks);
            $this->updateIsOpenIfDone($orderProductCancellationHead);
        }

        return true;
    }

    /**
     * @param OrderProductCancellationHead $orderProductCancellationHead
     */
    public function updateIsOpenIfDone (OrderProductCancellationHead $orderProductCancellationHead)
    {
        $orderProductCancellationDetails = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationDetail')
                                                    ->findByOrderProductCancellationHead ($orderProductCancellationHead->getOrderProductCancellationHeadId());
        $isOpen = true;

        foreach ($orderProductCancellationDetails as $orderProductCancellationDetail) {

            if ((int) $orderProductCancellationDetail->getStatus() === OrderProductCancellationDetail::DETAIL_STATUS_APPROVED ||
                (int) $orderProductCancellationDetail->getStatus() === OrderProductCancellationDetail::DETAIL_STATUS_DENIED) {
                $isOpen = false;
            }

        }

        if ($isOpen === false) {
            $orderProductCancellationHead->setIsOpened(0);
            $this->em->persist($orderProductCancellationHead);
            $this->em->flush();
        }

    }

    /**
     * @param $orderId
     * @return bool
     */
    public function getRemarksByOrder ($orderId)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder->select(array (
                            "o.orderId",
                            "op.orderProductId",
                            "opcHead.orderProductCancellationHeadId",
                            "opcDetail.orderProductCancellationDetailId",
                            "op.productName",
                            "opcDetail.remarks AS remarkByCsr",
                            "opcHead.remarks AS remarkBySeller",
                            "COALESCE(au.adminUserId, 0) AS isAdmin",
                            "opcHead.isOpened",
                            "opcHead.dateAdded",
                            "opcReason.reason",
                            "CONCAT(au.firstName, ' ', au.lastName) AS csr",
                            "CONCAT(u.firstName, ' ', u.lastName) AS seller"
                        ) )
            ->from("YilinkerCoreBundle:UserOrder", "o")
            ->join("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId")
            ->join("YilinkerCoreBundle:OrderProductCancellationDetail", "opcDetail", "WITH", "opcDetail.orderProduct = op.orderProductId")
            ->join("YilinkerCoreBundle:OrderProductCancellationHead", "opcHead", "WITH", "opcHead.orderProductCancellationHeadId = opcDetail.orderProductCancellationHead")
            ->join("YilinkerCoreBundle:OrderProductCancellationReason", "opcReason", "WITH", "opcReason.orderProductCancellationReasonId = opcHead.orderProductCancellationReason")
            ->leftJoin("YilinkerCoreBundle:AdminUser", "au", "WITH", "au.adminUserId = opcDetail.adminUser")
            ->leftJoin("YilinkerCoreBundle:User", "u", "WITH", "u.userId = opcHead.user")
            ->where("o.orderId = :orderId")
            ->setParameter('orderId', $orderId)
            ->groupBy("opcHead.orderProductCancellationHeadId, opcDetail.orderProductCancellationDetailId")
            ->orderBy("opcHead.dateAdded", "DESC");

        $query = $queryBuilder->getquery();
        $unGroupedRemarks = $query->getScalarResult();
        $remarksGroupByHead = array();

        if ($unGroupedRemarks) {

            foreach ($unGroupedRemarks as $unGroupedRemark) {
                $headId = $unGroupedRemark['orderProductCancellationHeadId'];
                $remarks = $unGroupedRemark['remarkBySeller'];
                $user = $unGroupedRemark['seller'];
                $userKey = $unGroupedRemark['isAdmin'] ? 'admin' : 'seller';
                $orderProductId = $unGroupedRemark['orderProductId'];

                if ($unGroupedRemark['isAdmin']) {
                    $remarks = $unGroupedRemark['remarkByCsr'];
                    $user = $unGroupedRemark['csr'];
                }

                $remarksGroupByHead[$headId]['details'][$userKey] = array (
                    'remarks' => $remarks,
                    'user' => $user,
                    'isAdmin' => $unGroupedRemark['isAdmin'],
                    'dateAdded' => $unGroupedRemark['dateAdded']
                );
                $remarksGroupByHead[$headId]['isOpen'] = $unGroupedRemark['isOpened'];
                $remarksGroupByHead[$headId]['reason'] = $unGroupedRemark['reason'];
                $remarksGroupByHead[$headId]['orderId'] = $unGroupedRemark['orderId'];
                $remarksGroupByHead[$headId]['products'][$orderProductId] = $unGroupedRemark['productName'];
            }

        }

        return $remarksGroupByHead;
    }

    public function getPayoutHistory($keyword, $dateFrom, $dateTo, $limit, $offset)
    {
        $payoutHistory = array(
            "payouts" => array(),
            "payoutCount" => 0
        );

        $payoutRepository = $this->em->getRepository("YilinkerCoreBundle:Payout");

        $payoutData = $payoutRepository->getPayouts($keyword, $dateFrom, $dateTo, $limit, $offset);

        $payoutHistory["payoutCount"] = $payoutData["payoutCount"];

        foreach($payoutData["payouts"] as $payout){

            $user = $payout->getUser();

            $orderProducts = array();
            $documents = array();

            $payoutOrderProducts = $payout->getPayoutOrderProducts();
            $payoutDocuments = $payout->getPayoutDocuments();

            $currency = $payout->getCurrency()->getSymbol();

            foreach($payoutOrderProducts as $payoutOrderProduct){

                $orderProduct = $payoutOrderProduct->getOrderProduct();

                array_push($orderProducts, array(
                    "orderProductId" => $orderProduct->getOrderProductId(),
                    "name" => $orderProduct->getProduct()->getName(),
                    "amount" => $currency." ".number_format($payoutOrderProduct->getAmount(), 2),
                    "dateCreated" => $payoutOrderProduct->getDateCreated()->format("m/d/Y")
                ));
            }

            foreach($payoutDocuments as $payoutDocument){

                array_push($documents, array(
                    "path" => $this->assetsHelper->getUrl($payoutDocument->getFilepath(), "payout")
                ));
            }

            array_push($payoutHistory["payouts"], array(
                "referenceNumber" => $payout->getReferenceNumber(),
                "storeName" => $user->getStore()->getStoreName(),
                "email" => $user->getEmail(),
                "supportCsr" => $payout->getAdminUser()->getFullName(),
                "dateCreated" => $payout->getDateCreated()->format("m/d/Y"),
                "dateModified" => $payout->getDateModified()->format("m/d/Y"),
                "currency" => $currency,
                "amount" => $currency." ".number_format($payout->getAmount(), 2),
                "status" => $payout->getStatus() == Payout::PAYOUT_STATUS_INCOMPLETE? "Incomplete" : "Completed",
                "orderProducts" => $orderProducts,
                "documents" => $documents
            ));
        }

        return $payoutHistory;
    }
}
