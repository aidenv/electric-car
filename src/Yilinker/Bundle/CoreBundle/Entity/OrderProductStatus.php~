<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductStatus
 */
class OrderProductStatus
{
    const PAYMENT_CONFIRMED = 1;

    const STATUS_READY_FOR_PICKUP = 2;

    const STATUS_PRODUCT_ON_DELIVERY = 3;

    const STATUS_ITEM_RECEIVED_BY_BUYER = 4;

    const STATUS_SELLER_PAYMENT_RELEASED = 5;

    const STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY = 6;

    const STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY = 7;

    const STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED = 8;

    const STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED = 9;

    const STATUS_ITEM_REFUND_REQUESTED = 10;

    const STATUS_ITEM_REFUND_BOOKED_FOR_PICKUP = 11;

    const STATUS_REFUNDED_ITEM_RECEIVED = 12;

    const STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED = 13;

    const STATUS_REFUND_REASON_DENIED_ON_THE_SPOT = 14;

    const STATUS_REFUND_REASON_DENIED_ON_INSPECTION = 15;

    const STATUS_ITEM_REPLACEMENT_REQUESTED = 16;

    const STATUS_ITEM_RETURN_BOOKED_FOR_PICKUP = 17;

    const STATUS_RETURNED_ITEM_RECEIVED = 18;

    const STATUS_REPLACEMENT_PRODUCT_INSPECTION_APPROVED = 19;

    const STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT = 20;

    const STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_INSPECTION = 21;

    const STATUS_SELLER_PAYOUT_UN_HELD = 22;

    const STATUS_BUYER_REFUND_RELEASED = 23;

    const STATUS_COD_TRANSACTION_CONFIRMED = 24;

    const STATUS_CANCELED_BY_ADMIN = 25;

    const STATUS_DISPUTE_IN_PROCESS = 26;

    const DEFAULT_CLASS = 'transparent';

    const CODE_AVAILABLE_FOR_PAYOUT = 100;

    /**
     * @var integer
     */
    private $orderProductStatusId;

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
    private $orderProducts;

    /**
     * @var string
     */
    private $class = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderProducts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get orderProductStatusId
     *
     * @return integer 
     */
    public function getOrderProductStatusId()
    {
        return $this->orderProductStatusId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return OrderProductStatus
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
     * Add orderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts
     * @return OrderProductStatus
     */
    public function addOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts)
    {
        $this->orderProducts[] = $orderProducts;

        return $this;
    }

    /**
     * Remove orderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts
     */
    public function removeOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts)
    {
        $this->orderProducts->removeElement($orderProducts);
    }

    /**
     * Get orderProducts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderProducts()
    {
        return $this->orderProducts;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return OrderProductStatus
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

    public function toArray()
    {
        $data = array(
            'orderProductStatusId'  => $this->getOrderProductStatusId(),
            'name'                  => $this->getName(),
            'description'           => $this->getDescription()
        );

        return $data;
    }

    /**
     * Set class
     *
     * @param string $class
     * @return OrderProductStatus
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string 
     */
    public function getClass()
    {
        return strlen(trim($this->class)) === 0 ? self::DEFAULT_CLASS : $this->class;
    }

    public function __toString()
    {
        return $this->getDescription();
    }
}
