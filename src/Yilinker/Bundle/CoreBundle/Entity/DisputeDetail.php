<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DisputeDetail
 */
class DisputeDetail
{

    const DETAIL_STATUS_OPEN = 1;

    const DETAIL_STATUS_CLOSE = 2;

    /**
     * @var integer
     */
    private $disputeDetailId;

    /**
     * @var integer
     */
    private $status = 1;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $disputee;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Dispute
     */
    private $dispute;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus
     */
    private $orderProductStatus;
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher
     */
    private $disputeDetailVoucher;

    /**
     * Get disputeDetailId
     *
     * @return integer 
     */
    public function getDisputeDetailId()
    {
        return $this->disputeDetailId;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return DisputeDetail
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return DisputeDetail
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
     * Set disputee
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $disputee
     * @return DisputeDetail
     */
    public function setDisputee(\Yilinker\Bundle\CoreBundle\Entity\User $disputee = null)
    {
        $this->disputee = $disputee;

        return $this;
    }

    /**
     * Get disputee
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getDisputee()
    {
        return $this->disputee;
    }

    /**
     * Set dispute
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute
     * @return DisputeDetail
     */
    public function setDispute(\Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute = null)
    {
        $this->dispute = $dispute;

        return $this;
    }

    /**
     * Get dispute
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Dispute 
     */
    public function getDispute()
    {
        return $this->dispute;
    }

    /**
     * Set orderProductStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus $orderProductStatus
     * @return DisputeDetail
     */
    public function setOrderProductStatus(\Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus $orderProductStatus = null)
    {
        $this->orderProductStatus = $orderProductStatus;
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
     * Set disputeDetailVoucher
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher $disputeDetailVoucher
     * @return DisputeDetail
     */
    public function setDisputeDetailVoucher(\Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher $disputeDetailVoucher = null)
    {
        $this->disputeDetailVoucher = $disputeDetailVoucher;

        return $this;
    }

    /*
     * Get disputeDetailVoucher
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher 
     */
    public function getDisputeDetailVoucher()
    {
        return $this->disputeDetailVoucher;
    }
}
