<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserSocialMediaAccountType
 */
class UserSocialMediaAccountType
{

    const FACEBOOK_TYPE = 1;

    const GOOGLE_TYPE = 2;

    const TWITTER_TYPE =3;

    /**
     * @var integer
     */
    private $userSocialMediaAccountTypeId;

    /**
     * @var string
     */
    private $name;


    /**
     * Get userSocialMediaAccountTypeId
     *
     * @return integer 
     */
    public function getUserSocialMediaAccountTypeId()
    {
        return $this->userSocialMediaAccountTypeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserSocialMediaAccountType
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
}
