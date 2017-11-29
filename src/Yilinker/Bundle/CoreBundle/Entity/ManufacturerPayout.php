<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerPayout
 */
class ManufacturerPayout
{

    /**
     * @var integer
     */
    private $manufacturerPayoutId;

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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payoutDocuments;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Currency
     */
    private $currency;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->payoutDocuments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get manufacturerPayoutId
     *
     * @return integer 
     */
    public function getManufacturerPayoutId()
    {
        return $this->manufacturerPayoutId;
    }

    /**
     * Set payoutType
     *
     * @param integer $payoutType
     * @return ManufacturerPayout
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
     * @return ManufacturerPayout
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
     * @return ManufacturerPayout
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
     * @return ManufacturerPayout
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
     * @return ManufacturerPayout
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
     * @return ManufacturerPayout
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
     * Add payoutDocuments
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutDocument $payoutDocuments
     * @return ManufacturerPayout
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return ManufacturerPayout
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
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return ManufacturerPayout
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
     * @return ManufacturerPayout
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
}
