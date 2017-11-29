<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Dispute
 */
class Dispute
{

    CONST APPROVE_REFUND = 0;
    CONST APPROVE_REPLACE_DIFF_ITEM = 1; // replace with voucher code

    /**
     * @var integer
     */
    private $disputeId;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $lastModifiedDate;

    /**
     * @var string
     */
    private $ticket;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType
     */
    private $disputeStatusType;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $disputer;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $disputeDetails;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason
     */
    private $orderProductCancellationReason;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->disputeDetails = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get disputeId
     *
     * @return integer 
     */
    public function getDisputeId()
    {
        return $this->disputeId;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Dispute
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Dispute
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set lastModifiedDate
     *
     * @param \DateTime $lastModifiedDate
     * @return Dispute
     */
    public function setLastModifiedDate($lastModifiedDate)
    {
        $this->lastModifiedDate = $lastModifiedDate;

        return $this;
    }

    /**
     * Get lastModifiedDate
     *
     * @return \DateTime 
     */
    public function getLastModifiedDate()
    {
        return $this->lastModifiedDate;
    }

    /**
     * Set ticket
     *
     * @param string $ticket
     * @return Dispute
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return string 
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * Set disputeStatusType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType $disputeStatusType
     * @return Dispute
     */
    public function setDisputeStatusType(\Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType $disputeStatusType = null)
    {
        $this->disputeStatusType = $disputeStatusType;

        return $this;
    }

    /**
     * Get disputeStatusType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType 
     */
    public function getDisputeStatusType()
    {
        return $this->disputeStatusType;
    }

    /**
     * Set disputer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $disputer
     * @return Dispute
     */
    public function setDisputer(\Yilinker\Bundle\CoreBundle\Entity\User $disputer = null)
    {
        $this->disputer = $disputer;

        return $this;
    }

    /**
     * Get disputer
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getDisputer()
    {
        return $this->disputer;
    }

    /**
     * Add disputeDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeDetail $disputeDetails
     * @return Dispute
     */
    public function addDisputeDetail(\Yilinker\Bundle\CoreBundle\Entity\DisputeDetail $disputeDetails)
    {
        $this->disputeDetails[] = $disputeDetails;

        return $this;
    }

    /**
     * Remove disputeDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeDetail $disputeDetails
     */
    public function removeDisputeDetail(\Yilinker\Bundle\CoreBundle\Entity\DisputeDetail $disputeDetails)
    {
        $this->disputeDetails->removeElement($disputeDetails);
    }

    /**
     * Get disputeDetails
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDisputeDetails()
    {
        return $this->disputeDetails;
    }

    /**
     * Set orderProductCancellationReason
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason $orderProductCancellationReason
     * @return Dispute
     */
    public function setOrderProductCancellationReason(\Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason $orderProductCancellationReason = null)
    {
        $this->orderProductCancellationReason = $orderProductCancellationReason;

        return $this;
    }

    /**
     * Get orderProductCancellationReason
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason 
     */
    public function getOrderProductCancellationReason()
    {
        return $this->orderProductCancellationReason;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $disputeMessages;


    /**
     * Add disputeMessages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeMessage $disputeMessages
     * @return Dispute
     */
    public function addDisputeMessage(\Yilinker\Bundle\CoreBundle\Entity\DisputeMessage $disputeMessages)
    {
        $this->disputeMessages[] = $disputeMessages;

        return $this;
    }

    /**
     * Remove disputeMessages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeMessage $disputeMessages
     */
    public function removeDisputeMessage(\Yilinker\Bundle\CoreBundle\Entity\DisputeMessage $disputeMessages)
    {
        $this->disputeMessages->removeElement($disputeMessages);
    }

    /**
     * Get disputeMessages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDisputeMessages()
    {
        return $this->disputeMessages;
    }

    public function groupedDisputeDetails()
    {
        $grouped = array();
        foreach ($this->getDisputeDetails() as $disputeDetail) {
            $orderProductStatus = $disputeDetail->getOrderProductStatus();
            $description = $orderProductStatus ? $orderProductStatus->getDescription(): '';
            $grouped[$description][] = $disputeDetail;
        }
        $this->voucherCodes = array();
        foreach ($grouped as &$group) {
            $voucherGrouped = array();
            foreach ($group as $disputeDetail) {
                $disputeDetailVoucher = $disputeDetail->getDisputeDetailVoucher();
                $voucherCode = $disputeDetailVoucher ? $disputeDetailVoucher->getVoucherCode()->getCode(): '';
                $voucherGrouped[$voucherCode][] = $disputeDetail;
                if($disputeDetailVoucher){
                    $this->voucherCodes[$voucherCode] = $disputeDetailVoucher->getVoucherCode();
                }
            }
            $group = $voucherGrouped;
        }

        return $grouped;
    }
}
