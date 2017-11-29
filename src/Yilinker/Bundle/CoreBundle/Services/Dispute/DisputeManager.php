<?php

namespace Yilinker\Bundle\CoreBundle\Services\Dispute;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\Entity\Dispute;
use Yilinker\Bundle\CoreBundle\Entity\DisputeDetail;
use Yilinker\Bundle\CoreBundle\Entity\DisputeHistory;
use Yilinker\Bundle\CoreBundle\Entity\DisputeMessage;
use Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;

/**
 * Class DisputeManager
 * @package Yilinker\Bundle\CoreBundle\Services\Dispute
 */
class DisputeManager
{

    const PAGE_LIMIT = 30;

    /**
     * Doctrine entity manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct($entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Add Dispute
     *
     * @param DisputeStatusType $disputeStatusType
     * @param User $disputer
     * @param null $description
     * @param null $ticket
     * @param $orderProductCancellationReason
     * @return Dispute
     */
    public function addDispute(
        DisputeStatusType $disputeStatusType,
        User $disputer,
        $description = null,
        $ticket = null,
        $orderProductCancellationReason
    )
    {
        $dispute = new Dispute();
        $dispute->setDisputeStatusType($disputeStatusType);
        $dispute->setDisputer($disputer);
        $dispute->setDescription($description);
        $dispute->setTicket($ticket);
        $dispute->setDateAdded(Carbon::now());
        $dispute->setLastModifiedDate(Carbon::now());
        $dispute->setOrderProductCancellationReason($orderProductCancellationReason);
        $this->em->persist($dispute);
        $this->em->flush();

        return $dispute;
    }

    /**
     * Add dispute detail
     *
     * @param Dispute $dispute
     * @param OrderProduct $orderProduct
     * @param User $disputee
     * @param $status
     * @return DisputeDetail
     */
    public function addDisputeDetail (Dispute $dispute, OrderProduct $orderProduct, User $disputee, $status = DisputeDetail::DETAIL_STATUS_OPEN)
    {
        $disputeDetail = new DisputeDetail();
        $disputeDetail->setDispute($dispute);
        $disputeDetail->setOrderProduct($orderProduct);
        $disputeDetail->setDisputee($disputee);
        $disputeDetail->setStatus($status)
                      ->setOrderProductStatus($orderProduct->getOrderProductStatus());
        $this->em->persist($disputeDetail);
        $this->em->flush();

        return $disputeDetail;
    }


    /**
     * Add Dispute History
     *
     * @param Dispute $dispute
     * @param DisputeStatusType $disputeStatusType
     * @return DisputeHistory
     */
    public function addDisputeHistory (Dispute $dispute, DisputeStatusType $disputeStatusType)
    {
        $disputeHistory = new DisputeHistory();
        $disputeHistory->setDispute($dispute);
        $disputeHistory->setDisputeStatusType($disputeStatusType);
        $disputeHistory->setDateAdded(Carbon::now());
        $this->em->persist($disputeHistory);
        $this->em->flush();

        return $disputeHistory;
    }

    /**
     * Add Dispute Message
     *
     * @param Dispute $dispute
     * @param AdminUser $authorId
     * @param null $message
     * @param int $isAdmin
     * @return DisputeMessage
     */
    public function addDisputeMessage (Dispute $dispute, AdminUser $authorId = null, $message = null, $isAdmin = 0)
    {
        $disputeMessage = new DisputeMessage();
        $disputeMessage->setDispute($dispute);
        $disputeMessage->setAuthor($authorId);
        $disputeMessage->setMessage($message);
        $disputeMessage->setDateAdded(Carbon::now());
        $disputeMessage->setIsAdmin($isAdmin);
        $this->em->persist($disputeMessage);
        $this->em->flush();

        return $disputeMessage;
    }

    /**
     * Create Dispute
     *
     * @param $orderProductEntities
     * @param User $disputer
     * @param $description
     * @param $message
     * @param $orderProductStatus
     * @param $orderProductReason
     * @return null|Dispute
     */
    public function addNewCase (
        $orderProductEntities,
        User $disputer,
        $description,
        $message,
        $orderProductStatus,
        $orderProductReason
    )
    {
        $disputeStatusTypeReference = $this->em->getReference(
                                                    'YilinkerCoreBundle:DisputeStatusType',
                                                     DisputeStatusType::STATUS_TYPE_OPEN
                                                 );
        $orderProductStatusReference = $this->em->getReference(
                                                     'YilinkerCoreBundle:OrderProductStatus',
                                                     $orderProductStatus
                                                 );
        $orderProductCancellationReasonReference = $this->em->getReference (
                                                      'YilinkerCoreBundle:OrderProductCancellationReason',
                                                       $orderProductReason
                                                  );
        $dispute = null;

        if (sizeof($orderProductEntities) > 0) {
            $userAndOrderProductContainer = array ();

            foreach ($orderProductEntities as $orderProductEntity) {
                $disputee = $orderProductEntity->getProduct()->getUser();
                $userAndOrderProductContainer[$disputee->getUserId()][] = array (
                    'user' => $disputee,
                    'orderProduct' => $orderProductEntity
                );
            }

            foreach ($userAndOrderProductContainer as $userAndOrderProductArray) {
                $ticket = $this->generateNewTicket($disputer->getUserId());
                $dispute = $this->addDispute(
                                      $disputeStatusTypeReference,
                                      $disputer,
                                      $description,
                                      $ticket,
                                      $orderProductCancellationReasonReference
                                  );

                foreach ($userAndOrderProductArray as $userAndOrderProduct) {
                    $userAndOrderProduct['orderProduct']->setOrderProductStatus($orderProductStatusReference);
                    $this->addDisputeDetail($dispute, $userAndOrderProduct['orderProduct'], $userAndOrderProduct['user']);
                }

                $this->addDisputeMessage($dispute, null, $message);
                $this->addDisputeHistory($dispute, $disputeStatusTypeReference);

            }

        }

        return $dispute;
    }

    /**
     * Generate new Ticket
     *
     * @param $userId
     * @return string
     */
    private function generateNewTicket ($userId)
    {
        return $userId . '-' . rand(0, 9999) . '-' . date_format(Carbon::now(), 'Y-d');
    }

    /**
     * Get Dispute Head with detail
     *
     * @param User $user
     * @param Dispute|null $dispute
     * @param null $disputeStatusType
     * @param null $searchKeyword
     * @param int $offset
     * @param int $limit
     */
    public function getCaseWithDetail (
        User $user = null,
        $disputeStatusType = null,
        Dispute $dispute = null,
        $searchKeyword = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT
    )
    {
        $offset = ($offset - 1) * $limit;
        $disputeStatusTypeReference = null;

        if ($disputeStatusType !== null) {
            $disputeStatusTypeReference = $this->em->getReference('YilinkerCoreBundle:DisputeStatusType', $disputeStatusType);
        }
        $tbDispute = $this->em->getRepository('YilinkerCoreBundle:Dispute');

        $disputeHead = $tbDispute->getCase(
            $user,
            $dispute,
            $disputeStatusTypeReference,
            $searchKeyword,
            null,
            null,
            null,
            $offset,
            $limit
        );

        foreach ($disputeHead['cases'] as &$dispute) {
            $dispute['object'] = $tbDispute->find($dispute['disputeId']);
            if ((int) $dispute['orderProductStatusId'] === OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED) {
                $dispute['orderProductStatus'] = 'Refund';
            } else if ((int) $dispute['orderProductStatusId'] === OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED) {
                $dispute['orderProductStatus'] = 'Replacement';
            } else {
                $dispute['orderProductStatus'] = 'Invalid OrderProductStatus';
            }

            $disputeDetailArray = $this->em->getRepository('YilinkerCoreBundle:DisputeDetail')
                                           ->findByDispute($dispute['disputeId']);
            $disputeMessageArray = $this->em->getRepository('YilinkerCoreBundle:DisputeMessage')
                                            ->findByDispute($dispute['disputeId']);

            foreach ($disputeDetailArray as $disputeDetail) {
                // $dispute['products'] should not be use. instead use disputeDetails
                $dispute['products'][] = $disputeDetail->getOrderProduct();
                $dispute['transaction'] = $disputeDetail->getOrderProduct()->getOrder();
                $dispute['disputeDetails'][] = $disputeDetail;
            }

            foreach ($disputeMessageArray as $disputeMessage ) {
                $authorEntity = $disputeMessage->getAuthor();

                if ($authorEntity === null) {
                    $authorEntity = $disputeMessage->getDispute()->getDisputer();
                }

                $dispute['message'][] = array (
                    'isAdmin' => $disputeMessage->getIsAdmin(),
                    'message' => $disputeMessage->getMessage(),
                    'dateAdded' => $disputeMessage->getDateAdded(),
                    'authorEntity' => $authorEntity
                );
            }
        }

        return $disputeHead;
    }

    /**
     * Update Dispute Detail
     *
     * @deprecated use DisputeService to approve or reject a dispute
     * @param $disputeDetailIds
     * @param bool $isApproved
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateDisputeDetail ($disputeDetailIds, $isApproved)
    {
        $isSuccessful = false;
        $disputeEntity = null;

        if (!is_array($disputeDetailIds)) {
            $disputeDetailIds = array($disputeDetailIds);
        }

        foreach ($disputeDetailIds as $disputeDetailId) {
            $disputeDetailEntity = $this->em->getRepository('YilinkerCoreBundle:DisputeDetail')->find($disputeDetailId);

            if ($disputeDetailEntity instanceof DisputeDetail) {

                $orderProductStatusId = (int) $disputeDetailEntity->getOrderProductStatus()->getOrderProductStatusId();
                $newOrderProductStatus = null;

                if ($isApproved === true) {

                    if ($orderProductStatusId === OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED) {
                        $newOrderProductStatus = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_ITEM_RETURN_BOOKED_FOR_PICKUP);
                    }
                    else if ($orderProductStatusId === OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED) {
                        $newOrderProductStatus = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_ITEM_REFUND_BOOKED_FOR_PICKUP);
                    }

                }
                else {

                    if ($orderProductStatusId === OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED) {
                        $newOrderProductStatus = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT);
                    }
                    else if ($orderProductStatusId === OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED) {
                        $newOrderProductStatus = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_THE_SPOT);
                    }

                }

                if ($newOrderProductStatus !== null) {
                    $disputeEntity = $disputeDetailEntity->getDispute();
                    $disputeDetailEntity->setStatus(DisputeDetail::DETAIL_STATUS_CLOSE);
                    $orderProductEntity = $disputeDetailEntity->getOrderProduct();
                    $orderProductEntity->setOrderProductStatus($newOrderProductStatus);
                    $isSuccessful = true;

                    $this->em->flush();
                    $unHeldOrderProductStatus = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_SELLER_PAYOUT_UN_HELD);

                    if ( (int) $newOrderProductStatus->getOrderProductStatusId() === OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT ||
                        (int) $newOrderProductStatus->getOrderProductStatusId() === OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_THE_SPOT) {
                        $orderProductEntity->setOrderProductStatus($unHeldOrderProductStatus);
                    }

                    $this->em->flush();
                }
            }

        }

        if ($disputeEntity !== null && $disputeEntity !== '') {

            $disputeDetails = $this->em->getRepository('YilinkerCoreBundle:DisputeDetail')->findBy(array(
                'dispute' => $disputeEntity->getDisputeId(),
                'status' => DisputeDetail::DETAIL_STATUS_OPEN
            ));

            if (sizeof($disputeDetails) === 0 || $disputeDetails === null) {
                $this->updateDisputeStatus ($disputeEntity);
            }

        }

        return $isSuccessful;
    }

    /**
     * Update Dispute Status
     *
     * @param Dispute $dispute
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     */
    public function updateDisputeStatus (Dispute $dispute)
    {
        $disputeStatusCloseReference = $this->em->getReference('YilinkerCoreBundle:DisputeStatusType', DisputeStatusType::STATUS_TYPE_CLOSE);
        $dispute->setDisputeStatusType($disputeStatusCloseReference);
        $dispute->setLastModifiedDate(Carbon::now());
        $this->addDisputeHistory ($dispute, $disputeStatusCloseReference);
        $this->em->flush();

        return true;
    }

    /**
     * Get Order Product Reason By Type
     *
     * @param $reasonType
     * @param $userType
     * @return array
     */
    public function getOrderProductReasonByType ($reasonType, $userType = null)
    {
        $orderProductReasonArray = array();
        if ($userType !== null) {
            $orderProductReason = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason')
                                           ->findBy(array(
                                               'reasonType' => $reasonType,
                                               'userType' => $userType
                                           ));
        }
        else {
            $orderProductReason = $this->em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason')
                                           ->findBy(array(
                                               'reasonType' => $reasonType
                                           ));
        }

        if ($orderProductReason) {

            foreach ($orderProductReason as $reason) {
                $userType = $reason->getUserType() === OrderProductCancellationReason::USER_TYPE_BUYER ? 'buyer' : 'seller';
                $orderProductReasonArray[$userType][] = array(
                    'id' => $reason->getOrderProductCancellationReasonid(),
                    'reason' => $reason->getReason()
                );
            }

        }

        return $orderProductReasonArray;
    }

}
