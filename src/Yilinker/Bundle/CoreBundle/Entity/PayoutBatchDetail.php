<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PayoutBatchDetail
 */
class PayoutBatchDetail
{

    /**
     * @var integer
     */
    private $payoutBatchDetailId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead
     */
    private $payoutBatchHead;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PayoutRequest
     */
    private $payoutRequest;

    /**
     * @var integer
     */
    private $isDelete;

    /**
     * Get payoutBatchDetailId
     *
     * @return integer 
     */
    public function getPayoutBatchDetailId()
    {
        return $this->payoutBatchDetailId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return PayoutBatchDetail
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
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return PayoutBatchDetail
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set payoutBatchHead
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead $payoutBatchHead
     * @return PayoutBatchDetail
     */
    public function setPayoutBatchHead(\Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead $payoutBatchHead = null)
    {
        $this->payoutBatchHead = $payoutBatchHead;

        return $this;
    }

    /**
     * Get payoutBatchHead
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PayoutBatchHead 
     */
    public function getPayoutBatchHead()
    {
        return $this->payoutBatchHead;
    }

    /**
     * Set payoutRequest
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PayoutRequest $payoutRequest
     * @return PayoutBatchDetail
     */
    public function setPayoutRequest(\Yilinker\Bundle\CoreBundle\Entity\PayoutRequest $payoutRequest = null)
    {
        $this->payoutRequest = $payoutRequest;

        return $this;
    }

    /**
     * Get payoutRequest
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PayoutRequest 
     */
    public function getPayoutRequest()
    {
        return $this->payoutRequest;
    }

    /**
     * Entity to array format
     *
     * @return array
     */
    public function toArray ()
    {
        $details = array (
            'payoutBatchDetailId' => $this->getPayoutBatchDetailId(),
            'payoutBatchHead'     => $this->payoutBatchHead->toArray(),
            'payoutRequest'       => $this->payoutRequest->toArray(),
            'dateAdded'           => $this->getDateAdded()->format('m/d/Y'),
            'dateLastModified'    => $this->getDateLastModified()->format('m/d/Y')
        );

        return $details;
    }

    /**
     * Set isDelete
     *
     * @param integer $isDelete
     * @return PayoutBatchDetail
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete
     *
     * @return integer 
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }
}
