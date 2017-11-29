<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class OrderStatus 
{
    const PAYMENT_WAITING = 1;

    const PAYMENT_CONFIRMED = 2;

    const ORDER_STATUS_COMPLETED = 3;
    
    const ORDER_REJECTED_FOR_FRAUD = 4;

    const PAYMENT_FAILED = 5;

    const ORDER_DELIVERED = 6;

    const ORDER_FOR_PICKUP = 7;

    const ORDER_FOR_CANCELLATION = 8;
    
    const ORDER_FOR_REFUND = 9;

    const ORDER_FOR_REPLACEMENT = 10;

    const COD_WAITING_FOR_PAYMENT = 11;

    /**
     * @var integer
     */
    private $orderStatusId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderHistories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderHistories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get orderStatusId
     *
     * @return integer 
     */
    public function getOrderStatusId()
    {
        return $this->orderStatusId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return OrderStatus
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return OrderStatus
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
     * Add orderHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories
     * @return OrderStatus
     */
    public function addOrderHistory(\Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories)
    {
        $this->orderHistories[] = $orderHistories;

        return $this;
    }

    /**
     * Remove orderHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories
     */
    public function removeOrderHistory(\Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories)
    {
        $this->orderHistories->removeElement($orderHistories);
    }

    /**
     * Get orderHistories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderHistories()
    {
        return $this->orderHistories;
    }

    public function toArray()
    {
        $data = array(
            'orderStatusId' => $this->getOrderStatusId(),
            'name'          => $this->getName(),
            'description'   => $this->getDescription()
        );

        return $data;
    }
}
