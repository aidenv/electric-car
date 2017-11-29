<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductShipmentHistory
 */
class OrderProductShipmentHistory
{

    /**
     * @var integer
     */
    private $orderProductShipmentHistoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipment
     */
    private $orderProductShipment;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipmentStatus
     */
    private $orderProductShipmentStatus;


    /**
     * Get orderProductShipmentHistoryId
     *
     * @return integer 
     */
    public function getOrderProductShipmentHistoryId()
    {
        return $this->orderProductShipmentHistoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderProductShipmentHistory
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
     * Set orderProductShipment
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipment $orderProductShipment
     * @return OrderProductShipmentHistory
     */
    public function setOrderProductShipment(\Yilinker\Bundle\CoreBundle\Entity\OrderProductShipment $orderProductShipment = null)
    {
        $this->orderProductShipment = $orderProductShipment;

        return $this;
    }

    /**
     * Get orderProductShipment
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipment 
     */
    public function getOrderProductShipment()
    {
        return $this->orderProductShipment;
    }

    /**
     * Set orderProductShipmentStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductShipmentStatus $orderProductShipmentStatus
     * @return OrderProductShipmentHistory
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
}
