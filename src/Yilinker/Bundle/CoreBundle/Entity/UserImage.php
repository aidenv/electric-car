<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * UserImage
 */
class UserImage
{

    const IMAGE_TYPE_AVATAR = 0;

    const IMAGE_TYPE_BANNER = 1;

    const IMAGE_SIZE_SMALL = "small";
    
    const IMAGE_SIZE_MEDIUM = "medium";
    
    const IMAGE_SIZE_LARGE = "large";

    const IMAGE_SIZE_THUMBNAIL = "thumbnail";

    const DEFAULT_DIRECTORY = 'defaults';

    const DEFAULT_SELLER_AVATAR_FILE = 'merchant-default-image.png';
    
    const DEFAULT_BUYER_AVATAR_FILE = 'buyer-default-image.png';

    const DEFAULT_BANNER_FILE = 'merchant-default-cover.jpg';

    const AVATAR_SIZE_THUMBNAIL_WIDTH = 50;

    const AVATAR_SIZE_THUMBNAIL_HEIGHT = 50;

    const AVATAR_SIZE_SMALL_WIDTH = 100;

    const AVATAR_SIZE_SMALL_HEIGHT = 100;

    const AVATAR_SIZE_MEDIUM_WIDTH = 200;

    const AVATAR_SIZE_MEDIUM_HEIGHT = 200;

    const AVATAR_SIZE_LARGE_WIDTH = 350;

    const AVATAR_SIZE_LARGE_HEIGHT = 350;

    const COVER_SIZE_THUMBNAIL_WIDTH = 1100;

    const COVER_SIZE_THUMBNAIL_HEIGHT = 300;

    const COVER_SIZE_SMALL_WIDTH = 1510;

    const COVER_SIZE_SMALL_HEIGHT = 420;

    const COVER_SIZE_MEDIUM_WIDTH = 2150;

    const COVER_SIZE_MEDIUM_HEIGHT = 580;

    const COVER_SIZE_LARGE_WIDTH = 2300;

    const COVER_SIZE_LARGE_HEIGHT = 600;

    /**
     * @var integer
     */
    private $userImageId;

    /**
     * @var string
     */
    private $imageLocation;

    /**
     * @var boolean
     */
    private $isHidden;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var integer
     */
    private $userImageType;

    /**
     * @var User
     */
    private $user;

    /**
     * The full image path (populated by listener)
     *
     * @var string
     */
    private $fullImagePath = null;

    /**
     * Get userImageId
     *
     * @return integer 
     */
    public function getUserImageId()
    {
        return $this->userImageId;
    }

    /**
     * Set imageLocation
     *
     * @param string $imageLocation
     * @return UserImage
     */
    public function setImageLocation($imageLocation)
    {
        $this->imageLocation = $imageLocation;

        return $this;
    }

    /**
     * Get imageLocation
     *
     * @return string 
     */
    public function getImageLocation($raw = false)
    {
        if($this->imageLocation === "" || $this->imageLocation === null){
            $imageLocation = self::DEFAULT_DIRECTORY;
            if($this->userImageType === self::IMAGE_TYPE_BANNER){
                $imageLocation .= '/'.self::DEFAULT_BANNER_FILE;
            }
            else{
                $imageLocation .= $this->user->isSeller()? '/'.self::DEFAULT_SELLER_AVATAR_FILE : '/'.self::DEFAULT_BUYER_AVATAR_FILE;
            }
        }
        else{
            if($raw){
                $imageLocation = $this->imageLocation;
            }
            else{
                $imageLocation = $this->user->getUserId().'/'.$this->imageLocation;
            }
        }

        return $imageLocation;
    }

    /**
     * Get imageLocation
     *
     * @return string 
     */
    public function getImageLocationBySize($size = null)
    {
        if($this->imageLocation === "" || $this->imageLocation === null){
            $imageLocation = self::DEFAULT_DIRECTORY;
            if($this->userImageType === self::IMAGE_TYPE_BANNER){
                $imageLocation .= '/'.self::DEFAULT_BANNER_FILE;
            }
            else{
                $imageLocation .= $this->user->isSeller()? '/'.self::DEFAULT_SELLER_AVATAR_FILE : '/'.self::DEFAULT_BUYER_AVATAR_FILE;
            }
        }

        if($this->imageLocation !== "" || $this->imageLocation !== null){
            $folder = !is_null($size)? $size.DIRECTORY_SEPARATOR : "";
            $imageLocation = $this->user->getUserId().'/'.$folder.$this->imageLocation;
        }

        return $imageLocation;
    }


    /**
     * Set isHidden
     *
     * @param boolean $isHidden
     * @return UserImage
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden
     *
     * @return boolean 
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserImage
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
     * Set userImageType
     *
     * @param integer $userImageType
     * @return UserImage
     */
    public function setUserImageType($userImageType)
    {
        $this->userImageType = $userImageType;

        return $this;
    }

    /**
     * Get userImageType
     *
     * @return integer 
     */
    public function getUserImageType()
    {
        return $this->userImageType;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return UserImage
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
     * Get the full image path
     *
     * @return string
     */
    public function getFullImagePath()
    {
        return $this->fullImagePath;
    }

    /**
     * Set the full image path
     *
     * @param string $fullImagePath
     */
    public function setFullImagePath($fullImagePath)
    {
        $this->fullImagePath = $fullImagePath;
    }
    
}
