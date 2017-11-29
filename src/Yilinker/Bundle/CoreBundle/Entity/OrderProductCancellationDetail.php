<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductCancellationDetail
 */
class OrderProductCancellationDetail
{

    const DETAIL_STATUS_OPEN = 1;

    const DETAIL_STATUS_APPROVED = 2;

    const DETAIL_STATUS_DENIED = 3;

    /**
     * @var integer
     */
    private $orderProductCancellationDetailId;

    /**
     * @var string
     */
    private $remarks;

    /**
     * @var integer
     */
    private $status = self::DETAIL_STATUS_OPEN;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationHead
     */
    private $orderProductCancellationHead;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;


    /**
     * Get orderProductCancellationDetailId
     *
     * @return integer 
     */
    public function getOrderProductCancellationDetailId()
    {
        return $this->orderProductCancellationDetailId;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     * @return OrderProductCancellationDetail
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string 
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return OrderProductCancellationDetail
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
     * @return OrderProductCancellationDetail
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
     * Set orderProductCancellationHead
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationHead $orderProductCancellationHead
     * @return OrderProductCancellationDetail
     */
    public function setOrderProductCancellationHead(\Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationHead $orderProductCancellationHead = null)
    {
        $this->orderProductCancellationHead = $orderProductCancellationHead;

        return $this;
    }

    /**
     * Get orderProductCancellationHead
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationHead 
     */
    public function getOrderProductCancellationHead()
    {
        return $this->orderProductCancellationHead;
    }

    /**
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return OrderProductCancellationDetail
     */
    public function setAdminUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser = null)
    {
        $this->adminUser = $adminUser;

        return $this;
    }

    /**
     * Get adminUser
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getAdminUser()
    {
        return $this->adminUser;
    }
}
