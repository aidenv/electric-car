<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductHistory
 */
class OrderProductHistory
{

    /**
     * @var integer
     */
    private $orderProductHistoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus
     */
    private $orderProductStatus;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;


    /**
     * Get orderHistoryId
     *
     * @return integer 
     */
    public function getOrderProductHistoryId()
    {
        return $this->orderProductHistoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderProductHistory
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
     * Set orderProductStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus $orderProductStatus
     * @return OrderProductHistory
     */
    public function setOrderProductStatus(\Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus $orderProductStatus = null)
    {
        $this->orderProductStatus = $orderProductStatus;

        return $this;
    }

    /**
     * Get orderProductStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus 
     */
    public function getOrderProductStatus()
    {
        return $this->orderProductStatus;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return OrderProductHistory
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
}
