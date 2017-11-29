<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class EarningFollow
{
    /**
     * @var integer
     */
    private $earningFollowId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Earning
     */
    private $earning;


    /**
     * Get earningFollowId
     *
     * @return integer 
     */
    public function getEarningFollowId()
    {
        return $this->earningFollowId;
    }

    /**
     * Set earning
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earning
     * @return EarningFollow
     */
    public function setEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earning = null)
    {
        $this->earning = $earning;

        return $this;
    }

    /**
     * Get earning
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Earning 
     */
    public function getEarning()
    {
        return $this->earning;
    }

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory
     */
    private $userFollowHistory;


    /**
     * Set userFollowHistory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory $userFollowHistory
     * @return EarningFollow
     */
    public function setUserFollowHistory(\Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory $userFollowHistory = null)
    {
        $this->userFollowHistory = $userFollowHistory;

        return $this;
    }

    /**
     * Get userFollowHistory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory 
     */
    public function getUserFollowHistory()
    {
        return $this->userFollowHistory;
    }
}
