<?php
namespace Yilinker\Bundle\MerchantBundle\Services\User;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\StoreCategory;
use Yilinker\Bundle\CoreBundle\Services\Upload\UploadService;
use Yilinker\Bundle\CoreBundle\Services\Jwt\JwtManager;
use Yilinker\Bundle\CoreBundle\Services\Yilinker\Account;
use Yilinker\Bundle\CoreBundle\Services\QrCode\Generator;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank as NotBlankConstraint;


class AccountManager
{

    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var uploadService
     */
    private $uploadService;

    /**
     * @var jwtManager
     */
    private $jwtManager;

    /**
     * @var  Generator
     */
    private $qrCodeGenerator;

    /**
     * @var account
     */
    private $ylaService;

    /**
     * @var  AssetsHelper
     */
    private $assetsHelper;

    private $filesystem;

    private $imageManipulator;

    private $container;
    /**
     * @param EntityManager $entityManager
     * @param UploadService $uploadService
     */
    public function __construct(
        EntityManager $entityManager, 
        UploadService $uploadService,
        JwtManager $jwtManager,
        Account $ylaService,
        Generator $qrCodeGenerator,
        AssetsHelper $assetsHelper,
        $filesystem,
        $imageManipulator,
        $container
    )
    {
        $this->em = $entityManager;
        $this->uploadService = $uploadService;
        $this->jwtManager = $jwtManager;
        $this->ylaService = $ylaService;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->assetsHelper = $assetsHelper;
        $this->filesystem = $filesystem;
        $this->imageManipulator = $imageManipulator;
        $this->container = $container;
    }

    /**
     * Update user info
     *
     * @param User $user
     * @param $data
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function updateUserInfo(User $user, $data)
    {
        $entityManager = $this->em;

        $entityManager->getConnection()->beginTransaction();
        $coverPhoto = null;
        $profilePhoto = null;
        $errors = array();

        $store = $user->getStore();

        try{
            if(array_key_exists("storeName", $data) && $store->getIsEditable()){
                $duplicateStore = $entityManager->getRepository("YilinkerCoreBundle:Store")->getStoreByStoreName($data["storeName"], $user);
                if(!empty($duplicateStore)){
                    array_push($errors, "Store name already in use.");
                }

                if($data["storeName"] == ""){
                    array_push($errors, "Store name is required.");
                }
                else{
                    $store->setStoreName($data["storeName"]);
                }
            }

            if(array_key_exists("storeDescription", $data)){
                if($data["storeDescription"] == ""){
                    array_push($errors, "Store description is required.");
                }
                else{
                    $store->setStoreDescription($data["storeDescription"]);
                }
            }

            if(array_key_exists("storeSlug", $data) && $store->getStoreSlug() != $data["storeSlug"] && $store->getIsEditable()){
                if(!$store->getSlugChanged()){

                    $this->qrCodeGenerator->generateStoreQrCode($store, $data["storeSlug"]);
                    $store->setStoreSlug($data["storeSlug"]);
                    $store->setSlugChanged(true);
                }
                else{
                    array_push($errors, "This user has already changed the slug.");
                }
            }

            if(array_key_exists("coverPhoto", $data)){
                if(is_null($data["coverPhoto"])){
                    array_push($errors, "Failed to upload cover photo.");
                }else{
                    $this->uploadService->setType("user", $user->getUserId());
                    $file = $this->uploadService->uploadFile($data["coverPhoto"]);
                    if(!$file){
                        array_push($errors, "Failed to upload cover photo.");
                    }
                    else{
                        $coverPhoto = $this->instantiateUserPhoto($user, $file, true, false, UserImage::IMAGE_TYPE_BANNER);
                        $entityManager->persist($coverPhoto);
                    }
                }
            }

            if(array_key_exists("profilePhoto", $data)){
                if(is_null($data["profilePhoto"])){
                    array_push($errors, "Failed to upload profile photo.");
                }else{
                    $this->uploadService->setType("user", $user->getUserId());
                    $file = $this->uploadService->uploadFile($data["profilePhoto"]);
                    if(!$file){
                        array_push($errors, "Failed to upload profile photo.");
                    }
                    else{
                        $profilePhoto = $this->instantiateUserPhoto($user, $file, true, false, UserImage::IMAGE_TYPE_AVATAR);
                        $entityManager->persist($profilePhoto);
                    }
                }
            }

            if(
                array_key_exists('firstName', $data) OR 
                array_key_exists('lastName', $data) OR 
                array_key_exists('gender', $data) OR 
                array_key_exists('birthdate', $data) OR 
                array_key_exists('nickname', $data)
            ){

                if(array_key_exists('firstName', $data)){
                    $user->setFirstName($data["firstName"]);
                }

                if(array_key_exists('lastName', $data)){
                    $user->setLastName($data["lastName"]);
                }

                if(array_key_exists('gender', $data)){
                    $user->setGender($data["gender"]);
                }

                if(array_key_exists('birthdate', $data)){
                    $user->setBirthdate(Carbon::instance(new DateTime($data["birthdate"])));
                }

                if(array_key_exists('nickname', $data)){
                    $user->setNickname($data["nickname"]);
                }

                $user->setDateLastModified(Carbon::now());
                $entityManager->persist($user);
            }

            if(array_key_exists("categoryIds", $data)){
                $store = $user->getStore();
                $storeCategories = $data["categoryIds"]->toArray();

                foreach($store->getStoreCategories() as $storeCategory){
                    
                    $productCategory = $storeCategory->getProductCategory();
                    
                    if(!in_array($productCategory, $storeCategories)){
                        $store->removeStoreCategory($storeCategory);
                        $entityManager->remove($storeCategory);
                    }
                    else{
                        $index = array_search($productCategory, $storeCategories);
                        unset($storeCategories[$index]);
                    }
                }

                foreach ($storeCategories as $productCategory) {
                    $storeCategory = new StoreCategory();
                    $storeCategory->setDateAdded(Carbon::now())
                                  ->setDateLastModified(Carbon::now())
                                  ->setStore($store)
                                  ->setProductCategory($productCategory);

                    $entityManager->persist($storeCategory);
                    $store->addStoreCategory($storeCategory);
                }
            }

            if(!empty($errors)){
                throw new Exception("Invalid transaction.");
            }

            $request = $this->jwtManager->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

            $this->ylaService->setEndpoint(false);

            $response = $this->ylaService->sendRequest("user_update", "post", array("request" => $request));

            $entityManager->flush();
            $entityManager->getConnection()->commit();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Info successfully updated.",
                "data" => array(
                  "storeName" => $store->getStoreName(),
                  "storeDescription" => $store->getStoreDescription(),
                  "storeSlug" => $store->getStoreSlug(),
                  "qrCodeLocation" => $this->assetsHelper->getUrl($store->getQrCodeLocation(), 'qr_code')
                )
            ), 200);
        }
        catch (Exception $e){
            $entityManager->getConnection()->rollback();

            return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => $e->getMessage(),
                    "data" => array(
                        "errors" => $errors,
                    )
                ), 400);
        }
    }

    /**
     * Create image object
     *
     * @param User $user
     * @param $file
     * @param $isPrimary
     * @param $isHidden
     * @param $imageType
     * @return UserImage
     */
    private function instantiateUserPhoto(User $user, $file, $isPrimary, $isHidden, $imageType)
    {
        $photo = new UserImage();
        $photo->setUser($user)
              ->setImageLocation($file)
              ->setIsHidden($isHidden)
              ->setUserImageType($imageType)
              ->setDateAdded(Carbon::now());
        if ($isPrimary) {
            if($imageType ===  UserImage::IMAGE_TYPE_BANNER){
                $user->setPrimaryCoverPhoto($photo);
            }
            else{
                $user->setPrimaryImage($photo);
            }
        }

        $this->createResizedImage($user, $file, $imageType);

        return $photo;
    }

    private function createResizedImage($user, $file, $imageType)
    {
        $userId = $user->getUserId();
        $userFileLocation = 'assets/images/uploads/users/'.$userId;

        $sizes = array(
            UserImage::IMAGE_SIZE_THUMBNAIL => array(
                "width" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_THUMBNAIL_WIDTH : UserImage::AVATAR_SIZE_THUMBNAIL_WIDTH,
                "height" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_THUMBNAIL_HEIGHT : UserImage::AVATAR_SIZE_THUMBNAIL_HEIGHT,
            ),
            UserImage::IMAGE_SIZE_SMALL => array(
                "width" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_SMALL_WIDTH : UserImage::AVATAR_SIZE_SMALL_WIDTH,
                "height" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_SMALL_HEIGHT : UserImage::AVATAR_SIZE_SMALL_HEIGHT,
            ),
            UserImage::IMAGE_SIZE_MEDIUM => array(
                "width" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_MEDIUM_WIDTH : UserImage::AVATAR_SIZE_MEDIUM_WIDTH,
                "height" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_MEDIUM_HEIGHT : UserImage::AVATAR_SIZE_MEDIUM_HEIGHT,
            ),
            UserImage::IMAGE_SIZE_LARGE => array(
                "width" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_LARGE_WIDTH : UserImage::AVATAR_SIZE_LARGE_WIDTH,
                "height" => $imageType === UserImage::IMAGE_TYPE_BANNER? UserImage::COVER_SIZE_LARGE_HEIGHT : UserImage::AVATAR_SIZE_LARGE_HEIGHT,
            )
        );

        foreach($sizes as $folder => $size){
            $this->imageManipulator->writeThumbnail(
                $userFileLocation.DIRECTORY_SEPARATOR.$file, 
                $userFileLocation.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$file, 
                array(
                "filters" => array(
                    "thumbnail" => array(
                        "size" => array($size["width"], $size["height"])
                    ),
                ),
            ));

            $this->uploadToCloud($userFileLocation.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$file);
        }
    }

    private function uploadToCloud($imageDir)
    {
        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof AwsS3){
            $file = new File($imageDir);
            $adapter->setMetadata($imageDir, array('contentType' => $file->getMimeType()));
            $result = $adapter->write($imageDir, file_get_contents($file->getPathname()));
        }
    }

    /**
     * Generates forgot password token
     *
     * @param User $user
     * @return User
     */
    public function generateForgotPasswordToken(User $user)
    {
        $forgotPasswordToken = sha1(uniqid(time()).$user->getEmail().uniqid(time()));

        $user->setForgotPasswordToken($forgotPasswordToken)
             ->setForgotPasswordTokenExpiration(Carbon::now()->addHours(2));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * [updateUser description]
     * @param  User   $user 
     * @param  [array] $data parameters
     * @return [array]       [description]
     */
    public function updateUser(User $user, $data)
    {
        $data = array_merge(array('isSent' => false),$data);

        $entityManager = $this->em;
        
        $verificationService = $this->container->get("yilinker_core.service.user.verification");
        $mailerService = $this->container->get("yilinker_core.service.user.mailer");
        $entityManager->getConnection()->beginTransaction();
        
        $errors = array();

        try{
            $emailConstraint = new EmailConstraint();
            $notBlankConstraint = new NotBlankConstraint();

            $v = $this->container->get('validator');
            
            if(array_key_exists("referralCode", $data)) {
                $coreAccountManager = $this->container->get('yilinker_core.service.account_manager');
                $referralCode = $data['referralCode'];

                $updateReferralCode = strlen($referralCode) > 0 && !$user->getUserReferral();

                if (strlen($referralCode) && $user->getUserReferral()) {
                    array_push($errors, 'You can only refer once per account');
                }

                if ($updateReferralCode) {
                    $processReferralCode = $coreAccountManager->processReferralCode($referralCode, $user);
                    if ((bool) $processReferralCode['isSuccessful'] === false) {
                        array_push($errors, $processReferralCode['message']);
                    }
                }
            }

            if(array_key_exists("firstName", $data)) {
                $error = $v->validate($data['firstName'],$notBlankConstraint)->getIterator()->current();
                if ($error) {
                    array_push($errors, 'First Name should not be blank');
                }
                
                $user->setFirstName($data['firstName']);
            }

            if(array_key_exists("lastName", $data)) {
                $error = $v->validate($data['lastName'],$notBlankConstraint)->getIterator()->current();
                if ($error) {
                    array_push($errors, 'Last Name should not be blank');
                }
                
                $user->setLastName($data['lastName']);
            }
            
            if(array_key_exists("tin", $data)) {
                $user->setTin($data['tin']);
                $accreditationApplication = $entityManager->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($user);
                $accreditationApplication->setIsBusinessEditable(0);
            }

            if(array_key_exists("email", $data)) {

                $error = $v->validate($data['email'],$emailConstraint)->getIterator()->current();
                if ($error) {
                    array_push($errors, 'Email is not a valid address.');
                }

                $user->setEmail($data['email']);
                $user->setIsEmailVerified(false);

                if ($data['isSent'] === false) {
                    $verificationService->createVerificationToken($user, $data['email']);
                    $mailerService->sendEmailVerification($user);    
                }
            }

            if(!empty($errors)){
                throw new Exception("Invalid transaction.");
            }


            $entityManager->persist($user);
            $entityManager->flush();
            $entityManager->getConnection()->commit();

            return array(
                "isSuccessful" => true,
                "message" => "Info successfully updated.",
                "data" => array(
                  "firstName" => $user->getFirstName(),
                  "lastName" => $user->getLastName(),
                  "tin" => $user->getTin(),
                  "email" => $user->getEmail(),
                )
            );
        
        } catch (Exception $e){
            $entityManager->getConnection()->rollback();

            return array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array(
                    "errors" => $errors,
                )
            );
        }

    }

    /**
     * Send Email Verification
     * @param  $data['email']
     * @return boolean | array response
     */
    public function sendVerification(User $user, $data)
    {
        $response = $this->updateUser($user,$data);
        
        if ($response['isSuccessful'] === true) {
            $response['message'] = 'Successfully Sent.';
        }


        return $response;
    }
}
