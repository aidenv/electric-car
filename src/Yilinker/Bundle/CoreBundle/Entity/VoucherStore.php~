<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VoucherStore
 */
class VoucherStore
{
    /**
     * @var integer
     */
    private $voucherStoreId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Voucher
     */
    private $voucher;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Store
     */
    private $store;


    /**
     * Get voucherStoreId
     *
     * @return integer 
     */
    public function getVoucherStoreId()
    {
        return $this->voucherStoreId;
    }

    /**
     * Set voucher
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Voucher $voucher
     * @return VoucherStore
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
     * Set store
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Store $store
     * @return VoucherStore
     */
    public function setStore(\Yilinker\Bundle\CoreBundle\Entity\Store $store = null)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Store 
     */
    public function getStore()
    {
        return $this->store;
    }
}
