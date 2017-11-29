<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class OrderHistory {
    
    /**
     * @var integer
     */
    private $orderHistoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderStatus
     */
    private $orderStatus;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    private $order;


    /**
     * Get orderHistoryId
     *
     * @return integer 
     */
    public function getOrderHistoryId()
    {
        return $this->orderHistoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderHistory
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
     * Set orderStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderStatus $orderStatus
     * @return OrderHistory
     */
    public function setOrderStatus(\Yilinker\Bundle\CoreBundle\Entity\OrderStatus $orderStatus = null)
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderStatus 
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    /**
     * Set order
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrder $order
     * @return OrderHistory
     */
    public function setOrder(\Yilinker\Bundle\CoreBundle\Entity\UserOrder $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserOrder 
     */
    public function getOrder()
    {
        return $this->order;
    }
}
