<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead;

/**
 * PayoutRequest
 */
class PayoutRequest
{

    const PAYOUT_METHOD_BANK = 1;

    const PAYOUT_METHOD_CHEQUE = 2;

    const PAYOUT_STATUS_PENDING = 1;

    const PAYOUT_STATUS_PAID = 2;

    const PAYOUT_STATUS_INVALID = 3;

    const PAYOUT_STATUS_IN_PROCESS = 4;

    const BANK_CHARGE = 50;

    /**
     * @var integer
     */
    private $payoutRequestId;

    /**
     * @var string
     */
    private $referenceNumber = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Bank
     */
    private $bank;

    /**
     * @var string
     */
    private $bankAccountTitle;

    /**
     * @var string
     */
    private $bankAccountName;

    /**
     * @var string
     */
    private $bankAccountNumber;

    /**
     * @var string
     */
    private $requestedAmount;

    /**
     * @var string
     */
    private $charge = 0;

    /**
     * @var string
     */
    private $netAmount;

    /**
     * @var string
     */
    private $adjustmentAmount;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $requestBy;

    /**
     * @var integer
     */
    private $payoutRequestMethod;

    /**
     * @var integer
     */
    private $requestSellerType;

    /**
     * @var integer
     */
    private $payoutRequestStatus;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payoutRequests;

    public function __construct()
    {
        $this->payoutRequestStatus = self::PAYOUT_STATUS_PENDING;
    }

    /**
     * Get payoutRequestId
     *
     * @return integer 
     */
    public function getPayoutRequestId()
    {
        return $this->payoutRequestId;
    }

    /**
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return PayoutRequest
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    /**
     * Get referenceNumber
     *
     * @return string 
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * Set bankAccountTitle
     *
     * @param string $bankAccountTitle
     * @return PayoutRequest
     */
    public function setBankAccountTitle($bankAccountTitle)
    {
        $this->bankAccountTitle = $bankAccountTitle;

        return $this;
    }

    /**
     * Set bank
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Bank $bank
     * @return PayoutRequest
     */
    public function setBank(\Yilinker\Bundle\CoreBundle\Entity\Bank $bank = null)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get bank
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Bank 
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * Get bankAccountTitle
     *
     * @return string 
     */
    public function getBankAccountTitle()
    {
        return $this->bankAccountTitle;
    }

    /**
     * Set bankAccountName
     *
     * @param string $bankAccountName
     * @return PayoutRequest
     */
    public function setBankAccountName($bankAccountName)
    {
        $this->bankAccountName = $bankAccountName;

        return $this;
    }

    /**
     * Get bankAccountName
     *
     * @return string 
     */
    public function getBankAccountName()
    {
        return $this->bankAccountName;
    }

    /**
     * Set bankAccountNumber
     *
     * @param string $bankAccountNumber
     * @return PayoutRequest
     */
    public function setBankAccountNumber($bankAccountNumber)
    {
        $this->bankAccountNumber = $bankAccountNumber;

        return $this;
    }

    /**
     * Get bankAccountNumber
     *
     * @return string 
     */
    public function getBankAccountNumber()
    {
        return $this->bankAccountNumber;
    }

    /**
     * Set requestedAmount
     *
     * @param string $requestedAmount
     * @return PayoutRequest
     */
    public function setRequestedAmount($requestedAmount)
    {
        $charge = $requestedAmount < 5000 ? self::BANK_CHARGE: 0;
        $this->setCharge($charge);
        $this->setNetAmount($requestedAmount - $charge);
        $this->setAdjustmentAmount($requestedAmount);
        $this->requestedAmount = $requestedAmount;

        return $this;
    }

    /**
     * Get requestedAmount
     *
     * @return string 
     */
    public function getRequestedAmount()
    {
        return $this->requestedAmount;
    }

    /**
     * Set charge
     *
     * @param string $charge
     * @return PayoutRequest
     */
    public function setCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * Get charge
     *
     * @return string 
     */
    public function getCharge()
    {
        return $this->charge;
    }

    /**
     * Set netAmount
     *
     * @param string $netAmount
     * @return PayoutRequest
     */
    public function setNetAmount($netAmount)
    {
        $this->netAmount = $netAmount;

        return $this;
    }

    /**
     * Get netAmount
     *
     * @return string 
     */
    public function getNetAmount()
    {
        return $this->netAmount;
    }

    /**
     * Set adjustmentAmount
     *
     * @param string $adjustmentAmount
     * @return PayoutRequest
     */
    public function setAdjustmentAmount($adjustmentAmount)
    {
        $this->adjustmentAmount = $adjustmentAmount;

        return $this;
    }

    /**
     * Get adjustmentAmount
     *
     * @return string 
     */
    public function getAdjustmentAmount()
    {
        return $this->adjustmentAmount;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return PayoutRequest
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
     * @return PayoutRequest
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
     * Set requestBy
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $requestBy
     * @return PayoutRequest
     */
    public function setRequestBy(\Yilinker\Bundle\CoreBundle\Entity\User $requestBy = null)
    {
        $this->requestBy = $requestBy;

        return $this;
    }

    /**
     * Get requestBy
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getRequestBy()
    {
        return $this->requestBy;
    }

    /**
     * Set payoutRequestMethod
     *
     * @param integer $payoutRequestMethod
     * @return PayoutRequest
     */
    public function setPayoutRequestMethod($payoutRequestMethod)
    {
        $this->payoutRequestMethod = $payoutRequestMethod;

        return $this;
    }

    /**
     * Get payoutRequestMethod
     *
     * @return integer 
     */
    public function getPayoutRequestMethod($text = false, $forApi = false)
    {
        if ($text) {
            switch ($this->payoutRequestMethod) {
                case self::PAYOUT_METHOD_BANK:
                    return $forApi? 'Bank Deposit' : 'Bank';
                case self::PAYOUT_METHOD_CHEQUE:
                    return $forApi? 'Bank Cheque' : 'Cheque';
            }
        }

        return $this->payoutRequestMethod;
    }

    /**
     * Set requestSellerType
     *
     * @param integer $requestSellerType
     * @return PayoutRequest
     */
    public function setRequestSellerType($requestSellerType)
    {
        $this->requestSellerType = $requestSellerType;

        return $this;
    }

    /**
     * Get requestSellerType
     *
     * @param $text bool
     * @return integer 
     */
    public function getRequestSellerType($text = false)
    {
        if ($text) {
            switch ($this->payoutRequestStatus) {
                case Store::STORE_TYPE_RESELLER:
                    return 'Affiliate';
                case Store::STORE_TYPE_MERCHANT:
                    return 'Merchant';
            }
        }

        return $this->requestSellerType;
    }

    /**
     * Set payoutRequestStatus
     *
     * @param integer $payoutRequestStatus
     * @return PayoutRequest
     */
    public function setPayoutRequestStatus($payoutRequestStatus)
    {
        $this->payoutRequestStatus = $payoutRequestStatus;

        return $this;
    }

    /**
     * Get payoutRequestStatus
     *
     * @return integer 
     */
    public function getPayoutRequestStatus($text = false, $includeInProcess = false)
    {
        if($includeInProcess && $this->hasActivePayoutRequests()){
            return 'In process';
        }

        if ($text) {

            switch ($this->payoutRequestStatus) {
                case self::PAYOUT_STATUS_PENDING:
                    return 'Tentative';
                case self::PAYOUT_STATUS_PAID:
                    return 'Completed';
                case self::PAYOUT_STATUS_INVALID:
                    return 'Invalid';
            }
        }

        return $this->payoutRequestStatus;
    }

    public function toArray ()
    {
        $userDetails = array (
            'userId'   => $this->getRequestBy()->getUserId(),
            'fullName' => $this->getRequestBy()->getFullName()
        );

        $userType = array (
            'id'   => $this->getRequestSellerType(),
            'name' => $this->getRequestSellerType(true)
        );

        $requestMethod = array (
            'id'   => $this->getPayoutRequestMethod(),
            'name' => $this->getPayoutRequestMethod(true)
        );

        $requestStatus = array (
            'id'   => $this->getPayoutRequestStatus(),
            'name' => $this->getPayoutRequestStatus(true)
        );

        return array(
            'payoutRequestId'     => $this->getPayoutRequestId(),
            'requestBy'           => $userDetails,
            'referenceNumber'     => $this->getReferenceNumber(),
            'requestSellerType'   => $userType,
            'payoutRequestMethod' => $requestMethod,
            'bankAccountTitle'    => $this->getBankAccountTitle(),
            'bankAccountName'     => $this->getBankAccountName(),
            'bankAccountNumber'   => $this->getBankAccountNumber(),
            'payoutRequestStatus' => $requestStatus,
            'requestedAmount'     => $this->getRequestedAmount(),
            'bankName'            => $this->bank->getBankName(),
            'charge'              => $this->getCharge(),
            'netAmount'           => $this->getNetAmount(),
            'adjustmentAmount'    => $this->getAdjustmentAmount(),
            'dateAdded'           => $this->getDateAdded()->format('d/m/Y'),
            'dateLastModified'    => $this->getDateLastModified()->format('d/m/Y'),
        );
    }

    /**
     * Add payoutRequests
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutBatchDetail $payoutRequests
     * @return PayoutRequest
     */
    public function addPayoutRequest(\Yilinker\Bundle\CoreBundle\Entity\PayoutBatchDetail $payoutRequests)
    {
        $this->payoutRequests[] = $payoutRequests;

        return $this;
    }

    /**
     * Remove payoutRequests
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutBatchDetail $payoutRequests
     */
    public function removePayoutRequest(\Yilinker\Bundle\CoreBundle\Entity\PayoutBatchDetail $payoutRequests)
    {
        $this->payoutRequests->removeElement($payoutRequests);
    }

    /**
     * Get payoutRequests
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPayoutRequests()
    {
        return $this->payoutRequests;
    }

    public function hasActivePayoutRequests()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isDelete", false))
                            ->setFirstResult(0)
                            ->setMaxResults(1);
        $payoutRequest = $this->getPayoutRequests()->matching($criteria)->first();

        if(!$payoutRequest){
            return false;
        }
        else{
            return $payoutRequest->getPayoutBatchHead()->getPayoutBatchStatus() === PayoutBatchHead::PAYOUT_BATCH_STATUS_IN_PROCESS? true : false; 
        }
    }
}
