<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class OrderVoucher
{
    
    /**
     * @var integer
     */
    private $orderVoucherId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    private $order;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\VoucherCode
     */
    private $voucherCode;
    
    /**
     * @var string
     */
    private $value;


    /**
     * Get orderVoucherId
     *
     * @return integer 
     */
    public function getOrderVoucherId()
    {
        return $this->orderVoucherId;
    }

    /**
     * Set order
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrder $order
     * @return OrderVoucher
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

    /**
     * Set value
     *
     * @param string $value
     * @return OrderVoucher
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set voucherCode
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherCode $voucherCode
     * @return OrderVoucher
     */
    public function setVoucherCode(\Yilinker\Bundle\CoreBundle\Entity\VoucherCode $voucherCode = null)
    {
        $this->voucherCode = $voucherCode;

        return $this;
    }

    /**
     * Get voucherCode
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\VoucherCode 
     */
    public function getVoucherCode()
    {
        return $this->voucherCode;
    }

    /**
     * Convert the voucher to an array
     *
     * @return mixed
     */
    public function toArray()
    {
        $voucherCode = $this->voucherCode;
        return array(
            'amount'  => $this->value,
            'voucher' => $voucherCode->getVoucher()->toArray(),
            'code'    => $voucherCode->getCode(),
        );
    }
}
