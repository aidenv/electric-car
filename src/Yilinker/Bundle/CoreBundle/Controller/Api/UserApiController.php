<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;
use Yilinker\Bundle\CoreBundle\Entity\EmailNewsletterSubscription;
use Yilinker\Bundle\CoreBundle\Entity\SmsNewsletterSubscription;
use Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Yilinker\Bundle\CoreBundle\Traits\ContactNumberHandler;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Traits\AccessTokenGenerator;
use DateTime;
use StdClass;

class UserApiController extends YilinkerBaseController
{
    use ContactNumberHandler;
    use FormHandler;

    /**
     * Get store information
     *
     * @ApiDoc(
     *     section="Store",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Invalid parameter request",
     *         401="Unauthorized Request",
     *         404="User not found",
     *     },
     *     parameters={
     *         {"name"="userId", "dataType"="integer", "required"=true, "description"="user id"},
     *     },
     * )
     * @param Request $request
     * @return JsonResponse
     */
    public function getStoreInfoAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $entityManager = $this->getDoctrine()->getManager();
        $assetsHelper = $this->container->get('templating.helper.assets');
        $authorizationChecker = $this->get('security.authorization_checker');

        $referrerCode = "";
        $referrerName = "";
        $referralCode = "";

        $userRepository = $entityManager->getRepository("YilinkerCoreBundle:User");
        $user = $userRepository->getOnebyUserOrSlug($request->get('userId', 0))
            ->getOneOrNullResult();

        if(is_null($user)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "User not found.",
                "data" => array()
            ), 404);
        }

        $isFollowed = false;

        if(
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ){
            $userFollowRepository = $entityManager->getRepository("YilinkerCoreBundle:UserFollow");
            $record = $userFollowRepository->findOneBy(array(
                "follower" => $authenticatedUser,
                "followee" => $user
            ));

            if(!is_null($record)){
                $isFollowed = true;
            }

            $userReferrer = $authenticatedUser->getUserReferral();
            if ($userReferrer) {
                $referrerCode = $userReferrer->getReferrer()->getReferralCode();
                $referrerName = $userReferrer->getReferrer()->getFullName();
            }
        }

        $store = $user->getStore();

        $specialty = null;
        $productCategory = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory")->getUserSpecialty($user);

        if(!is_null($productCategory) && $productCategory){
            $specialty = $productCategory->getName();
        }

        $birthdate = null;
        if(!is_null($user->getBirthdate())){
            $birthdate = $user->getBirthdate()->format('M d, Y');
        }

        $image = $user->getPrimaryImage();
        $cover = $user->getPrimaryCoverPhoto();

        $profilePhoto = "";
        $coverPhoto = "";

        if($image){
            $profilePhoto = $assetsHelper->getUrl($image->getImageLocation(), 'user');
        }

        if($cover){
            $coverPhoto = $assetsHelper->getUrl($cover->getImageLocation(), 'user');
        }

        $userAddressService = $this->get('yilinker_core.service.user_address.user_address');

        $data = array(
            "userId"        => $user->getUserId(),
            "fullName"      => $user->getFullName(),
            "firstName"     => $user->getFirstName(),
            "lastName"      => $user->getLastName(),
            "email"         => $user->getEmail(),
            "gender"        => $user->getGender(),
            "nickname"      => $user->getNickname(),
            "contactNumber" => $user->getContactNumber(),
            "specialty"     => $specialty,
            "birthdate"     => $birthdate,
            "storeName"     => $store->getStoreName(),
            "storeDescription" => $store->getStoreDescription(),
            "profilePhoto"  => $profilePhoto,
            "coverPhoto"    => $coverPhoto,
            "isFollowed"    => $isFollowed,
            "storeAddress"  => $userAddressService->getDefaultUserAddress($user),
            "isAffiliate"   => $store->isAffiliate(),
            "tin"           => $user->getTin(),
        );

        if(
            (
                !$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') &&
                !$authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ) ||
            $authenticatedUser->getUserType() == User::USER_TYPE_BUYER
        ){
            $productUnitCollection = $entityManager->getRepository("YilinkerCoreBundle:ProductUnit")
                                                   ->getLatestUploadedProducts($user, Product::ACTIVE, 8, null);

            $products = array();
            foreach($productUnitCollection as $productUnit){
                $product = $productUnit->getProduct();
                $unit = $productUnit->toArray();

                $primaryImage = $unit["primaryImage"];

                if(!is_null($primaryImage) || $primaryImage != ""){
                    $primaryImage = $assetsHelper->getUrl($primaryImage, 'product');
                }

                array_push($products, array(
                    "productId" => $product->getProductId(),
                    "productName" => $product->getName(),
                    "originalPrice" => $unit["price"],
                    "newPrice" => $unit["discountedPrice"],
                    "discount" => $unit["discount"],
                    "promoType" => $unit["promoTypeId"],
                    "promoName" => $unit["promoTypeName"],
                    "imageUrl" => $primaryImage,
                ));
            }

            $data["products"] = $products;
        }

        $referralData = array(
            'referralCode' => (string) $referralCode,
            'referrerCode' => (string) $referrerCode,
            'referrerName' => (string) $referrerName,
        );

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Store info.",
            "data" => array_merge($data, $referralData)
        ), 200);
    }

    public function changePasswordAction(Request $request)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        $oldPassword = $request->request->get("oldPassword", "");
        $newPassword = $request->request->get("newPassword", "");
        $newPasswordConfirm = $request->request->get("newPasswordConfirm", "");
        $postData = array(
            "plainPassword" => array(
                "first" => $newPassword,
                "second" => $newPasswordConfirm
            ),
            "oldPassword" => $oldPassword
        );

        $authenticatedUser = $this->getAuthenticatedUser();

        $passwordEncoder = $this->get('security.encoder_factory')->getEncoder($authenticatedUser);

        $isPasswordValid = $passwordEncoder->isPasswordValid($authenticatedUser->getPassword(), $oldPassword, null);

        if(!$isPasswordValid){
            return $formErrorService->throwCustomErrorResponse(array("Invalid password."), "Incorrect old password.");
        }

        $isOldPassword =  $passwordEncoder->isPasswordValid($authenticatedUser->getPassword(), $newPassword, null);

        if(!$isOldPassword){

            $form = $this->transactForm('core_change_password', null, $postData);

            if($form->isValid()){
                $formData = $form->getData();

                $newPassword = $formData['plainPassword'];

                $entityManager = $this->getDoctrine()->getManager();

                $entityManager->beginTransaction();

                try{

                    $authenticatedUser->setPlainPassword($newPassword);
                    $entityManager->persist($authenticatedUser);
                    $entityManager->flush();

                    $authenticatedUser->setForgotPasswordToken(null)
                                      ->setForgotPasswordTokenExpiration(null)
                                      ->setForgotPasswordCode(null)
                                      ->setForgotPasswordCodeExpiration(null);

                    $entityManager->persist($authenticatedUser);
                    $entityManager->flush();

                    $jwtService = $this->get("yilinker_core.service.jwt_manager");
                    $request = $jwtService->setKey("ylo_secret_key")->encodeUser($authenticatedUser)->encodeToken(null);

                    $ylaService = $this->get("yilinker_core.service.yla_service");
                    $ylaService->setEndpoint(false);

                    $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

                    if(is_array($response) && array_key_exists("isSuccessful", $response) && $response["isSuccessful"]){

                        $entityManager->commit();
                        return new JsonResponse(array(
                            "isSuccessful" => true,
                            "message" => "Successfully changed password",
                            "data" => array()
                        ), 200);
                    }
                    else{
                        throw new YilinkerException("Error Processing Request");
                    }
                }
                catch(YilinkerException $e){

                    $entityManager->rollback();
                    return new JsonResponse(array(
                        "isSuccessful" => false,
                        "message" => "Change password failed.",
                        "data" => array(
                            "errors" => array($e->getMessage())
                        )
                    ), 400);
                }
            }

            $errors = $formErrorService->throwInvalidFields($form);

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Password not match",
                "data" => array(
                    "errors" => $errors
                )
            ), 400);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Password is same to the old password.",
                "data" => array(
                    "errors" => array("Password is same to the old password.")
                )
            ), 400);
        }
    }

    /**
     * Verify the mobile code
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyCodeAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $code = $request->get('code', '');
        $isVerificationSuccessful = $this->get('yilinker_core.service.user.verification')
                                         ->confirmVerificationToken($authenticatedUser, $code, $type = UserVerificationToken::TYPE_CONTACT_NUMBER);

        return new JsonResponse(array(
            'isSuccessful' => $isVerificationSuccessful,
            'data' => array(),
            'message' => $isVerificationSuccessful ? "Mobile successfully verified" : "Code is either invalid or is already expired",
        ), $isVerificationSuccessful? 200:400);
    }

    public function changeEmailAction(Request $request)
    {
        $email = $request->request->get('email', null);

        $entityManager = $this->getDoctrine()->getManager();

        $authenticatedUser = $this->getAuthenticatedUser();

        $userType = $authenticatedUser->getUserType();

        $storeType = null;
        if($userType != User::USER_TYPE_BUYER && $authenticatedUser->getStore()){
            $storeType = $authenticatedUser->getStore()->getStoreType();
        }

        $userRepository = $entityManager->getRepository("YilinkerCoreBundle:User");
        $user = $userRepository->findUserByEmailExcludeId(
                    $email,
                    $authenticatedUser->getUserId(),
                    null,
                    $userType,
                    null,
                    $storeType
        );

        if(!is_null($user)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Email '{$email}' is already taken.",
                "data" => array()
            ), 400);
        }

        if(filter_var($email, FILTER_VALIDATE_EMAIL)){

            $entityManager->beginTransaction();

            try{

                if($authenticatedUser->getEmail() == $email){
                    throw new YilinkerException("New email should not be the same with your present email.");
                }

                $authenticatedUser->setEmail($email);
                $authenticatedUser->setIsEmailVerified(false);

                $verificationService = $this->get("yilinker_core.service.user.verification");
                $mailerService = $this->get("yilinker_core.service.user.mailer");

                $jwtService = $this->get("yilinker_core.service.jwt_manager");
                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($authenticatedUser)->encodeToken(null);

                $ylaService = $this->get("yilinker_core.service.yla_service");
                $ylaService->setEndpoint(false);

                $ylaService->sendRequest("user_update", "post", array("request" => $request));

                $verificationService->createVerificationToken($authenticatedUser, $email);
                $mailerService->sendEmailVerification($authenticatedUser);

                $entityManager->flush();
                $entityManager->commit();

                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Email has been changed.",
                    "data" => array()
                ), 200);
            }
            catch(YilinkerException $e){
                $entityManager->rollback();

                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => $e->getMessage(),
                    "data" => array(
                        "errors" => array($e->getMessage())
                    )
                ), 400);
            }
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Email is not valid.",
                "data" => array()
            ), 400);
        }
    }

    public function changeContactV1NumberAction(Request $request)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $authenticatedUser = $this->getAuthenticatedUser();
        $entityManager = $this->getDoctrine()->getManager();

        $oldContactNumber = $request->request->get('oldContactNumber');

        if($authenticatedUser->getContactNumber() != $oldContactNumber){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Old contact number does not match.",
                "data" => array(
                    "errors" => array("Old contact number does not match.")
                )
            ), 400);
        }

        $countryRepository = $entityManager->getRepository("YilinkerCoreBundle:Country");
        $areaCode = $request->get("areaCode", "63");
        $country = $countryRepository->findOneByAreaCode($areaCode);

        if(is_null($country)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "SMS support is not available in your country.",
                "data" => array(
                    "errors" => array("SMS support is not available in your country.")
                )
            ), 400);
        }

        $newContactNumber = $this->formatContactNumber($country->getCode(), $request->get('newContactNumber'));

        $form = $this->transactForm('core_change_contact_number', null, array(
            "contactNumber" => $newContactNumber,
        ), array(
            "userId"        => $authenticatedUser->getUserId(),
            "userType"      => $authenticatedUser->getUserType(),
            "storeType"     => $authenticatedUser->getStore()? $authenticatedUser->getStore()->getStoreType() : null
        ));

        if($form->isValid()){
            $oneTimePasswordService = $this->get('yilinker_core.service.sms.one_time_password');
            $oneTimePassword = $oneTimePasswordService->sendOneTimePassword(
                                $authenticatedUser,
                                $country,
                                $newContactNumber,
                                OneTimePasswordService::OTP_TYPE_CHANGE_CONTACT_NUMBER
                            );

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Verification code sent to ".$newContactNumber,
                "data" => array(
                    "expiration_in_minutes" => OneTimePasswordService::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                )
            ), 200);
        }

        $errors = $formErrorService->throwInvalidFields($form);

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Invalid contact number",
            "data" => array(
                "errors" => $errors
            )
        ), 400);
    }

    /**
     * Send verification code to a user's mobile
     *
     * @param Request|Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function requestVerificationCodeAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $response = $this->get('yilinker_core.service.sms.sms_service')
                         ->sendUserVerificationCode($authenticatedUser);

        return new JsonResponse(array(
            'isSuccessful' => $response['isSuccessful'],
            'message'      => $response['message'],
            'data'         => array(),
        ));
    }

    public function activityLogAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $user = $this->getUser();
        $page = $request->get('page');
        $perPage = $request->get('perPage', 10);

        $tbUserActivity = $em->getRepository('YilinkerCoreBundle:UserActivityHistory');
        $logService = $this->get('yilinker_core.service.log.user.activity');
        $activities = $logService->getActivitiesOfUser($user->getUserId(), $page, $perPage, true);
        $count = $tbUserActivity->countActivities($user->getUserId());
        $data = array(
            'isSuccessful' => true,
            'data'         => compact('activities', 'count'),
            'message'      => ''
        );

        return new JsonResponse($data);
    }

    /**
     * Disables the authenicated user
     *
     * @param Symfony\Component\HttpFoundation\Request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function disableUserAction(Request $request)
    {
        $responseData = array(
            'data' => array(),
            'message' => 'Invalid password provided.',
            'isSuccessful' => false,
        );

        $password = $request->get('password', null);
        $authenticatedUser = $this->getAuthenticatedUser();

        $isPasswordValid = $this->get('security.encoder_factory')
                                ->getEncoder($authenticatedUser)
                                ->isPasswordValid($authenticatedUser->getPassword(), $password, null);
        
        if ($authenticatedUser->getEmail() && !$authenticatedUser->getIsEmailVerified()) {
               return new JsonResponse(array(
                'data' => array(),
                'message' => 'Verify Email First to Deactivate.',
                'isSuccessful' => false,
            ));  
        }

        if($isPasswordValid){
            $this->container->get('yilinker_core.service.account_manager')
                 ->disableAccount($authenticatedUser);
            $responseData['isSuccessful'] = true;
            $responseData['message'] = "Account successfully disabled";
            $responseData['data'] = array(
                "userType" => $authenticatedUser->getUserType(),
                "storeType" => !is_null($authenticatedUser->getStore())? $authenticatedUser->getStore()->getStoreType() : null
            );

            $response = new JsonResponse($responseData);
            return $this->unauthenticateUser($request, $response);
        }

        return new JsonResponse($responseData);
    }

    /**
     * Subscribe the user to the email newsletter
     */
    public function subscribeUserEmailAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getEntityManager();
        $isSubscribe = $request->get('isSubscribe', 'true');
        $isSubscribe = trim($isSubscribe) === 'true';
        $authenticatedUser = $this->getAuthenticatedUser();

        $subscribedEmail = $em->getRepository('YilinkerCoreBundle:EmailNewsletterSubscription')
                              ->findOneBy(array(
                                  'email'  => $authenticatedUser->getEmail(),
                                  'userId' => $authenticatedUser->getUserId(),
                              ));

        if($isSubscribe){
            if($subscribedEmail === null){
                $newSubscribedEmail = new EmailNewsletterSubscription();
                $newSubscribedEmail->setEmail($authenticatedUser->getEmail());
                $newSubscribedEmail->setUserId($authenticatedUser->getUserId());
                $newSubscribedEmail->setDateCreated(new DateTime());
                $newSubscribedEmail->setDateLastModified(new DateTime());
                $em->persist($newSubscribedEmail);
                $response['isSuccessful'] = true;
            }
            else{
                $response['message'] = 'Email is already subscribed';
                if($subscribedEmail->getIsActive() === false){
                    $subscribedEmail->setDateLastModified(new DateTime());
                    $subscribedEmail->setIsActive(true);
                    $response['isSuccessful'] = true;
                }
            }
        }
        else{
            $response['message'] = 'Email is not subscribed';
            if($subscribedEmail && $subscribedEmail->getIsActive()){
                $subscribedEmail->setDateLastModified(new DateTime());
                $subscribedEmail->setIsActive(false);
                $response['isSuccessful'] = true;
            }
        }

        if($response['isSuccessful']){
            $em->flush();
            $response['message'] = 'Email subscription changed';
        }

        return new JsonResponse($response);
    }

    /**
     * Subscribe the user to the sms newsletter
     */
    public function subscribeUserSmsAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getEntityManager();
        $isSubscribe = $request->get('isSubscribe', 'true');
        $isSubscribe = trim($isSubscribe) === 'true';
        $authenticatedUser = $this->getAuthenticatedUser();

        $subscribedSms = $em->getRepository('YilinkerCoreBundle:SmsNewsletterSubscription')
                              ->findOneBy(array(
                                  'contactNumber' => $authenticatedUser->getContactNumber(),
                                  'userId'        => $authenticatedUser->getUserId(),
                              ));

        if($isSubscribe){
            if($subscribedSms === null){
                $newSubscribedSms = new SmsNewsletterSubscription();
                $newSubscribedSms->setContactNumber($authenticatedUser->getContactNumber());
                $newSubscribedSms->setUserId($authenticatedUser->getUserId());
                $newSubscribedSms->setDateCreated(new DateTime());
                $newSubscribedSms->setDateLastModified(new DateTime());
                $em->persist($newSubscribedSms);
                $response['isSuccessful'] = true;
            }
            else{
                $response['message'] = 'Mobile number is already subscribed';
                if($subscribedSms->getIsActive() === false){
                    $subscribedSms->setDateLastModified(new DateTime());
                    $subscribedSms->setIsActive(true);
                    $response['isSuccessful'] = true;
                }
            }
        }
        else{
            $response['message'] = 'Mobile number is not subscribed';
            if($subscribedSms && $subscribedSms->getIsActive()){
                $subscribedSms->setDateLastModified(new DateTime());
                $subscribedSms->setIsActive(false);
                $response['isSuccessful'] = true;
            }
        }

        if($response['isSuccessful']){
            $em->flush();
            $response['message'] = 'Mobile number subscription changed';
        }

        return new JsonResponse($response);
    }

    public function updateBasicInfoAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->jsonResponse['message'] = 'You are not logged in';
        }

        $postData = array(
            "firstName" => $request->get('firstName', null),
            "lastName" => $request->get('lastName', null),
            "contactNumber" => $request->get('contactNo', null),
            "email" => $request->get('email', null),
            "confirmationCode" => $request->get('confirmationCode', null),
        );

        $errors = array();
        $form = $this->transactForm("api_core_update_basic_info", null, $postData, array(
                    "csrf_protection" => false,
                    "excludeUserId" => $user->getUserId(),
                    "userType" => User::USER_TYPE_BUYER,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "contactNumber" => $postData["contactNumber"],
                    "type" => OneTimePasswordService::OTP_TYPE_CHECKOUT,
                    "user" => $user,
                    "mustVerify" => $user->getIsMobileVerified()? false : true,
                    "hasEmail" => !is_null($postData["email"])? true : false,
                    "areaCode" => "63"
                ));


        $translationService = $this->get("yilinker_core.translatable.listener");
        if($form->isValid()){

            $data = $form->getData();

            $em = $this->getDoctrine()->getManager();

            $user->setFirstName($data["firstName"])
                 ->setLastName($data["lastName"]);

            !is_null($postData["email"])? $user->setEmail($data["email"]) : null;

            if(!$user->getIsMobileVerified()){

                $user->setContactNumber($this->formatContactNumber(
                    strtoupper($translationService->getCountry()),
                    $data["contactNumber"])
                );
        
                $user->setIsMobileVerified(true);

                $oneTimePasswordService = $this->get('yilinker_core.service.sms.one_time_password');
                $oneTimePasswordService->confirmOneTimePassword(
                    $user,
                    $data["contactNumber"],
                    $data["confirmationCode"],
                    OneTimePasswordService::OTP_TYPE_CHECKOUT,
                    true
                );
            }

            $em->flush();
        }
        else{
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $errors = $formErrorService->throwInvalidFields($form);
        }

        if (empty($errors)) {
            $this->jsonResponse['isSuccessful'] = true;
            $this->jsonResponse['message'] = 'User Information successfully changed';
            $this->jsonResponse['data'] = new StdClass();
        }
        else {
            $this->jsonResponse['message'] = implode("\n", $errors);
            $this->jsonResponse['data']['errors'] = $errors;
        }

        return $this->jsonResponse();
    }

    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }

}
