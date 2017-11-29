<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EarningUserRegistration
 */
class EarningUserRegistration
{
    /**
     * @var integer
     */
    private $earningUserRegistrationId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Earning
     */
    private $earning;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Get earningUserRegistrationId
     *
     * @return integer 
     */
    public function getEarningUserRegistrationId()
    {
        return $this->earningUserRegistrationId;
    }

    /**
     * Set earning
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earning
     * @return EarningUserRegistration
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return EarningUserRegistration
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
}
