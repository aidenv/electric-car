<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Carbon\Carbon;
use Exception;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Yilinker\Bundle\CoreBundle\Traits\ContactNumberHandler;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;

/**
 * Class UserController
 * @package Yilinker\Bundle\FrontendBundle\Controller
 */
class UserController extends Controller
{
    use ContactNumberHandler;

    /**
     * Register page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            !$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $storeType = $request->get("storeType", Store::STORE_TYPE_RESELLER);

            $kernel = $this->get("kernel")->getName();

            switch ($kernel) {
                case 'frontend':
                    $userType = User::USER_TYPE_BUYER;
                    break;

                default:
                    $userType = User::USER_TYPE_SELLER;
                    break;
            }

            $formData = array(
                "plainPassword" => array(
                    "first" => $request->get("password", null),
                    "second" => $request->get("confirmPassword", null)
                ),
                "contactNumber" => $request->get("contactNumber", null),
                "verificationCode" => $request->get("verificationCode", null),
                "areaCode" => $request->get("areaCode", "63"),
                "_token" => $request->get("token", null),
                "referralCode" => $request->get('referralCode', '')
            );
            $formData['language'] = $request->get('languageId');

            $form = $this->createForm('core_user_add', null, array(
                "storeType" => $storeType,
                "mustVerify" => true,
                "contactNumber" => $formData["contactNumber"],
                "token" => $formData["verificationCode"],
                "user" => null,
                "type" => OneTimePasswordService::OTP_TYPE_REGISTER,
                "areaCode" => $formData["areaCode"],
                "userType" => $userType
            ));

            $form->submit($formData);

            if ($form->isValid()) {

                $data = $form->getData();
                $accountManager = $this->get("yilinker_core.service.account_manager");
                $isRegistered = $accountManager->mapUser($data, $userType, $storeType);

                if($isRegistered){
                    return new JsonResponse(array(
                        "isSuccessful" => true,
                        "message" => "Successfully registered!",
                        "data" => array()
                    ), 200);
                }
                else{
                    return new JsonResponse(array(
                        "isSuccessful" => false,
                        "message" => "An error occured.",
                        "data" => array(
                            "errors" => array($isRegistered)
                        )
                    ), 400);
                }
            }

            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $errors = $formErrorService->throwInvalidFields($form);

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "An error occured.",
                "data" => array(
                    "errors" => $errors
                )
            ), 400);
        }
    }

    public function sendOneTimePasswordAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => "",
            'data'         => array(),
        );

        $user = $this->getUser();
        $type = $request->get('type');
        if (!$type) {
            $response['message'] = "Token type is required";
        }
        else{
            $otp = $this->get('yilinker_core.service.sms.one_time_password');
            $em = $this->get('doctrine')->getManager();
            $countryRepository = $em->getRepository("YilinkerCoreBundle:Country");
            $contactNumber = $request->get('contactNumber');
            $areaCode = $request->get("areaCode", "63");
            $country = $countryRepository->findOneBy(array(
                "areaCode" => $areaCode
            ));
            try {
                $sent = $otp->sendOneTimePassword($user, $country, $contactNumber, $type, true);
                if ($sent) {
                    $dateNow = Carbon::now();
                    $response['isSuccessful'] = true;
                    $response['data']['expiresOn'] = $dateNow->addMinutes(OneTimePasswordService::VERIFICATION_CODE_EXPIRATION_IN_MINUTES);
                }
                else {
                    $response['message'] = "OTP sending failed";
                }
            } catch (YilinkerException $e) {
                return new Response($e->getMessage(), 500);
            }
        }

        return new JsonResponse($response);
    }

    public function resendMobileVerificationAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if(
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->getAuthenticatedUser();

            $smsResponse = $this->get('yilinker_core.service.sms.one_time_password')
                                ->sendOneTimePassword(
                                    $authenticatedUser,
                                    $authenticatedUser->getCountry(),
                                    $authenticatedUser->getContactNumber(),
                                    OneTimePasswordService::OTP_TYPE_VERIFY_CONTACT_NUMBER
                                );

            if ($smsResponse) {
                $response = array(
                    "message" => "Verification code sent.",
                    "isSuccessful" => true
                );

                $response["data"]["expiration_in_minutes"] = Verification::VERIFICATION_CODE_EXPIRATION_IN_MINUTES;

                return new JsonResponse($response, 200);
            }
        }

        return new JsonResponse(array(
            'isSuccessful' => false
        ), 403);
    }

    public function resendEmailVerificationAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if(
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->getAuthenticatedUser();

            if(!$authenticatedUser->getIsEmailVerified()){
                $verificationService = $this->get("yilinker_core.service.user.verification");
                $mailerService = $this->get("yilinker_core.service.user.mailer");

                $verificationService->createVerificationToken($authenticatedUser, $authenticatedUser->getEmail());
                $mailerService->sendEmailVerification($authenticatedUser);

                return new JsonResponse(array(
                    'isSuccessful' => true,
                ), 200);
            }
        }

        return new JsonResponse(array(
            'isSuccessful' => false
        ), 403);
    }

    public function changeContactNumberRequestAction(Request $request)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $authenticatedUser = $this->getAuthenticatedUser();
        $entityManager = $this->getDoctrine()->getManager();

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
            "storeType"     => $request->get("storeType", Store::STORE_TYPE_RESELLER)
        ));

        if($form->isValid()){
            $oneTimePasswordService = $this->get('yilinker_core.service.sms.one_time_password');
            $oneTimePassword = $oneTimePasswordService->sendOneTimePassword(
                                $authenticatedUser,
                                $country,
                                $newContactNumber,
                                OneTimePasswordService::OTP_TYPE_CHANGE_CONTACT_NUMBER
                            );

            if($oneTimePassword instanceof OneTimePassword){
                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Verification code sent to ".$newContactNumber,
                    "data" => array(
                        "expiration_in_minutes" => OneTimePasswordService::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                    )
                ), 200);
            }
            else{
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "You can only send a confirmation once per minute.",
                    "data" => array(
                        "errors" => array($oneTimePassword)
                    )
                ), 400);
            }
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

    public function forgotPasswordCodeCheckAction(Request $request)
    {
        $session = $this->get("session")->get(OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD);
        $user = $this->getDoctrine()
                     ->getManager()
                     ->getRepository("YilinkerCoreBundle:User")
                     ->find(array_key_exists("user", $session)? $session["user"]:null);

        $postData = $request->get("user_forgot_password_code");

        $options = array(
            "mustVerify"    => true,
            "token"         => isset($postData["code"])? $postData["code"]:null,
            "user"          => !is_null($user)? $user : null,
            "contactNumber" => !is_null($user)? $user->getContactNumber() : null,
            "areaCode"      => !is_null($user) && !is_null($user->getCountry()) ? $user->getCountry()->getAreaCode() : null,
        );

        $postData = array(
            "code" => isset($postData["code"])? $postData["code"]:null,
            "_token" => isset($postData["_token"])? $postData["_token"]:null
        );

        $form = $this->transactForm('user_forgot_password_code', null, $postData, $options);

        if(!$form->isValid()){
            $formErrorsService = $this->get('yilinker_core.service.form.form_error');

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Forgot password failed.",
                "data" => array(
                    "errors" => $formErrorsService->throwInvalidFields($form)
                )
            ), 400);
        }

        $accountManagerService = $this->get('yilinker_core.service.account_manager');
        $accountManagerService->generateForgotPasswordToken($user);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Successful request.",
            "data" => array(
                "token" => $user->getForgotPasswordToken()
            )
        ), 200);

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Request failed.",
            "data" => array(
                "errors" => array("The code you entered is invalid")
            )
        ), 400);
    }

    /**
     * Forgot password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPasswordAction(Request $request)
    {
        $userType = null;
        $kernel = $this->get("kernel");

        if ($kernel->getName() == 'frontend') {
            $userType = User::USER_TYPE_BUYER;
        }
        elseif ($kernel->getName() == 'merchant') {
            $userType = User::USER_TYPE_SELLER;
        }

        $storeType = $request->get("storeType", Store::STORE_TYPE_RESELLER);
        $areaCode = $request->get("areaCode", "63");
        $captchaType = $request->get('captchaType', 'grecaptcha');

        $postData = array(
            "request" => $request->get("request", null),
            $captchaType => $request->get($captchaType, null),
            "_token" => $request->get("token", null)
        );

        $form = $this->transactForm('user_forgot_password', null, $postData);

        if (!$form->isValid()) {
            $formErrorsService = $this->get('yilinker_core.service.form.form_error');
            $errorData = array(
                "errors" => $formErrorsService->throwInvalidFields($form)
            );
            if ($captchaType == 'captcha') {
                $errorData["code"] = $form->get('captcha')->createView()->vars['captcha_code'];
            }

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid inputs.",
                "data" => $errorData
            ), 400);
        }

        $secret = $this->getParameter("grecaptcha_merchant_secret");

        $data = $form->getData();
        $reCaptcha = new ReCaptcha($secret);

        if (array_key_exists('grecaptcha', $data)) {
            $response = $reCaptcha->verify($data["grecaptcha"]);
            if (!$response->isSuccess()) {
                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Request failed.",
                    "data" => array(
                        "errors" => array("Forgot password failed.")
                    )
                ), 400);
            }
        }

        $entityManager = $this->get('doctrine')->getManager();

        $country = $entityManager->getRepository("YilinkerCoreBundle:Country")
                                 ->findOneBy(array(
                                    "areaCode" => $areaCode
                    ));


        if(preg_match('/^\d+$/', $data["request"])){
            $data["request"] = $this->formatContactNumber($country->getCode(), $data["request"]);
        }

        if(is_null($country)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "SMS support is not available in your country.",
                "data" => array(
                    "errors" => array("SMS support is not available in your country.")
                )
            ), 400);
        }

        $user = $entityManager->getRepository('YilinkerCoreBundle:User')
                              ->loadUserByContactOrEmail(
                                $data["request"],
                                $userType == User::USER_TYPE_SELLER,
                                $storeType == Store::STORE_TYPE_RESELLER
                              );

        if (is_null($user)) {
            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Requested user not found.",
                "data" => array(
                    "errors" => array("Requested user not found.")
                )
            ), 400);
        }

        $type = null;
        $isSent = false;
        if ($user->getEmail() === $data["request"]) {
            $type = "email";
            $accountManagerService = $this->get('yilinker_core.service.account_manager');
            $accountManagerService->generateForgotPasswordToken($user);

            $mailer = $this->get('yilinker_core.service.user.mailer');

            $isSent = $mailer->sendForgotPassword($user);
        }
        else {
            $type = "contact";
            $this->get('session')->set(OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD, array(
                "expiration"    => Carbon::now()->addMinutes(OneTimePasswordService::VERIFICATION_CODE_EXPIRATION_IN_MINUTES)->getTimestamp(),
                "storeType"     => $storeType,
                "user"          => $user->getUserId()
            ));

            $oneTimePasswordService = $this->get('yilinker_core.service.sms.one_time_password');
            $oneTimePassword = $oneTimePasswordService->sendOneTimePassword(
                                $user,
                                $country,
                                $data["request"],
                                OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD
                            );

            $isSent = true;
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Request sent.",
            "data" => array(
                "type" => $type,
                "isSent" => $isSent
            )
        ), 200);
    }

    public function sendEmailVerificationAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if(
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->getAuthenticatedUser();

            if(!$authenticatedUser->getIsEmailVerified()){
                $verificationService = $this->get("yilinker_core.service.user.verification");
                $mailerService = $this->get("yilinker_core.service.user.mailer");

                $verificationService->createVerificationToken($authenticatedUser, $authenticatedUser->getEmail());
                $mailerService->sendEmailVerification($authenticatedUser);

                $this->get('session')->getFlashBag()->add('verificationEmailSent', 'true');
                return $this->redirect($this->generateUrl(
                    $authenticatedUser->getUserType() == User::USER_TYPE_BUYER? "profile_information" : "merchant_account_information")
                );
            }
        }

        return $this->render('YilinkerCoreBundle:Error:404.html.twig');
    }

    /**
     * Confirms the email of the user
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function confirmEmailAction(Request $request)
    {
        $entityManager = $this->get('doctrine')->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();

        $token = $request->query->get('tk');

        $verificationToken = $entityManager->getRepository('YilinkerCoreBundle:UserVerificationToken')
                                           ->findOneBy(array(
                                               'token'    => $token,
                                           ));
        $tokenOwner = $verificationToken ? $verificationToken->getUser() : null;

        if(!is_null($token) && !is_null($tokenOwner)){
            $entityManager->beginTransaction();

            try{

                if(!$verificationToken->getIsActive()){
                    throw new Exception('Email token not valid.');
                }

                $verificationService = $this->get('yilinker_core.service.user.verification');

                $isValid = $verificationService->confirmVerificationToken($tokenOwner, $token);

                if(!$isValid){
                    throw new Exception('Email token not found');
                }

                $jwtService = $this->get("yilinker_core.service.jwt_manager");
                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($tokenOwner)->encodeToken(null);

                $ylaService = $this->get("yilinker_core.service.yla_service");
                $ylaService->setEndpoint(false);

                $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

                $entityManager->commit();
                $tokenStorage->setToken(null);
                $this->get('session')->getFlashBag()->add('verifyEmail', 'true');

                if($tokenOwner->getUserType() == User::USER_TYPE_BUYER){

                    $link = $this->generateUrl("user_buyer_login");
                }
                elseif(
                    $tokenOwner->getUserType() == User::USER_TYPE_SELLER &&
                    !is_null($tokenOwner->getStore()) &&
                    $tokenOwner->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER
                ){
                    $link = $this->generateUrl("user_affiliate_login");
                }
                else{
                    $link = $this->generateUrl("default");
                }

                if($tokenOwner->getUserType() == User::USER_TYPE_BUYER){
                    $mailer = $this->get('yilinker_core.service.user.mailer');
                    $mailer->sendSuccessVerification($tokenOwner);
                }

                return $this->redirect($link);
            }
            catch(Exception $e){

                $entityManager->rollback();
                $redirectUrl = $this->generateUrl('home_page');

                $verificationErrors = array(
                    "message" => "Unfortunately, this link has already expired.",
                    "buttonMessage" => "Request for a new verification email."
                );

                try {
                    if($tokenOwner == $authenticatedUser){
                        $redirectUrl = $this->generateUrl("core_send_email_verification");
                    }
                    else{

                        $tokenStorage->setToken(null);

                        if(!is_null($tokenOwner) && $tokenOwner->getUserType() == User::USER_TYPE_BUYER){
                            $redirectUrl = $this->generateUrl("user_buyer_login");
                        }
                        elseif(
                            !is_null($tokenOwner) &&
                            $tokenOwner->getUserType() == User::USER_TYPE_SELLER &&
                            !is_null($tokenOwner->getStore()) &&
                            $tokenOwner->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER
                        ){
                            $redirectUrl = $this->generateUrl("user_affiliate_login");
                        }
                        else{
                            $redirectUrl = $this->generateUrl("default");
                        }
                    }

                    // TODO : page for not available token
                    return $this->render('YilinkerCoreBundle:Error:404.html.twig', compact("verificationErrors", "redirectUrl"));
                }
                catch(Exception $e){
                    return $this->render('YilinkerCoreBundle:Error:404.html.twig');
                }
            }
        }
        else{
            // TODO : page for not available token
            return $this->render('YilinkerCoreBundle:Error:404.html.twig');
        }
    }

    public function uploadPhotoAction(Request $request)
    {
        $image = $request->files->get('image', null);
        $imageType = $request->request->get('imageType', null);
        $x = $request->request->get("x", 0);
        $y = $request->request->get("y", 0);
        $width = $request->request->get("width", 0);
        $height = $request->request->get("height", 0);
        $resizeWidth = $request->request->get("resizeWidth", null);
        $resizeHeight = $request->request->get("resizeHeight", null);

        $validImageTypes = array(
            UserImage::IMAGE_TYPE_AVATAR,
            UserImage::IMAGE_TYPE_BANNER
        );

        $entityManager = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();

        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            !$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "You are not allowed to acccess this url",
                "data" => array(
                    "errors" => "You are not allowed to acccess this url"
                )
            ), 400);
        }

        if(!in_array($imageType, $validImageTypes)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid image type",
                "data" => array(
                    "errors" => "Invalid image type"
                )
            ), 400);
        }

        $accountManager = $this->get('yilinker_core.service.account_manager');

        $imageTmpPath = $image->getRealPath();
        $croppedImage = $accountManager->cropImage($authenticatedUser, $imageTmpPath, $x, $y, $width, $height, $image, $imageType);

        if(!$croppedImage){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed uploading image",
                "data" => array(
                    "errors" => "Failed uploading image"
                )
            ), 400);
        }

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->transactForm('user_image', null, array("image" => $image));

        if($form->isValid()){
            $userImage = $accountManager->instantiateUserPhoto($authenticatedUser, $croppedImage, true, false, $imageType);

            $entityManager->persist($userImage);

            $assetsHelper = $this->container->get('templating.helper.assets');

            $userImage = "";
            switch($imageType){
                case UserImage::IMAGE_TYPE_AVATAR:
                    $userImage = $authenticatedUser->getPrimaryImage();
                    break;
                case UserImage::IMAGE_TYPE_BANNER:
                    $userImage = $authenticatedUser->getPrimaryCoverPhoto();
                    break;
            }

            $imageUrl = "";
            $smallUrl = "";
            $mediumUrl = "";
            $largeUrl = "";
            $thumbnailUrl = "";
            if($userImage){
                $imageUrl = $assetsHelper->getUrl($userImage->getImageLocation(), 'user');
                $smallUrl = $assetsHelper->getUrl($userImage->getImageLocationBySize(UserImage::IMAGE_SIZE_SMALL), 'user');
                $mediumUrl = $assetsHelper->getUrl($userImage->getImageLocationBySize(UserImage::IMAGE_SIZE_MEDIUM), 'user');
                $largeUrl = $assetsHelper->getUrl($userImage->getImageLocationBySize(UserImage::IMAGE_SIZE_LARGE), 'user');
                $thumbnailUrl = $assetsHelper->getUrl($userImage->getImageLocationBySize(UserImage::IMAGE_SIZE_THUMBNAIL), 'user');
            }

            $entityManager->flush();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Successfully uploaded image.",
                "data" => array(
                    "imageUrl" => $imageUrl,
                    "smallUrl" => $smallUrl,
                    "mediumUrl" => $mediumUrl,
                    "largeUrl" => $largeUrl,
                    "thumbnailUrl" => $thumbnailUrl
                )
            ), 200);
        }

        $errors = $formErrorService->throwInvalidFields($form);
        return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
    }

    /**
     * Reactivate a user
     *
     * @param Request $request
     * @return Response
     */
    public function reactivateUserAction(Request $request)
    {
        $reactivationCode = $request->get('token');
        $entityManager = $this->getDoctrine()->getManager();
        $tokenOwner = $entityManager->getRepository('YilinkerCoreBundle:User')
                                    ->findOneBy(array(
                                        'reactivationCode' => $reactivationCode,
                                        'isActive'         => false,
                                    ));

        if($tokenOwner){
            $tokenOwner->setReactivationCode(null);
            $tokenOwner->setIsActive(true);
            $entityManager->flush();

            $roles = array('ROLE_BUYER');
            $firewall = 'buyer';
            if($tokenOwner->getUserType() === User::USER_TYPE_SELLER){
                $firewall = 'seller';
                if($tokenOwner->getStore()->getStoreType() === Store::STORE_TYPE_MERCHANT){
                    $roles = array('ROLE_MERCHANT');
                }
                else{
                    $roles = array('ROLE_RESELLER');
                }
            }

            if(
                $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY') ||
                $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ){
                $route = "user_logout";

                if(in_array("ROLE_MERCHANT", $roles)){
                    $route = "user_merchant_logout";
                }

                if(in_array("ROLE_RESELLER", $roles)){
                    $route = "user_affiliate_logout";
                }

                return $this->redirect($this->generateUrl($route));
            }

            $authService = $this->get('yilinker_core.security.authentication');
            $authService->authenticateUser($tokenOwner, $firewall, $roles);

            return $this->redirect($this->generateUrl('default'));
        }
        else{
            return $this->render('YilinkerCoreBundle:Error:404.html.twig');
        }
    }

    public function editUserAddressAction(Request $request)
    {
        $id = $request->get('id');
        $form = null;
        $em = $this->getDoctrine()->getEntityManager();
        $tbUserAddress = $em->getRepository('YilinkerCoreBundle:UserAddress');
        $userAddress = $tbUserAddress->find($id);
        if (!$userAddress) {
            throw new YilinkerException("User Address with #$id does not exist");
        }
        $options = array(
            'action'    => $this->get('router')->generate('core_address_edit_v1', array('id' => $id)),
            'edit_mode' => true
        );

        $form = $this->createForm('user_address_edit', $userAddress, $options);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $em->flush();

            $form = $this->createForm('user_address_edit', $form->getData(), $options);
        }
        $form = $form->createView();
        $data = compact('form');

        return $this->render('YilinkerCoreBundle:Form:user_address.html.twig', $data);
    }

    public function renderRegisterAction($actionUrl, $successUrl, $referral, $storeType)
    {
        $em = $this->getDoctrine()->getManager();
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $tbLanguage = $em->getRepository('YilinkerCoreBundle:Language');

        $kernelName = $this->get('kernel')->getName();
        $locale = $this->getRequest()->getLocale();
        $userLanguage = $tbLanguage->getByCodes($locale);
        $userLanguage = array_shift($userLanguage);
        $userCountry = $tbCountry->filterBy(array('lc' => array($locale), 'enAllCountries' => false))->getResult();
        $userCountry = array_shift($userCountry);

        $countries = $tbCountry->filterBy()->getResult();
        $languages = $tbLanguage->filterBy()->getResult();

        return $this->render('YilinkerCoreBundle:User:register.html.twig', compact(
            'actionUrl',
            'successUrl',
            'referral',
            'storeType',
            'kernelName',
            'userLanguage',
            'userCountry',
            'countries',
            'languages'
        ));
    }

    public function renderForgotPasswordAction($userType, $storeType)
    {
        $form = $this->createForm('user_forgot_password');
        $form = $form->createView();

        return $this->render('YilinkerCoreBundle:User:forgot_password.html.twig', compact('userType', 'storeType', 'form'));
    }

    public function daterangeEarningsAction(Request $request)
    {

        if (!$this->getUser()) {
            throw $this->createNotFoundException('You are not logged in');
        }

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();
        $em = $this->getDoctrine()->getEntityManager();
        $tbEarning = $em->getRepository('YilinkerCoreBundle:Earning');
        $filter = $request->get('earning_filter', array());
        $earnings = $tbEarning->getDailyEarning($store, $filter);

        return new JsonResponse($earnings);
    }

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $request
     * @return \Symfony\Component\Form\Form
     * @internal param $postData
     */
    private function transactForm($formType, $entity, $request, $options = array())
    {
        $form = $this->createForm($formType, $entity, $options);
        $form->submit($request);

        return $form;
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
