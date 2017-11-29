<?php
namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserVerificationToken
 */
class UserVerificationToken 
{

    const TYPE_EMAIL = 0;

    const TYPE_CONTACT_NUMBER = 1;

    /**
     * @var integer
     */
    private $userVerificationTokenId;
        
    /**
     * @var integer
     */
    private $tokenType;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $token;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var boolean
     */
    private $isActive = true;

    public function __construct()
    {
        $this->tokenType = self::TYPE_EMAIL;
    }

    /**
     * Get userVerificationTokenId
     *
     * @return integer 
     */
    public function getUserVerificationTokenId()
    {
        return $this->userVerificationTokenId;
    }

    /**
     * Set field
     *
     * @param string $field
     * @return UserVerificationToken
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return string 
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return UserVerificationToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserVerificationToken
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

    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return UserVerificationToken
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserVerificationToken
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
     * @var \DateTime
     */
    private $tokenExpiration;


    /**
     * Set tokenExpiration
     *
     * @param \DateTime $tokenExpiration
     * @return UserVerificationToken
     */
    public function setTokenExpiration($tokenExpiration)
    {
        $this->tokenExpiration = $tokenExpiration;

        return $this;
    }

    /**
     * Get tokenExpiration
     *
     * @return \DateTime 
     */
    public function getTokenExpiration()
    {
        return $this->tokenExpiration;
    }


    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return UserVerificationToken
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }


    /**
     * Set tokenType
     *
     * @param integer $tokenType
     * @return UserVerificationToken
     */
    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;

        return $this;
    }

    /**
     * Get tokenType
     *
     * @return integer 
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

}
