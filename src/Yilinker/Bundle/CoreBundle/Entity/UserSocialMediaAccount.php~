<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserSocialMediaAccount
 */
class UserSocialMediaAccount
{

    /**
     * @var integer
     */
    private $userSocialMediaAccountId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccountType
     */
    private $userSocialMediaAccountType;

    /**
     * Get userSocialMediaAccountId
     *
     * @return integer 
     */
    public function getUserSocialMediaAccountId()
    {
        return $this->userSocialMediaAccountId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserSocialMediaAccount
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserSocialMediaAccount
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
     * Set userSocialMediaAccountType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccountType $userSocialMediaAccountType
     * @return UserSocialMediaAccount
     */
    public function setUserSocialMediaAccountType(\Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccountType $userSocialMediaAccountType = null)
    {
        $this->userSocialMediaAccountType = $userSocialMediaAccountType;

        return $this;
    }

    /**
     * Get userSocialMediaAccountType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccountType 
     */
    public function getUserSocialMediaAccountType()
    {
        return $this->userSocialMediaAccountType;
    }
}
