<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DeviceNotification
 */
class DeviceNotification
{
	const TARGET_TYPE_HOME = "home"; //native home

	const TARGET_TYPE_WEBVIEW = "webView"; //webview pages (flash sale, categories)

	const TARGET_TYPE_PRODUCT = "product"; //native product 

	const TARGET_TYPE_PRODUCT_LIST = "productList"; //native product search

	const TARGET_TYPE_STORE = "seller"; //native store

	const TARGET_TYPE_STORE_LIST = "sellerList"; //native store search

    const TARGET_FLASH_SALE = "flashSale";

    const TARGET_CATEGORIES = "categories";
    
    const TARGET_HOT_ITEMS = "hotItems";
    
    const TARGET_NEW_ITEMS = "newItems";
    
    const TARGET_TODAYS_PROMO = "todaysPromo";
    
    const TARGET_NEW_STORES = "newStores";
    
    const TARGET_HOT_STORES = "hotStores";

    const TARGET_DAILY_LOGIN = "dailyLogin";

	const RECIPIENT_ALL = 0; //both android and ios

	const RECIPIENT_ANDROID = 1; //android only

	const RECIPIENT_IOS = 2; //ios only

    /**
     * @var integer
     */
    private $deviceNotificationId;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $target;

    /**
     * @var integer
     */
    private $targetType = '0';

    /**
     * @var boolean
     */
    private $isSent = false;

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
    private $dateScheduled;

    /**
     * @var \DateTime
     */
    private $dateSent;

    /**
     * @var integer
     */
    private $recipient = '0';

    /**
     * @var string
     */
    private $targetParameters;
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $createdBy;
    
    /**
     * @var boolean
     */
    private $isActive = true;
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * Get deviceNotificationId
     *
     * @return integer 
     */
    public function getDeviceNotificationId()
    {
        return $this->deviceNotificationId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return DeviceNotification
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return DeviceNotification
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set target
     *
     * @param string $target
     * @return DeviceNotification
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set targetType
     *
     * @param integer $targetType
     * @return DeviceNotification
     */
    public function setTargetType($targetType)
    {
        $this->targetType = $targetType;

        return $this;
    }

    /**
     * Get targetType
     *
     * @return integer 
     */
    public function getTargetType()
    {
        return $this->targetType;
    }

    /**
     * Set isSent
     *
     * @param boolean $isSent
     * @return DeviceNotification
     */
    public function setIsSent($isSent)
    {
        $this->isSent = $isSent;

        return $this;
    }

    /**
     * Get isSent
     *
     * @return boolean 
     */
    public function getIsSent()
    {
        return $this->isSent;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return DeviceNotification
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
     * @return DeviceNotification
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
     * Set dateScheduled
     *
     * @param \DateTime $dateScheduled
     * @return DeviceNotification
     */
    public function setDateScheduled($dateScheduled)
    {
        $this->dateScheduled = $dateScheduled;

        return $this;
    }

    /**
     * Get dateScheduled
     *
     * @return \DateTime 
     */
    public function getDateScheduled()
    {
        return $this->dateScheduled;
    }

    /**
     * Set dateSent
     *
     * @param \DateTime $dateSent
     * @return DeviceNotification
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;

        return $this;
    }

    /**
     * Get dateSent
     *
     * @return \DateTime 
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * Set recipient
     *
     * @param integer $recipient
     * @return DeviceNotification
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return integer 
     */
    public function getRecipient($isString = false)
    {
        if($isString){

            switch ($this->recipient) {
                case self::RECIPIENT_ANDROID:
                    $this->recipient = "Android";
                    break;
                case self::RECIPIENT_IOS:
                    $this->recipient = "IOS";
                    break;
                default:
                    $this->recipient = "Android, IOS";
                    break;
            }
        }

        return $this->recipient;
    }

    /**
     * Set targetParameters
     *
     * @param string $targetParameters
     * @return DeviceNotification
     */
    public function setTargetParameters($targetParameters)
    {
        $this->targetParameters = $targetParameters;

        return $this;
    }

    /**
     * Get targetParameters
     *
     * @return string 
     */
    public function getTargetParameters()
    {
        return $this->targetParameters;
    }

    /**
     * Set createdBy
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $createdBy
     * @return DeviceNotification
     */
    public function setCreatedBy(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function __toArray()
    {
        return array(
            "deviceNotificationId"  => $this->deviceNotificationId,
            "title"                 => $this->title,
            "message"               => $this->message,
            "target"                => $this->target,
            "targetType"            => $this->targetType,
            "isSent"                => $this->isSent,
            "recipient"             => $this->recipient,
            "targetParameters"      => $this->targetParameters,
            "isActive"              => $this->isActive,
            "dateAdded"             => $this->dateAdded? $this->dateAdded->format("m/d/Y H:i:s"):null,
            "dateScheduled"         => $this->dateScheduled? $this->dateScheduled->format("m/d/Y H:i:s"):null,
            "dateSent"              => $this->dateSent? $this->dateSent->format("m/d/Y H:i:s"):null,
            "createdBy"             => $this->createdBy? $this->createdBy->getFullName() : null,
            "product"               => $this->product? array(
                                        "productId" => $this->product->getProductId(),
                                        "slug" => $this->product->getSlug()
                                    ) : null,
            "store"                 => ($this->user && $this->user->getStore())? array(
                                        "userId" => $this->user->getUserId(),
                                        "storeSlug" => $this->user->getStore()->getStoreSlug()
                                    ) : null
        );
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return DeviceNotification
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
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return DeviceNotification
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return DeviceNotification
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
