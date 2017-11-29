<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderProductCancellationReason
 */
class OrderProductCancellationReason
{
    const REASON_TYPE_CANCELLATION = 1;

    const REASON_TYPE_REPLACEMENT = 2;

    const REASON_TYPE_REFUND = 3;

    const USER_TYPE_BUYER = 1;

    const USER_TYPE_SELLER = 2;

    const USER_TYPE_ALL = 3;

    /**
     * @var integer
     */
    private $orderProductCancellationReasonId;

    /**
     * @var string
     */
    private $reason = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var integer
     */
    private $reasonType;

    /**
     * @var integer
     */
    private $userType;

    /**
     * Get orderProductCancellationReasonId
     *
     * @return integer 
     */
    public function getOrderProductCancellationReasonId()
    {
        return $this->orderProductCancellationReasonId;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return OrderProductCancellationReason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return OrderProductCancellationReason
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Convert object to an array
     *
     * @return mixed
     */
    public function toArray()
    {
        return array(
            'id' => $this->getOrderProductCancellationReasonId(),
            'reason' => $this->getReason(),
            'description' => $this->getDescription(),
        );
    }

    /**
     * Set reasonType
     *
     * @param integer $reasonType
     * @return OrderProductCancellationReason
     */
    public function setReasonType($reasonType)
    {
        $this->reasonType = $reasonType;

        return $this;
    }

    /**
     * Get reasonType
     *
     * @return integer 
     */
    public function getReasonType()
    {
        return $this->reasonType;
    }

    /**
     * Set userType
     *
     * @param integer $userType
     * @return OrderProductCancellationReason
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return integer 
     */
    public function getUserType()
    {
        return $this->userType;
    }
}
