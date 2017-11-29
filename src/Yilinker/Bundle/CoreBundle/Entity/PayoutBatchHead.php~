<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PayoutBatchHead
 */
class PayoutBatchHead
{
    const PAYOUT_BATCH_STATUS_IN_PROCESS = 1;

    const PAYOUT_BATCH_STATUS_DEPOSITED = 2;

    /**
     * @var integer
     */
    private $payoutBatchHeadId;

    /**
     * @var string
     */
    private $batchNumber;

    /**
     * @var integer
     */
    private $payoutBatchStatus;

    /**
     * @var string
     */
    private $remarks;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var integer
     */
    private $isDelete;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;


    /**
     * Get payoutBatchHeadId
     *
     * @return integer 
     */
    public function getPayoutBatchHeadId()
    {
        return $this->payoutBatchHeadId;
    }

    /**
     * Set batchNumber
     *
     * @param string $batchNumber
     * @return PayoutBatchHead
     */
    public function setBatchNumber($batchNumber)
    {
        $this->batchNumber = $batchNumber;

        return $this;
    }

    /**
     * Get batchNumber
     *
     * @return string 
     */
    public function getBatchNumber()
    {
        return $this->batchNumber;
    }

    /**
     * Set payoutBatchStatus
     *
     * @param integer $payoutBatchStatus
     * @return PayoutBatchHead
     */
    public function setPayoutBatchStatus($payoutBatchStatus)
    {
        $this->payoutBatchStatus = $payoutBatchStatus;

        return $this;
    }

    /**
     * Get payoutBatchStatus
     *
     * @param $text
     * @return integer 
     */
    public function getPayoutBatchStatus($text = false)
    {
        if ($text) {
            switch ($this->payoutBatchStatus) {
                case self::PAYOUT_BATCH_STATUS_IN_PROCESS:
                    return 'In Process';
                case self::PAYOUT_BATCH_STATUS_DEPOSITED:
                    return 'Deposited';
            }
        }
        return $this->payoutBatchStatus;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     * @return PayoutBatchHead
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string 
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return PayoutBatchHead
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
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return PayoutBatchHead
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return PayoutBatchHead
     */
    public function setAdminUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser = null)
    {
        $this->adminUser = $adminUser;

        return $this;
    }

    /**
     * Get adminUser
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getAdminUser()
    {
        return $this->adminUser;
    }

    public function toArray ()
    {
        $adminUserDetails = array (
            'adminUserId' => $this->adminUser->getAdminUserId(),
            'fullName'    => $this->adminUser->getFullName()
        );

        $payoutBatchStatus = array (
            'id'   => $this->getPayoutBatchStatus(),
            'name' => $this->getPayoutBatchStatus(true)
        );

        $details = array (
            'payoutBatchHeadId'   => $this->getPayoutBatchHeadId(),
            'adminUser'           => $adminUserDetails,
            'batchNumber'         => $this->getBatchNumber(),
            'payoutBatchStatus'   => $payoutBatchStatus,
            'remarks'             => $this->getRemarks(),
            'dateAdded'           => $this->getDateAdded()->format('m/d/Y'),
            'dateLastModified'    => $this->getDateLastModified()->format('m/d/Y'),
        );

        return $details;
    }

    /**
     * Set isDelete
     *
     * @param integer $isDelete
     * @return PayoutBatchHead
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete
     *
     * @return integer 
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }
}
