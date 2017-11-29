<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VoucherProductCategory
 */
class VoucherProductCategory
{
    /**
     * @var integer
     */
    private $voucherProductCategoryId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Voucher
     */
    private $voucher;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;


    /**
     * Get voucherProductCategoryId
     *
     * @return integer 
     */
    public function getVoucherProductCategoryId()
    {
        return $this->voucherProductCategoryId;
    }

    /**
     * Set voucher
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Voucher $voucher
     * @return VoucherProductCategory
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
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return VoucherProductCategory
     */
    public function setProductCategory(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCategory 
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }
}
