<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductCancellationHead
 */
class OrderProductCancellationHead
{

    /**
     * @var integer
     */
    private $orderProductCancellationHeadId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var integer
     */
    private $isOpened;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason
     */
    private $orderProductCancellationReason;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $admin;
    
    /**
     * @var string
     */
    private $remarks;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderProductCancellationDetails;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderProductCancellationDetails = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get orderProductCancellationHeadId
     *
     * @return integer 
     */
    public function getOrderProductCancellationHeadId()
    {
        return $this->orderProductCancellationHeadId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderProductCancellationHead
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
     * Set isOpened
     *
     * @param integer $isOpened
     * @return OrderProductCancellationHead
     */
    public function setIsOpened($isOpened)
    {
        $this->isOpened = $isOpened;

        return $this;
    }

    /**
     * Get isOpened
     *
     * @return integer 
     */
    public function getIsOpened()
    {
        return $this->isOpened;
    }

    /**
     * Set orderProductCancellationReason
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason $orderProductCancellationReason
     * @return OrderProductCancellationHead
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return OrderProductCancellationHead
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
     * Set remarks
     *
     * @param string $remarks
     * @return OrderProductCancellationHead
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
     * Set admin
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $admin
     * @return OrderProductCancellationHead
     */
    public function setAdmin(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $admin = null)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Add orderProductCancellationDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails
     * @return OrderProductCancellationHead
     */
    public function addOrderProductCancellationDetail(\Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails)
    {
        $this->orderProductCancellationDetails[] = $orderProductCancellationDetails;

        return $this;
    }

    /**
     * Remove orderProductCancellationDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails
     */
    public function removeOrderProductCancellationDetail(\Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails)
    {
        $this->orderProductCancellationDetails->removeElement($orderProductCancellationDetails);
    }

    /**
     * Get orderProductCancellationDetails
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderProductCancellationDetails()
    {
        return $this->orderProductCancellationDetails;
    }
}
