<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductShipment
 */
class OrderProductShipment
{

    /**
     * @var integer
     */
    private $orderProductShipmentId;

    /**
     * @var integer
     */
    private $wayBillNumber;

    /**
     * @var string
     */
    private $trackingNumber;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipmentStatus
     */
    private $orderProductShipmentStatus;

    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Get orderProductShipmentId
     *
     * @return integer 
     */
    public function getOrderProductShipmentId()
    {
        return $this->orderProductShipmentId;
    }

    /**
     * Set wayBillNumber
     *
     * @param integer $wayBillNumber
     * @return OrderProductShipment
     */
    public function setWayBillNumber($wayBillNumber)
    {
        $this->wayBillNumber = $wayBillNumber;

        return $this;
    }

    /**
     * Get wayBillNumber
     *
     * @return integer 
     */
    public function getWayBillNumber()
    {
        return $this->wayBillNumber;
    }

    /**
     * Set trackingNumber
     *
     * @param string $trackingNumber
     * @return OrderProductShipment
     */
    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    /**
     * Get trackingNumber
     *
     * @return string 
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return OrderProductShipment
     */
    public function setOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct = null)
    {
        $this->orderProduct = $orderProduct;

        return $this;
    }

    /**
     * Get orderProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProduct 
     */
    public function getOrderProduct()
    {
        return $this->orderProduct;
    }

    /**
     * Set orderProductShipmentStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipmentStatus $orderProductShipmentStatus
     * @return OrderProductShipment
     */
    public function setOrderProductShipmentStatus(\Yilinker\Bundle\CoreBundle\Entity\OrderProductShipmentStatus $orderProductShipmentStatus = null)
    {
        $this->orderProductShipmentStatus = $orderProductShipmentStatus;

        return $this;
    }

    /**
     * Get orderProductShipmentStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipmentStatus 
     */
    public function getOrderProductShipmentStatus()
    {
        return $this->orderProductShipmentStatus;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderProductShipment
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
}
