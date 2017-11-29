<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserReferral
 */
class UserReferral
{

    /**
     * @var integer
     */
    private $userReferralId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $referrer;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * Get userReferralId
     *
     * @return integer
     */
    public function getUserReferralId()
    {
        return $this->userReferralId;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return User
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

    /**
     * Set referrer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $referrer
     * @return UserReferral
     */
    public function setReferrer(\Yilinker\Bundle\CoreBundle\Entity\User $referrer = null)
    {
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * Get referrer
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getReferrer()
    {
        return $this->referrer;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return UserReferral
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }
}
