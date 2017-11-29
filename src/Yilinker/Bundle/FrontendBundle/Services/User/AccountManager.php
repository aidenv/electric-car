<?php

namespace Yilinker\Bundle\FrontendBundle\Services\User;

use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\ContactNumber;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\UserReferral;
use Yilinker\Bundle\CoreBundle\Entity\UserIdentificationCard;
use Yilinker\Bundle\CoreBundle\Services\SMS\Senders\SemaphoreSms;
use Yilinker\Bundle\CoreBundle\Services\SMS\SmsService;
use Yilinker\Bundle\CoreBundle\Services\Upload\UploadService;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Services\Mailer\Mailer;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\CoreBundle\Services\Yilinker\Account;
use Yilinker\Bundle\CoreBundle\Services\Jwt\JwtManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\File;

class AccountManager
{
    /**
     * @var Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\User\Verification
     */
    private $verificationService;

    /**
     * Yilinker\Bundle\FrontendBundle\Services\User\Mailer
     */
    private $mailerService;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\Upload\UploadService
     */
    private $uploadService;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\SMS\SmsService
     */
    private $smsService;

    /**
     * @var Symfony\Component\Form\FormFactory 
     */
    private $formFactory;

    private $jwtManager;

    private $ylaService;

    private $filesystem;

    private $imageManipulator;

    /**
     * @param Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager $entityManager
     * @param Yilinker\Bundle\FrontendBundle\Services\User\Verification $verificationService
     * @param Yilinker\Bundle\CoreBundle\Services\Mailer\Mailer $mailerService
     * @param Yilinker\Bundle\CoreBundle\Services\Upload\UploadService $uploadService
     * @param Yilinker\Bundle\CoreBundle\Services\SMS\SmsService $smsService
     * @param Symfony\Component\Form\FormFactory $formFactory
     */
    public function __construct(
        EntityManager $entityManager,
        Verification $verificationService,
        Mailer $mailerService,
        UploadService $uploadService,
        SmsService $smsService,
        Account $ylaService,
        JwtManager $jwtManager,
        FormFactory $formFactory,
        $filesystem,
        $imageManipulator
    )
    {
        $this->em = $entityManager;
        $this->mailerService = $mailerService;
        $this->verificationService = $verificationService;
        $this->uploadService = $uploadService;
        $this->smsService = $smsService;
        $this->jwtManager = $jwtManager;
        $this->ylaService = $ylaService;
        $this->formFactory = $formFactory;
        $this->filesystem = $filesystem;
        $this->imageManipulator = $imageManipulator;
    }

    /**
     * Update user info
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param mixed $data
     * @param int $userAddressId
     * @param int $locationId
     * @param string $slug
     * @return JsonResponse
     */
    public function updateUserInfo(User $user, $data, $userAddressId, $locationId, $slug)
    {
        $entityManager = $this->em;

        $message = array();

        $entityManager->getConnection()->beginTransaction();
        $coverPhoto = null;
        $profilePhoto = null;
        $errors = array();

        try{
            //update basic info of user table
            if(
                array_key_exists('firstName', $data) OR
                array_key_exists('lastName', $data) OR
                array_key_exists('gender', $data) OR
                array_key_exists('birthdate', $data) OR
                array_key_exists('nickname', $data)
            ){

                $this->updateUserEntity($user, $data);
                $user->setDateLastModified(Carbon::now());
            }

            //update photos
            $this->uploadUserPhoto("profilePhoto", $data, $user, $errors);
            $this->uploadUserPhoto("coverPhoto", $data, $user, $errors);

            //check if same contact number
            if(array_key_exists('contactNumber', $data)){
                $updateContactResponse = $this->smsService->sendUserVerificationCode($user, $data["contactNumber"]);
                if($updateContactResponse['isSuccessful']){
                    array_push($errors, $updateContactResponse['message']);
                }
            }

            //change password
            if(array_key_exists('plainPassword', $data)){
                $user->setPlainPassword($data["plainPassword"]["first"]);
            }

            //check if unique slug
            if(array_key_exists('slug', $data)){
                $user->setSlug($data["slug"]);
                $user->setSlugChanged(true);
            }
            
            if(array_key_exists('userDocument', $data)){
                $document = new UserIdentificationCard();
                $document->setFile($data['userDocument']);
                $document->setDateAdded(new \DateTime());
                $document->setUser($user);
                $this->em->persist($document);
            }

            if(!empty($errors)){
                throw new Exception("Invalid transaction.");
            }
           
            if(array_key_exists('plainPassword', $data)){
                $newPassword = $data["plainPassword"]["first"];
                $newPasswordConfirm = $data["plainPassword"]["second"];
                $oldPassword = $data["oldPassword"];

                $this->ylaService->setEndpoint(null);

                $accessToken = $this->ylaService->getClientToken();

                $response = $this->ylaService->sendRequest(
                    array(
                        "route" => "change_password",
                        "params" => array(
                            "access_token" => $accessToken
                        ),
                    ), 
                    "post", 
                    array(
                        "oldPassword" => $oldPassword,
                        "newPassword" => $newPassword,
                        "newPasswordConfirm" => $newPasswordConfirm,
                        "userId" => $user->getAccountId()
                    )
                );
            }

            //updae country 
            if(array_key_exists('countryId', $data)){
                $user->setCountry($entityManager->getRepository('YilinkerCoreBundle:Country')->find($data['countryId']));
            }
            //and language preference
            if(array_key_exists('languageId', $data)){
                $user->setLanguage($entityManager->getRepository('YilinkerCoreBundle:Language')->find($data['languageId']));
            }

            $entityManager->persist($user);
            $request = $this->jwtManager->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

            $this->ylaService->setEndpoint(false);

            $response = $this->ylaService->sendRequest("user_update", "post", array("request" => $request));

            $entityManager->flush();
            $entityManager->getConnection()->commit();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Info successfully updated.",
                "data" => $message
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

    private function uploadUserPhoto($type, $data, &$user, &$errors)
    {
        if(array_key_exists($type, $data)){
            if(is_null($data[$type])){
                array_push($errors, "Failed to upload photo.");
            }else{
                $this->uploadService->setType("user", $user->getUserId());
                $file = $this->uploadService->uploadFile($data[$type]);
                if(!$file){
                    array_push($errors, "Failed to upload photo.");
                }
                else{
                    if($type == "profilePhoto"){
                        $photo = $this->instantiateUserPhoto($user, $file, true, false, UserImage::IMAGE_TYPE_AVATAR);
                    }
                    else{
                        $photo = $this->instantiateUserPhoto($user, $file, true, false, UserImage::IMAGE_TYPE_BANNER);
                    }

                    $this->em->persist($photo);
                }
            }
        }
    }

    private function updateUserEntity(User &$user, $data)
    {
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
    }

    private function assignUserAddressValues(User $user, UserAddress &$address, $data, $makeDefault = false){

        $address->setUser($user);
        if(array_key_exists('title', $data)){
            $address->setUnitNumber($data["title"]);
        }

        if(array_key_exists('unitNumber', $data)){
            $address->setUnitNumber($data["unitNumber"]);
        }

        if(array_key_exists('buildingName', $data)){
            $address->setBuildingName($data["buildingName"]);
        }

        if(array_key_exists('streetNumber', $data)){
            $address->setStreetNumber($data["streetNumber"]);
        }

        if(array_key_exists('streetName', $data)){
            $address->setStreetName($data["streetName"]);
        }

        if(array_key_exists('subdivision', $data)){
            $address->setSubdivision($data["subdivision"]);
        }

        if(array_key_exists('zipCode', $data)){
            $address->setZipCode($data["zipCode"]);
        }

        if(array_key_exists('streetAddress', $data)){
            $address->setStreetAddress($data["streetAddress"]);
        }

        if(array_key_exists('longitude', $data)){
            $address->setLongitude($data["longitude"]);
        }

        if(array_key_exists('latitude', $data)){
            $address->setLatitude($data["latitude"]);
        }

        if(array_key_exists('landline', $data)){
            $address->setLandline($data["landline"]);
        }

        if($makeDefault){
            $address->setIsDefault(true);
        }
    }

    /**
     * Create image object
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param string $file
     * @param boolean $isPrimary
     * @param boolean $isHidden
     * @param integer $imageType
     * @return Yilinker\Bundle\CoreBundle\Entity\UserImage
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

    /**
     * Update user address
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param int $regionId
     * @param int $cityId
     * @param string $streetAddress
     * @param string $latitude
     * @param string $longitude
     *
     * @return mixed
     */
    public function updateUserPermanentAddress($user, $regionId, $cityId, $streetAddress, $latitude = null, $longitude = null)
    {
        $result = array(
            'isSuccessful' => false,
            'message' => '',
            'address' => null,
        );
        $region = $this->em
                        ->getRepository('YilinkerCoreBundle:Location')
                        ->find($regionId);

        $city = $this->em
                     ->getRepository('YilinkerCoreBundle:Location')
                     ->find($cityId);

        if($region === null){
            $result['message'] = 'Region location is invalid';
        }
        else if($city === null){
            $result['message'] = 'City location is invalid';
        }
        else{
            $permanentAddress = $this->em->getRepository('YilinkerCoreBundle:UserAddress')
                                     ->getUserDefaultAddress($user->getUserId());
            if($permanentAddress){
                $permanentAddress->setRegion($region);
                $permanentAddress->setCity($city);
                $permanentAddress->setStreetAddress($streetAddress);
            }
            else{
                $permanentAddress = new UserAddress();
                $permanentAddress->setUser($user);
                $permanentAddress->setRegion($region);
                $permanentAddress->setCity($city);
                $permanentAddress->setStreetAddress($streetAddress);
                $permanentAddress->setIsPermanentAddress(true);
                $permanentAddress->setDateAdded(new \DateTime('now'));
                $this->em->persist($permanentAddress);
            }

            if($latitude !== null){
                $permanentAddress->setLatitude($latitude);
            }
            if($longitude !== null){
                $permanentAddress->setLongitude($longitude);
            }
            
            $result['address'] = $permanentAddress;
            $result['isSuccessful'] = true;
            $this->em->flush();
        }

        return $result;
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
        $referralEntity->setReferralId($referral);
        $referralEntity->setReferrerId($referrer);
        $referralEntity->setDateCreated(Carbon::now());
        $this->em->persist($referralEntity);
        $this->em->flush();

        return $referralEntity;
    }

    /**
     * Update the user email
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param string $email
     * @return Yilinker\Bundle\CoreBundle\Entity\User $user
     */
    public function updateUserEmail(User $user, $email, $sendEmail = false)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => ''
        );

        $form = $this->formFactory->createBuilder('form', null, array('csrf_protection' => false))
                     ->setMethod('POST')
                     ->add('email', 'text', array('constraints' => array(
                         new Email(),
                         new NotNull(),
                         new NotBlank(),
                         new UniqueEmail(array(
                             'excludeUserId' => $user->getUserId(),
                             'userType'      => $user->getUserType(),
                         )),
                     )))
                     ->getForm();
        
        $form->submit([
            'email' => $email,
        ]);

        if($form->isValid()){
            $user->setEmail($email);
            $user->setIsEmailVerified(false);
            if($user && $sendEmail){
                $this->verificationService->createVerificationToken($user, $email);
                $this->mailerService->sendEmailVerification($user);
            }
            
            $response['isSuccessful'] = true;
        }
        else{

            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }
        
        return $response;
    }

}
