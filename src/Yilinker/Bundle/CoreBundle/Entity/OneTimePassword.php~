<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OneTimePassword
 */
class OneTimePassword
{
    const PROVIDER_SEMAPHORE = 0;

    const PROVIDER_MOBIWEB = 1;
    
    const PROVIDER_UCPASS = 2;

    /**
     * @var integer
     */
    private $oneTimePasswordId;

    /**
     * @var string
     */
    private $contactNumber;

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
     * @var \DateTime
     */
    private $tokenExpiration;

    /**
     * @var boolean
     */
    private $isActive = true;

    /**
     * @var string
     */
    private $tokenType;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    private $country;

    /**
     * @var integer
     */
    private $provider = '0';

    /**
     * Get oneTimePasswordId
     *
     * @return integer
     */
    public function getOneTimePasswordId()
    {
        return $this->oneTimePasswordId;
    }

    /**
     * Set contactNumber
     *
     * @param string $contactNumber
     * @return OneTimePassword
     */
    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Get contactNumber
     *
     * @return string
     */
    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return OneTimePassword
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
     * @return OneTimePassword
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
     * @return OneTimePassword
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
     * Set tokenExpiration
     *
     * @param \DateTime $tokenExpiration
     * @return OneTimePassword
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
     * @return OneTimePassword
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
     * @return OneTimePassword
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

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return OneTimePassword
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
     * Set country
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country $country
     * @return OneTimePassword
     */
    public function setCountry(\Yilinker\Bundle\CoreBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set provider
     *
     * @param integer $provider
     * @return OneTimePassword
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Get provider
     *
     * @return integer
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Get provider
     *
     * @return integer
     */
    public function getProviderName()
    {
        $name = "";

        switch($this->getProvider()){
            case self::PROVIDER_SEMAPHORE:
                $name = "Semaphore";
                break;
            case self::PROVIDER_MOBIWEB:
                $name = "Mobiweb";
                break;
            case self::PROVIDER_UCPASS:
            	$name = 'Ucpass';
            	break;
            default:
                $name = "Semaphore";
                break;
        }

        return $name;
    }
}
