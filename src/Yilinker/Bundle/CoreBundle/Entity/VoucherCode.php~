<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class VoucherCode
{
    /**
     * @var integer
     */
    private $voucherCodeId;

    /**
     * @var string
     */
    private $code;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Voucher
     */
    private $voucher;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderVouchers;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $disputeDetailVouchers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderVouchers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->disputeDetailVouchers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get voucherCodeId
     *
     * @return integer 
     */
    public function getVoucherCodeId()
    {
        return $this->voucherCodeId;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return VoucherCode
     */
    public function setCode($code)
    {
        $this->code = trim($code);

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set voucher
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Voucher $voucher
     * @return VoucherCode
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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addConstraint(new UniqueEntity(array(
            'fields'    => 'code',
            'message'   => 'Voucher Code is already taken',
        )));
    }

    /**
     * Add orderVouchers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers
     * @return VoucherCode
     */
    public function addOrderVoucher(\Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers)
    {
        $this->orderVouchers[] = $orderVouchers;

        return $this;
    }

    /**
     * Remove orderVouchers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers
     */
    public function removeOrderVoucher(\Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers)
    {
        $this->orderVouchers->removeElement($orderVouchers);
    }

    /**
     * Get orderVouchers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderVouchers()
    {
        return $this->orderVouchers;
    }

    /**
     * Add disputeDetailVouchers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher $disputeDetailVouchers
     * @return VoucherCode
     */
    public function addDisputeDetailVoucher(\Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher $disputeDetailVouchers)
    {
        $this->disputeDetailVouchers[] = $disputeDetailVouchers;

        return $this;
    }

    /**
     * Remove disputeDetailVouchers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher $disputeDetailVouchers
     */
    public function removeDisputeDetailVoucher(\Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher $disputeDetailVouchers)
    {
        $this->disputeDetailVouchers->removeElement($disputeDetailVouchers);
    }

    /**
     * Get disputeDetailVouchers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDisputeDetailVouchers()
    {
        return $this->disputeDetailVouchers;
    }
}
