<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Device
 */
class Device
{
    const TOKEN_TYPE_REGISTRATION_ID = 0;

    const TOKEN_TYPE_DEVICE_TOKEN = 1;

    const TOKEN_TYPE_JWT = 2;

    const DEVICE_TYPE_ANDROID = 0;

    const DEVICE_TYPE_IOS = 1;

    const DEVICE_TYPE_WEB = 2;

    /**
     * @var integer
     */
    private $deviceId;

    /**
     * @var string
     */
    private $registrationId;

    /**
     * @var User
     */
    private $user;
    
    /**
     * @var integer
     */
    private $deviceType = '0';
    /**
     * @var string
     */
    private $token;

    /**
     * @var boolean
     */
    private $isDelete = false;

    /**
     * @var integer
     */
    private $tokenType = '0';

    /**
     * @var boolean
     */
    private $isIdle;
    
    /**
     * @var boolean
     */
    private $isNotificationSubscribe = true;

    /**
     * Set token
     *
     * @param string $token
     * @return Device
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
     * Set isDelete
     *
     * @param boolean $isDelete
     * @return Device
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete
     *
     * @return boolean 
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * Set tokenType
     *
     * @param integer $tokenType
     * @return Device
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
     * Get deviceId
     *
     * @return integer 
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Set registrationId
     *
     * @param string $registrationId
     * @return Device
     */
    public function setRegistrationId($registrationId)
    {
        $this->registrationId = $registrationId;

        return $this;
    }

    /**
     * Get registrationId
     *
     * @return string 
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Device
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set isIdle
     *
     * @param boolean $isIdle
     * @return Device
     */
    public function setIsIdle($isIdle)
    {
        $this->isIdle = $isIdle;

        return $this;
    }

    /**
     * Get isIdle
     *
     * @return boolean
     */
    public function getIsIdle()
    {
        return $this->isIdle;
    }

    /**
     * Set deviceType
     *
     * @param integer $deviceType
     * @return Device
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * Get deviceType
     *
     * @return integer 
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * Set isNotificationSubscribe
     *
     * @param boolean $isNotificationSubscribe
     * @return Device
     */
    public function setIsNotificationSubscribe($isNotificationSubscribe)
    {
        $this->isNotificationSubscribe = $isNotificationSubscribe;

        return $this;
    }

    /**
     * Get isNotificationSubscribe
     *
     * @return boolean 
     */
    public function getIsNotificationSubscribe()
    {
        return $this->isNotificationSubscribe;
    }
}
