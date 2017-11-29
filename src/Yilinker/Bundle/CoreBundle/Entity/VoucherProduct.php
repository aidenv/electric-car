<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VoucherProduct
 */
class VoucherProduct
{
    /**
     * @var integer
     */
    private $voucherProductId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Voucher
     */
    private $voucher;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;


    /**
     * Get voucherProductId
     *
     * @return integer 
     */
    public function getVoucherProductId()
    {
        return $this->voucherProductId;
    }

    /**
     * Set voucher
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Voucher $voucher
     * @return VoucherProduct
     */
    public function setVoucher(\Yilinker\Bundle\CoreBundle\Entity\Voucher $voucher = null)
    {
        $this->voucher = $voucher;

        return $this;
    }

    /**
     * Get voucher
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Voucher 
     */
    public function getVoucher()
    {
        return $this->voucher;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return VoucherProduct
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }
}
