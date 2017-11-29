<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class EarningTransaction
{
    /**
     * @var integer
     */
    private $earningTransactionId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Earning
     */
    private $earning;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;


    /**
     * Get earningTransactionId
     *
     * @return integer 
     */
    public function getEarningTransactionId()
    {
        return $this->earningTransactionId;
    }

    /**
     * Set earning
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earning
     * @return EarningTransaction
     */
    public function setEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earning = null)
    {
        $this->earning = $earning;

        return $this;
    }

    /**
     * Get earning
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Earning 
     */
    public function getEarning()
    {
        return $this->earning;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return EarningTransaction
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
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    private $order;


    /**
     * Set order
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrder $order
     * @return EarningTransaction
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
