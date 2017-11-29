<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class UserOrderFlagged
{
    const APPROVE = 1;

    const REJECT = 2;
    
    /**
     * @var int
     */
    private $userOrderFlaggedId;

    /**
     * @var integer
     */
    private $flagReason;

    /**
     * @var string
     */
    private $remarks = '';

    /**
     * @var integer
     */
    private $status = 0;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $user;

    /**
     * @var \DateTime
     */
    private $dateRemarked;

    public function __construct()
    {
        $this->dateRemarked = new \DateTime();
    }

    /**
     * Get userOrderFlaggedId
     *
     * @return integer 
     */
    public function getUserOrderFlaggedId()
    {
        return $this->userOrderFlaggedId;
    }

    /**
     * Set flagReason
     *
     * @param integer $flagReason
     * @return UserOrderFlagged
     */
    public function setFlagReason($flagReason)
    {
        $this->flagReason = $flagReason;

        return $this;
    }

    /**
     * Get flagReason
     *
     * @return integer 
     */
    public function getFlagReason()
    {
        return $this->flagReason;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     * @return UserOrderFlagged
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;
        $now = new \DateTime();
        $this->setDateRemarked($now);

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
     * Set dateRemarked
     *
     * @param \DateTime $dateRemarked
     * @return UserOrderFlagged
     */
    public function setDateRemarked($dateRemarked)
    {
        $this->dateRemarked = $dateRemarked;

        return $this;
    }

    /**
     * Get dateRemarked
     *
     * @return \DateTime 
     */
    public function getDateRemarked()
    {
        return $this->dateRemarked;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return UserOrderFlagged
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $user
     * @return UserOrderFlagged
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getStatusTxt()
    {
        $statusTxt = '';
        $status = $this->getStatus();
        if ($status == self::APPROVE) {
            $statusTxt = 'Approved';
        }
        elseif ($status == self::REJECT) {
            $statusTxt = 'Rejected';
        }

        return $statusTxt;
    }
}
