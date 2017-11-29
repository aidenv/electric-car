<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * PayoutOrderProduct
 */
class PayoutOrderProduct
{

    /**
     * @var integer
     */
    private $payoutOrderProductId;

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
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Payout
     */
    private $payout;

    /**
     * Get payoutOrderProductId
     *
     * @return integer 
     */
    public function getPayoutOrderProductId()
    {
        return $this->payoutOrderProductId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return PayoutOrderProduct
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
     * @return PayoutOrderProduct
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
     * @return PayoutOrderProduct
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
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProductId
     * @return PayoutOrderProduct
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
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProductId;


    /**
     * Set orderProductId
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProductId
     * @return PayoutOrderProduct
     */
    public function setOrderProductId(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProductId = null)
    {
        $this->orderProductId = $orderProductId;

        return $this;
    }

    /**
     * Get orderProductId
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProduct 
     */
    public function getOrderProductId()
    {
        return $this->orderProductId;
    }

    /**
     * Set payout
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Payout $payout
     * @return PayoutOrderProduct
     */
    public function setPayout(\Yilinker\Bundle\CoreBundle\Entity\Payout $payout = null)
    {
        $this->payout = $payout;

        return $this;
    }

    /**
     * Get payout
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Payout
     */
    public function getPayout()
    {
        return $this->payout;
    }
}
