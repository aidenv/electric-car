<?php
namespace Yilinker\Bundle\FrontendBundle\Services\User;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserFollow;
use Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Services\Contact\ContactService;

/**
 * Class UserFollowService
 * @package Yilinker\Bundle\FrontendBundle\Services\Message
 */
class UserFollowService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var null
     */
    public $authenticatedUser = null;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var  ContactService
     */
    private $contactService;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(EntityManager $entityManager, AssetsHelper $assetsHelper, ContactService $contactService)
    {
        $this->entityManager = $entityManager;
        $this->assetsHelper = $assetsHelper;
        $this->contactService = $contactService;
    }

    /**
     * @param null $authenticatedUser
     */
    public function setAuthenticatedUser($authenticatedUser)
    {
        $this->authenticatedUser = $authenticatedUser;
    }

    public function followSeller(User $seller)
    {
        $authenticatedUser = $this->authenticatedUser;

        $userFollow = new UserFollow();
        $userFollow->setFollower($authenticatedUser)
                   ->setFollowee($seller);

        $userFollowHistory = new UserFollowHistory();
        $userFollowHistory->setFollower($authenticatedUser)
                          ->setFollowee($seller)
                          ->setIsFollow(true)
                          ->setDateCreated(Carbon::now());

        $this->contactService->addToContact($authenticatedUser, $seller);

        $this->entityManager->persist($userFollow);
        $this->entityManager->persist($userFollowHistory);
        $this->entityManager->flush();

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Successfully followed the seller",
            "data" => array(
                "sellerId" => $seller->getUserId(),
                "dateSent" => Carbon::now()->toDateTimeString()
            )
        ));
    }

    public function unfollowSeller(User $seller, UserFollow $record)
    {
        $authenticatedUser = $this->authenticatedUser;

        $this->entityManager->remove($record);

        $userFollowHistory = new UserFollowHistory();
        $userFollowHistory->setFollower($authenticatedUser)
                          ->setFollowee($seller)
                          ->setIsFollow(false)
                          ->setDateCreated(Carbon::now());

        $this->entityManager->persist($userFollowHistory);
        $this->entityManager->flush();

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Successfully unfollowed the seller",
            "data" => array(
                "sellerId" => $seller->getUserId(),
                "dateSent" => Carbon::now()->toDateTimeString()
            )
        ));
    }

    /**
     * Fetch the followed history of the user
     *
     * @param int $limit
     * @param int $page
     * @return JsonResponse
     */
    public function getFollowHistory($limit = 10, $page = 1)
    {
        $offset = $this->getOffset($limit, $page);

        $userFollowHistoryRepository = $this->entityManager->getRepository("YilinkerCoreBundle:UserFollowHistory");

        $userFollowHistory = $userFollowHistoryRepository->loadUserFollowHistory($this->authenticatedUser, $limit, $offset);

        $userFollowHistoryData = $this->constructFollowHistory($userFollowHistory);

        $response = array(
            "isSuccessful" => true,
            "message" => "Successfully fetched history.",
            "data" => $userFollowHistoryData
        );

        return new JsonResponse($response, 200);
    }

    /**
     * Fetch the followed history of the user
     *
     * @param string $keyword
     * @param int $limit
     * @param int $page
     * @return JsonResponse
     */
    public function getFollowedSellers($keyword ="", $limit = 10, $page = 1)
    {
        // TODO : refactor upon creating seller reviews
        $offset = $this->getOffset($limit, $page);

        $userFollowRepository = $this->entityManager->getRepository("YilinkerCoreBundle:UserFollow");

        $followedSellers = $userFollowRepository->loadFollowedSellers($this->authenticatedUser, $keyword, $limit, $offset);

        $followedSellersData = $this->constructSellers($followedSellers);

        $response = array(
            "isSuccessful" => true,
            "message" => "Successfully fetched followed sellers.",
            "data" => $followedSellersData
        );

        return new JsonResponse($response, 200);
    }

    /**
     * Check if followed
     *
     * @param User $seller
     * @return null|object
     */
    public function isFollowed(User $seller)
    {
        $record = $this->entityManager
                       ->getRepository('YilinkerCoreBundle:UserFollow')
                       ->findOneBy(array(
                           "followee" => $seller,
                           "follower" => $this->authenticatedUser
                       ));

        return $record;
    }

    /**
     * Required fields not supplied
     *
     * @param null $form
     * @param bool|true $isFormTransaction
     * @param null $errors
     * @return JsonResponse
     */
    public function throwInvalidFields($form = null, $isFormTransaction = true, $errors = null)
    {
        if($isFormTransaction){
            $errors = array($this->generateErrors($form));
        }

        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "message" => "Invalid fields supplied.",
            "data" => $errors
        );

        return new JsonResponse($response, 400);
    }

    /**
     * Seller is already followed
     *
     * @return JsonResponse
     */
    public function throwAlreadyFollowed()
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "message" => "Seller is already followed.",
            "data" => array()
        );

        return new JsonResponse($response, 400);
    }

    /**
     * Seller is already followed
     *
     * @return JsonResponse
     */
    public function throwAlreadyUnfollowed()
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "message" => "Seller not followed.",
            "data" => array()
        );

        return new JsonResponse($response, 400);
    }

    /**
     * User not found in DB
     *
     * @return JsonResponse
     */
    public function throwUserNotFound()
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "message" => "Requested user not found.",
            "data" => array()
        );

        return new JsonResponse($response, 402);
    }

    /**
     * get Offset for pagination
     *
     * @param int $limit
     * @param int $page
     * @return int
     */
    public function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }

    private function constructFollowHistory($userFollowHistory)
    {
        $logs = array();

        foreach($userFollowHistory as $followHistory){

            $profileImageUrl = null;

            $userImage = $followHistory->getFollowee()->getPrimaryImage();

            if($userImage){
                $profileImageUrl = $this->assetsHelper->getUrl($userImage->getImageLocation(), 'user');
            }
            else{
                $profileImageUrl = "";
            }

            $seller = $followHistory->getFollowee();
            $store = $seller->getStore();

            $log = array(
                "sellerId"        => $seller->getUserId(),
                "fullName"        => $seller->getFullName(),
                "firstName"       => $seller->getFirstName(),
                "lastName"        => $seller->getLastName(),
                "storeName"       => $store->getStoreName(),
                "profileImageUrl" => $profileImageUrl,
                "isFollowed"      => $followHistory->getIsFollow(),
                "slug"            => $store->getStoreSlug(),
                "date"            => $followHistory->getDateCreated()->format('F d Y H:i:s A')
            );

            array_push($logs, $log);
        }

        return $logs;
    }

    public function constructSellers($sellers)
    {
        $data = array();
        $productCategoryRepository = $this->entityManager->getRepository("YilinkerCoreBundle:ProductCategory");

        foreach($sellers as $seller){

            $profileImageUrl = null;
            $userSpecialty = null;

            $userImage = $seller->getPrimaryImage();
        
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

            $store = $seller->getStore();

            $specialty = $productCategoryRepository->getUserSpecialty($seller);

            if(!is_null($specialty)){
                $userSpecialty = $specialty->getName();
            }

            $user = array(
                "sellerId"          => $seller->getUserId(),
                "fullName"          => $seller->getFullName(),
                "firstName"         => $seller->getFirstName(),
                "lastName"          => $seller->getLastName(),
                "storeName"         => $store->getStoreName(),
                "rating"            => $store->getRating(),
                "profileImageUrl"   => $this->assetsHelper->getUrl($profileImageUrl, 'user'),
                "smallImageUrl"     => $this->assetsHelper->getUrl($smallImageUrl, 'user'),
                "mediumImageUrl"    => $this->assetsHelper->getUrl($mediumImageUrl, 'user'),
                "largeImageUrl"     => $this->assetsHelper->getUrl($largeImageUrl, 'user'),
                "thumbnailImageUrl" => $this->assetsHelper->getUrl($thumbnailImageUrl, 'user'),
                "slug"              => $store->getStoreSlug(),
                "specialty"         => $userSpecialty,
            );

            array_push($data, $user);
        }

        return $data;
    }
}
