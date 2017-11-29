<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SmsNewsletterSubscription
 */
class SmsNewsletterSubscription
{

    /**
     * @var integer
     */
    private $smsNewsletterSubscriptionId;

    /**
     * @var string
     */
    private $contactNumber;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var boolean
     */
    private $isActive = true;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * Get smsNewsletterSubscriptionId
     *
     * @return integer 
     */
    public function getSmsNewsletterSubscriptionId()
    {
        return $this->smsNewsletterSubscriptionId;
    }

    /**
     * Set contactNumber
     *
     * @param string $contactNumber
     * @return SmsNewsletterSubscription
     */
    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Get contactNumber
     *
     * @return string 
     */
    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return SmsNewsletterSubscription
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return SmsNewsletterSubscription
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return SmsNewsletterSubscription
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return SmsNewsletterSubscription
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
}
