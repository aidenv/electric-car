<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class UserPoint
{
    const BUYER_REGISTRATION = 1;

    CONST PURCHASE = 2;

    const REFERRAL_PURCHASE = 3;

    const DAILY_LOGIN = 4;

    const REFERRAL = 5;

    /**
     * Points
     */
    const POINTS_DAILY_LOGIN = 1;

    const BONUS_POINTS_DAILY_LOGIN = 10;

    const DAILY_LOGIN_CONSECUTIVE_LOGIN = 30;

    const REFERRAL_BUYER_TO_BUYER = 3;

    const BUYER_REGISTRATION_POINT = 3;
    /**
     * @var integer
     */
    private $userPointId;

    /**
     * @var integer
     */
    protected $points = 0;

    /**
     * @var integer
     */
    private $type;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Get userPointId
     *
     * @return integer 
     */
    public function getUserPointId()
    {
        return $this->userPointId;
    }

    /**
     * Set points
     *
     * @param integer $points
     * @return UserPoint
     */
    public function setPoints($points)
    {
        $this->points = $points;

        return $this;
    }

    /**
     * Get points
     *
     * @return integer 
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return UserPoint
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserPoint
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getDescription()
    {
        if ($this->points < 0) {
            return "You consumed ".number_format(abs($this->points), 2);
        }

        return "You earned ".number_format(abs($this->points), 2);
    }
    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserPoint
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
}
