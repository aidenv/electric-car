<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Payout
 */
class Payout
{
    /**
     * @var integer
     */
    const PAYOUT_TYPE_SELLER_PAYOUT = 0;

    /**
     * @var integer
     */
    const PAYOUT_TYPE_BUYER_REFUND = 1;

    /**
     * @var integer
     */
    const PAYOUT_STATUS_INCOMPLETE = 0;

    /**
     * @var integer
     */
    const PAYOUT_STATUS_COMPLETE = 1;

    /**
     * @var integer
     */
    private $payoutId;

    /**
     * @var integer
     */
    private $payoutType = '0';

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateModified;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var integer
     */
    private $status = '0';

    /**
     * @var string
     */
    private $referenceNumber;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Currency
     */
    private $currency;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payoutOrderProducts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payoutDocuments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $refundNotes;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Dispute
     */
    private $dispute;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->payoutOrderProducts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->payoutDocuments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get payoutId
     *
     * @return integer 
     */
    public function getPayoutId()
    {
        return $this->payoutId;
    }

    /**
     * Set payoutType
     *
     * @param integer $payoutType
     * @return Payout
     */
    public function setPayoutType($payoutType)
    {
        $this->payoutType = $payoutType;

        return $this;
    }

    /**
     * Get payoutType
     *
     * @return integer 
     */
    public function getPayoutType()
    {
        return $this->payoutType;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return Payout
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return Payout
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime 
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return Payout
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Payout
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return Payout
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
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return Payout
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

    /**
     * Set currency
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Currency $currency
     * @return Payout
     */
    public function setCurrency(\Yilinker\Bundle\CoreBundle\Entity\Currency $currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Currency 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return Payout
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add payoutOrderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\payoutOrderProduct $payoutOrderProducts
     * @return Payout
     */
    public function addPayoutOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\payoutOrderProduct $payoutOrderProducts)
    {
        $this->payoutOrderProducts[] = $payoutOrderProducts;

        return $this;
    }

    /**
     * Remove payoutOrderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\payoutOrderProduct $payoutOrderProducts
     */
    public function removePayoutOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\payoutOrderProduct $payoutOrderProducts)
    {
        $this->payoutOrderProducts->removeElement($payoutOrderProducts);
    }

    /**
     * Get payoutOrderProducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPayoutOrderProducts()
    {
        return $this->payoutOrderProducts;
    }


    /**
     * Add payoutDocuments
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutDocument $payoutDocuments
     * @return Payout
     */
    public function addPayoutDocument(\Yilinker\Bundle\CoreBundle\Entity\PayoutDocument $payoutDocuments)
    {
        $this->payoutDocuments[] = $payoutDocuments;

        return $this;
    }

    /**
     * Remove payoutDocuments
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutDocument $payoutDocuments
     */
    public function removePayoutDocument(\Yilinker\Bundle\CoreBundle\Entity\PayoutDocument $payoutDocuments)
    {
        $this->payoutDocuments->removeElement($payoutDocuments);
    }

    /**
     * Get payoutDocuments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPayoutDocuments()
    {
        return $this->payoutDocuments;
    }

    /**
     * Add refundNotes
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\RefundNote $refundNotes
     * @return Payout
     */
    public function addRefundNote(\Yilinker\Bundle\CoreBundle\Entity\RefundNote $refundNotes)
    {
        $this->refundNotes[] = $refundNotes;

        return $this;
    }

    /**
     * Remove refundNotes
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\RefundNote $refundNotes
     */
    public function removeRefundNote(\Yilinker\Bundle\CoreBundle\Entity\RefundNote $refundNotes)
    {
        $this->refundNotes->removeElement($refundNotes);
    }

    /**
     * Get refundNotes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRefundNotes()
    {
        return $this->refundNotes;
    }

    /**
     * Set dispute
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute
     * @return Payout
     */
    public function setDispute(\Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute = null)
    {
        $this->dispute = $dispute;

        return $this;
    }

    /**
     * Get dispute
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Dispute 
     */
    public function getDispute()
    {
        return $this->dispute;
    }
}
