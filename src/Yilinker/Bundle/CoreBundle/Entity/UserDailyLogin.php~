<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDailyLogin
 */
class UserDailyLogin
{
    /**
     * @var integer
     */
    private $id;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @var integer
     */
    private $userDailyLoginId;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Get userDailyLoginId
     *
     * @return integer 
     */
    public function getUserDailyLoginId()
    {
        return $this->userDailyLoginId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return UserDailyLogin
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

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserDailyLogin
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
