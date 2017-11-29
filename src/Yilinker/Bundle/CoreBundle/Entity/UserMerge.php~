<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserMerge
 */
class UserMerge
{

    /**
     * @var integer
     */
    private $userMergeId;

    /**
     * @var string
     */
    private $socialMediaId = '';

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OauthProvider
     */
    private $oauthProvider;


    /**
     * Get userMergeId
     *
     * @return integer 
     */
    public function getUserMergeId()
    {
        return $this->userMergeId;
    }

    /**
     * Set socialMediaId
     *
     * @param string $socialMediaId
     * @return UserMerge
     */
    public function setSocialMediaId($socialMediaId)
    {
        $this->socialMediaId = $socialMediaId;

        return $this;
    }

    /**
     * Get socialMediaId
     *
     * @return string 
     */
    public function getSocialMediaId()
    {
        return $this->socialMediaId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return UserMerge
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
     * @return UserMerge
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
     * Set oauthProvider
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OauthProvider $oauthProvider
     * @return UserMerge
     */
    public function setOauthProvider(\Yilinker\Bundle\CoreBundle\Entity\OauthProvider $oauthProvider = null)
    {
        $this->oauthProvider = $oauthProvider;

        return $this;
    }

    /**
     * Get oauthProvider
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OauthProvider 
     */
    public function getOauthProvider()
    {
        return $this->oauthProvider;
    }
}
