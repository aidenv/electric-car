<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\StoreLevel;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserPoint;
use Yilinker\Bundle\CoreBundle\Services\Upload\UploadService;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\UserReferral;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Model\SimpleImage;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Adapter\AwsS3;

use Yilinker\Bundle\CoreBundle\Helpers\StringHelper;

class AccountManager
{
    const REFERRAL_LETTER_LEN = 3;

    const REFERRAL_NUMBER_LEN = 3;

    /**
     * Doctrine entity manager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\User\Verification
     */
    private $verificationService;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\User\Mailer
     */
    private $mailerService;

    private $uploadService;

    /**
     * @var Gaufrette\Filesystem
     */
    private $filesystem;

    private $oneTimePasswordService;

    private $kernel;

    private $jwtManager;

    private $ylaService;

    private $authService;

    private $registrationEarner;

    private $userPointReferralManager;

    private $accreditationApplicationManager;

    /**
     * Constructor
     *
     * @param Doctrine\ORM\EntityManager $entityManager
     * @param Yilinker\Bundle\CoreBundle\Upload\UploadService $uploadService
     * @param Yilinker\Bundle\CoreBundle\Services\User\Verification $verificationService
     * @param Yilinker\Bundle\CoreBundle\Services\User\Mailer $mailerService
     * @param Gaufrette\Filesystem $filesystem
     */
    public function __construct(
        $entityManager,
        $uploadService,
        $verificationService,
        $mailerService,
        $filesystem,
        $oneTimePasswordService,
        $jwtManager,
        $ylaService,
        $authService,
        $kernel,
        $registrationEarner,
        $userPointReferralManager,
        $accreditationApplicationManager
    ){
        $this->em = $entityManager;
        $this->uploadService = $uploadService;
        $this->mailerService = $mailerService;
        $this->verificationService = $verificationService;
        $this->filesystem = $filesystem;
        $this->oneTimePasswordService = $oneTimePasswordService;
        $this->jwtManager = $jwtManager;
        $this->ylaService = $ylaService;
        $this->authService = $authService;
        $this->kernel = $kernel;
        $this->registrationEarner = $registrationEarner;
        $this->userPointReferralManager = $userPointReferralManager;
        $this->accreditationApplicationManager = $accreditationApplicationManager;
    }

    /**
     * Disable a user account
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User
     * @return boolean
     */
    public function disableAccount($user)
    {
        $this->verificationService->createReactivationCode($user);
        $user->setIsActive(false);
        /**
         * Revoke access tokens of the user
         */
        $oauthAccessTokens = $this->em->getRepository('YilinkerCoreBundle:OauthAccessToken')
                                  ->findBy(array('user' => $user));
        foreach($oauthAccessTokens as $accessToken){
            $this->em->remove($accessToken);
        }
        $this->em->flush();
        $this->mailerService->sendDeactivationNotice($user);

        return true;
    }

    /**
     * [cropAndResizeImage description]
     * @param  UploadedFile $uploadedFile uploadedimage
     * @param  string $path         path to the temp file/path of the file
     * @param  string $x            x value of the image to crop
     * @param  string $y            y value of the image to crop
     * @param  string $width        width of the cropped image from javascript
     * @param  string $height       height of the cropped image from javascript
     * @param  string $resizeHeight your set height
     * @param  string $resizeWidth  your set width
     * @return mixed                false if failed
     */
    public function cropImage(User $user, $path, $x, $y, $width, $height, $uploadedFile, $imageType = 'profilePhoto')
    {
        try{
            $image = new SimpleImage($path);

            $userId = $user->getUserId();
            $filename = sha1(uniqid().time()).".".$uploadedFile->getClientOriginalExtension();

            $this->uploadService->setType("user", $userId);
            $uploadDirectory = $this->uploadService->getUploadDirectory().DIRECTORY_SEPARATOR;

            $image->save($uploadDirectory."raw".DIRECTORY_SEPARATOR.$filename);

            $origDir = $uploadDirectory.$filename;

            $image->crop($x, $y, $x+$width, $y+$height);
            $image->save($origDir);

            $largeDir = $uploadDirectory."large".DIRECTORY_SEPARATOR.$filename;
            $mediumDir = $uploadDirectory."medium".DIRECTORY_SEPARATOR.$filename;
            $smallDir = $uploadDirectory."small".DIRECTORY_SEPARATOR.$filename;
            $thumbnailDir = $uploadDirectory."thumbnail".DIRECTORY_SEPARATOR.$filename;

            if($imageType == UserImage::IMAGE_TYPE_AVATAR){
                $this->createImageSizes($largeDir, UserImage::AVATAR_SIZE_LARGE_HEIGHT, UserImage::AVATAR_SIZE_LARGE_WIDTH, $image);
                $this->createImageSizes($mediumDir, UserImage::AVATAR_SIZE_MEDIUM_HEIGHT, UserImage::AVATAR_SIZE_MEDIUM_WIDTH, $image);
                $this->createImageSizes($smallDir, UserImage::AVATAR_SIZE_SMALL_HEIGHT, UserImage::AVATAR_SIZE_SMALL_WIDTH, $image);
                $this->createImageSizes($thumbnailDir, UserImage::AVATAR_SIZE_THUMBNAIL_HEIGHT, UserImage::AVATAR_SIZE_THUMBNAIL_WIDTH, $image);
            }
            elseif($imageType == UserImage::IMAGE_TYPE_BANNER){
                $this->createImageSizes($largeDir, UserImage::COVER_SIZE_LARGE_HEIGHT, UserImage::COVER_SIZE_LARGE_WIDTH, $image);
                $this->createImageSizes($mediumDir, UserImage::COVER_SIZE_MEDIUM_HEIGHT, UserImage::COVER_SIZE_MEDIUM_WIDTH, $image);
                $this->createImageSizes($smallDir, UserImage::COVER_SIZE_SMALL_HEIGHT, UserImage::COVER_SIZE_SMALL_WIDTH, $image);
                $this->createImageSizes($thumbnailDir, UserImage::COVER_SIZE_THUMBNAIL_HEIGHT, UserImage::COVER_SIZE_THUMBNAIL_WIDTH, $image);
            }


            $adapter = $this->filesystem->getAdapter();
            if($adapter instanceof AwsS3){
                $imagePaths = array($largeDir, $mediumDir, $smallDir, $thumbnailDir, $origDir);
                foreach($imagePaths as $imagePath){
                    $file = new File($imagePath);
                    $adapter->setMetadata($imagePath, array('contentType' => $file->getMimeType()));
                    $adapter->write($imagePath, file_get_contents($file->getPathname()));
                }
            }

            return $filename;
        }
        catch(Exception $e) {
            return false;
        }
    }

    /**
     * Create a store for a user
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User
     * @return Yilinker\Bundle\CoreBundle\Entity\Store
     */
    public function createStore(User $user, $storeType = Store::STORE_TYPE_MERCHANT, $isEditable = false)
    {
        $store = null;
        if ($user->getUserType() === User::USER_TYPE_SELLER) {
            $storeLevelReference = $this->em->getReference('YilinkerCoreBundle:StoreLevel', StoreLevel::STORE_LEVEL_SILVER);
            $store = new Store();

            $store->setUser($user);
            $store->setAccreditationLevel(null);
            $store->setStoreLevel($storeLevelReference);
            $store->setStoreType($storeType);
            $store->setIsEditable(true);

            $this->em->persist($store);
            $this->em->flush();
        }

        return $store;
    }

    private function createImageSizes($uploadDirectory, $resizeHeight, $resizeWidth, $image)
    {
        $image->resize($resizeWidth, $resizeHeight);
        $image->save($uploadDirectory);
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
    public function instantiateUserPhoto(User $user, $file, $isPrimary, $isHidden, $imageType)
    {
        $photo = new UserImage();
        $photo->setUser($user)
              ->setImageLocation($file)
              ->setIsHidden($isHidden)
              ->setUserImageType($imageType)
              ->setDateAdded(Carbon::now());
        if ($isPrimary) {
            if($imageType == UserImage::IMAGE_TYPE_BANNER){
                $user->setPrimaryCoverPhoto($photo);
            }
            else{
                $user->setPrimaryImage($photo);
            }
        }

        return $photo;
    }

    /**
     * @param $data
     * @param $storeType
     * @param $referralCode
     * @return bool
     */
    public function mapUser($data, $userType, $storeType)
    {
        $this->em->beginTransaction();

        try{

            $country = $this->em
                            ->getRepository("YilinkerCoreBundle:Country")
                            ->findOneByAreaCode($data["areaCode"]);

            $isVerified = $this->oneTimePasswordService->confirmOneTimePassword(
                            null,
                            $data["contactNumber"],
                            $data["verificationCode"],
                            OneTimePasswordService::OTP_TYPE_REGISTER,
                            true,
                            null,
                            $country->getCode()
                        );

            if ($isVerified) {

                if(
                    $userType == User::USER_TYPE_BUYER ||
                    ($userType == User::USER_TYPE_SELLER && $storeType == Store::STORE_TYPE_RESELLER)
                ){
                    $buyer = new User();
                    $buyer->setContactNumber($data["contactNumber"])
                          ->setPlainPassword($data["plainPassword"])
                          ->setCountry($country)
                          ->setUserType(User::USER_TYPE_BUYER);

                    $affiliate = new User();
                    $affiliate->setContactNumber($data["contactNumber"])
                              ->setPlainPassword($data["plainPassword"])
                              ->setCountry($country)
                              ->setUserType(User::USER_TYPE_SELLER);
                    if($userType == User::USER_TYPE_SELLER) $affiliate->setResourceId(USER::RESOURCE_AFFILIATE_ID);
                    if (isset($data["language"])) {
                        $buyer->setLanguage($data["language"]);
                        $affiliate->setLanguage($data["language"]);
                    }

                    $this->oneTimePasswordService->createSmsSubscription($data["contactNumber"], $buyer);
                    $this->oneTimePasswordService->createSmsSubscription($data["contactNumber"], $affiliate);

                    $buyer = $this->registerUser($buyer, false, true, false);
                    $affiliate = $this->registerUser($affiliate, false, true, false);

                    $store = $this->createStore($affiliate, Store::STORE_TYPE_RESELLER);
                    $affiliate->setStore($store);
                    $this->accreditationApplicationManager->createApplication($affiliate, '', Store::STORE_TYPE_RESELLER, true);

                    $user = $buyer;
                }
                else{

                    $seller = new User();
                    $seller->setContactNumber($data["contactNumber"])
                           ->setPlainPassword($data["plainPassword"])
                           ->setCountry($country)
                           ->setUserType(User::USER_TYPE_SELLER);
                    if (isset($data["language"])) {
                        $seller->setLanguage($data["language"]);
                    }

                    $this->oneTimePasswordService->createSmsSubscription($data["contactNumber"], $seller);

                    $seller = $this->registerUser($seller, false, true, false);

                    $store = $this->createStore($seller, Store::STORE_TYPE_MERCHANT);
                    $seller->setStore($store);
                    $this->accreditationApplicationManager->createApplication($seller, '', Store::STORE_TYPE_MERCHANT, true);

                    $user = $seller;
                }

                $request = $this->jwtManager->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

                $this->ylaService->setEndpoint(false);

                $response = $this->ylaService->sendRequest("user_create", "post", array("request" => $request));

                if(
                      is_array($response) &&
                      array_key_exists("isSuccessful", $response) &&
                      $response["isSuccessful"]
                ){
                    if(
                        $userType == User::USER_TYPE_BUYER ||
                        (
                          $userType == User::USER_TYPE_SELLER &&
                          $storeType == Store::STORE_TYPE_RESELLER
                        )
                    ){
                        $buyer->setAccountId($response["data"]["userId"]);
                        $affiliate->setAccountId($response["data"]["userId"]);
                    }
                    else{
                        $seller->setAccountId($response["data"]["userId"]);
                    }
                }

                $referrer = $this->em
                                 ->getRepository('YilinkerCoreBundle:User')
                                 ->getUserByReferralCode($data["referralCode"], null);

                if ($userType == User::USER_TYPE_BUYER && $referrer){

                    $userReferral = $this->addReferrer($buyer, $referrer);

                    $this->registrationEarner->get($userReferral->getUser())
                                             ->earn();

                    if ($referrer->getUserType() == User::USER_TYPE_BUYER){

                        $this->userPointReferralManager
                             ->earn (
                                 $referrer,
                                 $userReferral,
                                 UserPoint::REFERRAL_BUYER_TO_BUYER
                             );
                    }

                }
                else if (
                    $referrer &&
                    $userType == User::USER_TYPE_SELLER &&
                    $storeType == Store::STORE_TYPE_RESELLER
                ){
                    $userReferral = $this->addReferrer($affiliate, $referrer);
                    $this->registrationEarner->get($userReferral->getUser())
                                             ->earn();
                }

                $this->em->flush();
                $this->em->commit();

                if($userType == User::USER_TYPE_BUYER){
                    $this->authService->authenticateUser($buyer, 'buyer', array('ROLE_BUYER'));
                }
                else{
                    if($storeType == Store::STORE_TYPE_MERCHANT){
                        $this->authService->authenticateUser($seller, 'seller', array('ROLE_UNACCREDITED_MERCHANT'));
                    }
                    elseif($storeType == Store::STORE_TYPE_RESELLER){
                        $this->authService->authenticateUser($affiliate, 'affiliate', array('ROLE_UNACCREDITED_MERCHANT'));
                    }
                }

                return $user;
            }
            else{
                throw new YilinkerException("Please revalidate your contact number.");
            }
        }
        catch(YilinkerException $e){
            $this->em->rollback();
            return $e->getMessage();
        }
    }

    public function resetPassword($data, $user, $userType, $storeType)
    {
        $this->em->beginTransaction();

        try{

            $isVerified = $this->oneTimePasswordService->confirmOneTimePassword(
                            $user,
                            $user->getContactNumber(),
                            $data["verificationCode"],
                            OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD,
                            true,
                            null,
                            $user->getCountry()? $user->getCountry()->getCode() : null
                        );

            if($isVerified){

                if(
                    $userType == User::USER_TYPE_BUYER ||
                    ($userType == User::USER_TYPE_SELLER && $storeType == Store::STORE_TYPE_RESELLER)
                ){
                    $userRepository = $this->em->getRepository("YilinkerCoreBundle:User");
                    $userEntries = $userRepository->findBy(array(
                        "contactNumber" => $user->getContactNumber(),
                        "accountId" => $user->getAccountId()
                    ));

                    foreach($userEntries as $entry){
                        $entry->setPlainPassword($data["plainPassword"]);
                    }

                    $user->setPlainPassword($data["plainPassword"]);
                }
                else{
                    $user->setPlainPassword($data["plainPassword"]);
                }

                $this->em->flush();

                $request = $this->jwtManager->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);
                $this->ylaService->setEndpoint(false);
                $this->ylaService->sendRequest("user_update", "post", array("request" => $request));

                $this->em->commit();
                return true;
            }
            else{
                throw new YilinkerException("Please revalidate your contact number.");
            }
        }
        catch(YilinkerException $e){
            $this->em->rollback();
            return false;
        }
    }

    /**
     * Persist new user
     *
     * @param User $user
     * @param bool|true $isSendEmail
     * @param bool|false $isMobileVerified
     * @param bool|false $isEmailVerified
     * @return User
     */
    public function registerUser(
        User $user,
        $isSendEmail = true,
        $isMobileVerified = false,
        $isEmailVerified = false
    ){
        $user->setDateAdded(Carbon::now())
             ->setDateLastModified(Carbon::now())
             ->setGender('M')
             ->setIsActive(true)
             ->setIsMobileVerified($isMobileVerified)
             ->setIsEmailVerified($isEmailVerified)
             ->setLoginCount(0)
             ->setIsBanned(false);

        $this->generateReferralCode($user);
        $this->em->persist($user);

        if(!$user->getSlug(false)){
            $user->setSlug($this->generateUniqueSlug($user));
        }

        $this->em->flush();

        if($isSendEmail){
            $this->sendVerficationEmails($user);
        }

        return $user;
    }

    public function sendVerficationEmails($user)
    {
        $this->verificationService->createVerificationToken($user, $user->getEmail());
        $this->mailerService->sendEmailVerification($user);
    }

    /**
     * Add Referrer
     * @param User $referral
     * @param User $referrer
     * @return UserReferral
     */
    public function addReferrer (User $referral, User $referrer)
    {
        $referralEntity = new UserReferral();
        $referralEntity->setUser($referral);
        $referralEntity->setReferrer($referrer);
        $referralEntity->setDateCreated(Carbon::now());

        $this->em->persist($referralEntity);

        $referral->setUserReferral($referralEntity);
        $referrer->setUserReferral($referralEntity);

        $this->em->flush();

        return $referralEntity;
    }

    /**
     * Generates forgot password token
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return Yilinker\Bundle\CoreBundle\Entity\User
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

    public function generateUniqueSlug(User $user)
    {
        $slug = substr(sha1($user->getUserId().uniqid('yilinker').time()), 0, 20);

        $userRepository = $this->em->getRepository("YilinkerCoreBundle:User");
        while(!$userRepository->isUniqueSlug($slug, null)){
            $slug = $this->generateUniqueSlug($user);
        }

        return $slug;
    }

    /**
     * Verify Email and Mobile
     *
     * @param User $user
     * @return User
     */
    public function verifyAccount (User $user)
    {
        if(!is_null($user->getEmail())){
          $user->setIsEmailVerified(true);
        }

        if(!is_null($user->getContactNumber())){
          $user->setIsMobileVerified(true);
        }

        $this->em->flush();

        return $user;
    }

    /**
     * Generate ReferralCode and persist
     *
     * @param User $user
     * @return String
     */
    public function generateReferralCode (User $user, $letterLength = self::REFERRAL_LETTER_LEN, $numberLength = self::REFERRAL_NUMBER_LEN)
    {
        $referralCode = "";
        $unique = false;

        while (!$unique) {
            $letters = StringHelper::generateRandomString($letterLength, true, false);
            $numbers = StringHelper::generateRandomString($numberLength, false, true);

            $referralCode = $letters . $numbers;
            $findUser = $this->em->getRepository('YilinkerCoreBundle:User')->findOneByReferralCode($referralCode);
            if (!$findUser) {
                $unique = true;
            }
        }

        $user->setReferralCode($referralCode);
        $this->em->flush();

        return $user->getReferralCode();
    }

    /**
     * Validate and add referral code
     *
     * @param $referralCode
     * @param User $referral
     * @return array
     */
    public function processReferralCode ($referralCode, User $referral)
    {
        $storeType = is_null($referral->getStore()) ? null : $referral->getStore()->getStoreType();
        $validationResponse = $this->validateReferrerCode($referralCode, $referral->getUserType(), $storeType, $referral->getUserId());

        if ($validationResponse['isSuccessful'] === true) {
            $userReferral = $this->addReferrer($referral, $validationResponse['data']);
            $this->registrationEarner->get($userReferral->getUser())
                                     ->earn();

            if ($validationResponse['data']->getUserType() == User::USER_TYPE_BUYER) {
                $this->userPointReferralManager
                     ->earn (
                         $validationResponse['data'],
                         $userReferral,
                         UserPoint::REFERRAL_BUYER_TO_BUYER
                     );
            }

            $this->em->flush();
            $validationResponse['data'] = $userReferral;
        }

        return $validationResponse;
    }

    /**
     * Validate referralCode
     *
     * @param $referrerCode
     * @param $referralType
     * @param null $referralStoreType
     * @return array
     */
    public function validateReferrerCode ($referrerCode, $referralType, $referralStoreType = null, $referralUserId = null)
    {
        $sellerType = User::USER_TYPE_SELLER;
        $buyerType = User::USER_TYPE_BUYER;
        $merchantStoreType = Store::STORE_TYPE_MERCHANT;
        $affiliateStoreType = Store::STORE_TYPE_RESELLER;

        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid Referral Code',
            'data'         => null
        );

        if ($referrerCode !== '') {
            $referrerEntity = $this->em->getRepository('YilinkerCoreBundle:User')
                                       ->getUserByReferralCode($referrerCode, $referralUserId);

            if ($referrerEntity instanceof User) {
                $referrerType = $referrerEntity->getUserType();
                $referrerStoreType = is_null($referrerEntity->getStore()) ? null : $referrerEntity->getStore()->getStoreType();
                $canRefer = false;

                if ($referrerType == $buyerType && $referralType == $buyerType) {
                    $canRefer = true;
                }
                else if ($referrerStoreType == $affiliateStoreType && $referralStoreType == $affiliateStoreType) {
                    $canRefer = true;
                }
                else if ($referrerStoreType == $affiliateStoreType && $referralType == $buyerType) {
                    $canRefer = true;
                }
                else if ($referrerType == $sellerType && $referralType == $buyerType) {
                    $canRefer = true;
                }

                if ($canRefer) {
                    $response = array (
                        'isSuccessful' => true,
                        'message'      => 'Successfully Updated!',
                        'data'         => $referrerEntity
                    );
                }
                else {
                    $response = array (
                        'isSuccessful' => false,
                        'message'      => 'Cannot use Referral Code',
                        'data'         => null
                    );
                }

            }

        }

        return $response;
    }

}
