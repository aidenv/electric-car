<?php

namespace Yilinker\Bundle\MerchantBundle\Services\User;

use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;

/**
 * Class UserFollowManager
 * @package Yilinker\Bundle\MerchantBundle\Services\User
 */
class UserFollowManager
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(EntityManager $entityManager, AssetsHelper $assetsHelper)
    {
        $this->entityManager = $entityManager;
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * Construct User to array
     * @param $users
     * @return array
     */
    public function constructUser ($users)
    {
        $data = array();

        foreach ($users as $user) {

            $profileImageUrl = null;

            $userImage = $user->getPrimaryImage();
        
            if($userImage){
                $images = array(
                            $userImage->getImageLocation(),
                            $userImage->getImageLocationBySize("small"),
                            $userImage->getImageLocationBySize("medium"),
                            $userImage->getImageLocationBySize("large"),
                            $userImage->getImageLocationBySize("thumbnail"),
                );
            }
            else{
                $images = array(
                            UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE,
                            UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE,
                            UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE,
                            UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE,
                            UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE,
                );
            }

            list($profileImageUrl, $smallImageUrl, $mediumImageUrl, $largeImageUrl, $thumbnailImageUrl) = $images;

            $userArray = array (
                "userId" => $user->getUserId(),
                "fullName" => $user->getFullName(),
                "firstName" => $user->getFirstName(),
                "lastName" => $user->getLastName(),
                "slug" => $user->getSlug(),
                "email" => $user->getEmail(),
                "contactNumber" => $user->getContactNumber(),
                "profileImageUrl"   => $this->assetsHelper->getUrl($profileImageUrl, 'user'),
                "smallImageUrl"     => $this->assetsHelper->getUrl($smallImageUrl, 'user'),
                "mediumImageUrl"    => $this->assetsHelper->getUrl($mediumImageUrl, 'user'),
                "largeImageUrl"     => $this->assetsHelper->getUrl($largeImageUrl, 'user'),
                "thumbnailImageUrl" => $this->assetsHelper->getUrl($thumbnailImageUrl, 'user'),
            );

            array_push($data, $userArray);
        }

        return $data;
    }

}