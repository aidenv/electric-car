<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Exception;
use Carbon\Carbon;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\UserMerge;
use Yilinker\Bundle\CoreBundle\Entity\UserReferral;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;

/**
 * Class UserController
 *
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class UserController extends YilinkerBaseController
{

    /**
     * @param null $referralCode
     * @return RedirectResponse
     */
    public function setCookieAction ($referralCode = null)
    {

        if (!is_null($referralCode)) {
            setcookie('referralCode', '', time() - 3600);
            setcookie('YLO_SESSION', '', time() - 3600);
            setcookie('YLO_SESSION', $_COOKIE['YLO_SESSION']);
            setcookie('referralCode', $referralCode, time() + 3600, '/');
        }

        return $this->redirect($this->generateUrl('home_page'));
    }

    /**
     * Renders Merchant Login
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginRenderAction(Request $request, $tab = null)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        $tokenStorage = $this->container->get('security.token_storage');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $tokenStorage = $this->container->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();
            if(in_array('ROLE_UNACCREDITED_MERCHANT', $authenticatedUser->getRoles())){
                return $this->redirect($this->generateUrl('merchant_accreditation'));
            }
            return $this->redirect($this->generateUrl('home_page'));
        }

        $siteKey = $this->getParameter("grecaptcha_merchant_site_key");

        $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('form');

        return $this->render('YilinkerMerchantBundle:User:merchant_login.html.twig', array(
            'token'            => $csrfToken,
            'siteKey'          => $siteKey,
            'tab'              => $tab,
            'frontendHostName' => $this->getParameter('frontend_hostname')
        ));
    }

    public function affiliateLoginRenderAction(Request $request, $tab = null)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        $tokenStorage = $this->container->get('security.token_storage');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            return $this->redirect($this->generateUrl('home_page'));
        }

        $siteKey = $this->getParameter("grecaptcha_merchant_site_key");

        $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('form');

        return $this->render('YilinkerMerchantBundle:Affiliate:login_affiliate.html.twig', array(
            'token'            => $csrfToken,
            'siteKey'          => $siteKey,
            'tab'              => $tab,
            'frontendHostName' => $this->getParameter('frontend_hostname')
        ));
    }

    /**
     * Get Merchant Image
     *
     * @return JsonResponse
     */
    public function getMerchantImageAction()
    {
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();

        $response = array(
            'avatar' => '',
            'banner' => '',
        );

        if($authenticatedUser){
            $assetHelper = $this->get('templating.helper.assets');
            $avatar = $authenticatedUser->getPrimaryImage();
            $banner = $authenticatedUser->getPrimaryCoverPhoto();
            $avatarFileLocation = $avatar ? $avatar->getImageLocationBySize("medium") : UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE;
            $bannerFileLocation = $banner ? $banner->getImageLocationBySize("large") : UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_BANNER_FILE;
            $response['avatar'] = $assetHelper->getUrl($avatarFileLocation, 'user');
            $response['banner'] = $assetHelper->getUrl($bannerFileLocation, 'user');
        }

        return new JsonResponse($response);
    }

    public function resetPasswordVerificationCodeAction()
    {
        $resetSession = $this->get('session')->get(OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD);
        if(!is_null($resetSession)){
            $expiration = $resetSession["expiration"];
            $timeNow = Carbon::now()->getTimestamp();

            if($expiration > $timeNow){
                $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('user_forgot_password_code');
                return $this->render('YilinkerMerchantBundle:User:merchant_reset_password_verification_code.html.twig', compact('csrfToken', 'expiration'));
            }
            else {
                return $this->render('YilinkerCoreBundle:Error:404.html.twig', array(
                    'redirectUrl' =>  $this->generateUrl('home_page'),
                ));
            }
        }
        else{
            return $this->render('YilinkerCoreBundle:Error:404.html.twig', array(
                'redirectUrl' =>  $this->generateUrl('home_page'),
            ));
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetPasswordAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        $entityManager = $this->get('doctrine')->getManager();

        $token = $request->query->get('tk', 'none');

        $user = $entityManager->getRepository("YilinkerCoreBundle:User")
                              ->findOneBy(array("forgotPasswordToken" => $token));

        if(
            !is_null($user) &&
            (
                !$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') &&
                !$authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            )
        ){
            $timeNow = Carbon::now()->getTimestamp();
            $expiration = $user->getForgotPasswordTokenExpiration()->getTimestamp();

            if ($expiration > $timeNow) {
                $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('user_reset_password');

                return $this->render('YilinkerMerchantBundle:User:reset_password.html.twig', compact("token", "csrfToken"));
            }
            else{
                $redirectUrl = $this->generateUrl('user_forgot_password_request');
                $verificationErrors = array(
                    "message" => "Unfortunately, this link has already expired.",
                    "buttonMessage" => "Request for reset password."
                );

                return $this->render('YilinkerCoreBundle:Error:404.html.twig', compact("verificationErrors", "redirectUrl"));
            }
        }

        return $this->render('YilinkerCoreBundle:Error:404.html.twig');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmResetPasswordAction(Request $request)
    {
        $password = $request->get("password", "");
        $passwordConfirm = $request->get("confirmPassword", "");
        $postData = array(
            "plainPassword" => array(
                "first" => $password,
                "second" => $passwordConfirm
            ),
            "_token" => $request->request->get('_token', "")
        );

        $token = $request->get("forgotPasswordToken", "");
        $formErrorsService = $this->get('yilinker_core.service.form.form_error');

        $entityManager = $this->get('doctrine')->getManager();
        $user = $entityManager->getRepository("YilinkerCoreBundle:User")
                              ->findOneBy(array("forgotPasswordToken" => $token));

        if (!is_null($user)) {
            $timeNow = Carbon::now()->getTimestamp();
            $expiration = $user->getForgotPasswordTokenExpiration()->getTimestamp();

            if ($expiration > $timeNow) {

                $form = $this->transactForm('user_reset_password', $user, $postData);

                if(!$form->isValid()){
                    return $this->throwErrorJsonResponse($formErrorsService->throwInvalidFields($form), "Reset password failed.");
                }

                $entityManager->beginTransaction();

                try{

                    $this->get('session')->getFlashBag()->add('resetPassword', 'true');
                    $user->setForgotPasswordToken(null)
                         ->setForgotPasswordTokenExpiration(null)
                         ->setForgotPasswordCode(null)
                         ->setForgotPasswordCodeExpiration(null);

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $jwtService = $this->get("yilinker_core.service.jwt_manager");
                    $request = $jwtService->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

                    $ylaService = $this->get("yilinker_core.service.yla_service");
                    $ylaService->setEndpoint(false);

                    $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

                    $entityManager->commit();

                    return new JsonResponse(array(
                        "isSuccessful" => true,
                        "message" => "Password successfully changed.",
                        "data" => array(
                            "userType" => User::USER_TYPE_SELLER,
                            "storeType" => $user->getStore()->getStoreType(),
                        )
                    ), 200);
                }
                catch(Exception $e){

                    $entityManager->rollback();

                    return new JsonResponse(array(
                        "isSuccessful" => true,
                        "message" => "Reset password failed.",
                        "data" => array(
                            "errors" => array("An error occured. Please try again later.")
                        )
                    ), 400);
                }
            }
        }

        throw $this->createNotFoundException('Password token not found');
    }

    public function accountInformationAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        return $this->render('YilinkerMerchantBundle:User:account_information.html.twig', array(
            "authenticatedUser" => $authenticatedUser,
        ));
    }


    /**
     * Render Dashboard Store Information Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function storeInformationAction()
    {
        $baseUri = $this->getParameter('frontend_hostname');
        $facebookClientId = $this->getParameter('merchant_facebook_client_id');
        $googleClientId = $this->getParameter('google_client_id');

        $assetsHelper = $this->container->get('templating.helper.assets');

        $authenticatedUser = $this->getAuthenticatedUser();

        $image = $authenticatedUser->getPrimaryImage();
        $cover = $authenticatedUser->getPrimaryCoverPhoto();

        $profilePhoto = $image ? $image->getImageLocation() : UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_SELLER_AVATAR_FILE;
        $coverPhoto = $cover ? $cover->getImageLocation() : UserImage::DEFAULT_DIRECTORY.'/'.UserImage::DEFAULT_BANNER_FILE;
        $storeCategories = $this->get('yilinker_core.service.store_category_service')
                                ->getCategoryWithSelectedStoreCategory($authenticatedUser->getStore());

        $userReferralEntity = $this->getAuthenticatedUser()->getUserReferral();

        $userReferrerEntity = null;

        if ($userReferralEntity instanceof UserReferral) {
            $userReferrerEntity = $userReferralEntity->getReferrer();
        }
        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
        $applicationDetails = $applicationManager->getApplicationDetailsBySeller($authenticatedUser);

        $storeDetails = array (
            "profilePhoto"       => $assetsHelper->getUrl($profilePhoto, 'user'),
            "coverPhoto"         => $assetsHelper->getUrl($coverPhoto, 'user'),
            "storeNumber"        => $authenticatedUser->getStore()->getStoreNumber(),
            "storeName"          => $authenticatedUser->getStore()->getStoreName(),
            "storeType"          => $authenticatedUser->getStore()->isAffiliate() ? Store::STORE_TYPE_RESELLER : Store::STORE_TYPE_MERCHANT,
            "contactNumber"      => $authenticatedUser->getContactNumber(),
            "isMobileVerified"   => $authenticatedUser->getIsMobileVerified(),
            "storeSlug"          => $authenticatedUser->getStore()->getStoreSlug(),
            "slugChanged"        => $authenticatedUser->getStore()->getSlugChanged(),
            "storeDescription"   => $authenticatedUser->getStore()->getStoreDescription(),
            "isEditable"         => $authenticatedUser->getStore()->getIsEditable(),
            "accreditationLevel" => is_null($authenticatedUser->getStore()->getAccreditationLevel()) ? false : $authenticatedUser->getStore()->getAccreditationLevel(),
            "qrCodeLocation"     => $assetsHelper->getUrl($authenticatedUser->getStore()->getQrCodeLocation(), 'qr_code'),
            "referrer"           => $userReferrerEntity,
            "storeCategories"    => $storeCategories
        );

        $view = $authenticatedUser->getStore()->isAffiliate() ? 'YilinkerMerchantBundle:User:store_information_affiliate.html.twig'
                                                              : 'YilinkerMerchantBundle:User:store_information.html.twig';

        return $this->render($view, compact(
            "storeDetails",
            "baseUri",
            "facebookClientId",
            "googleClientId",
            "applicationDetails"
        ));
    }

    /**
     * Update StoreCategory
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitStoreCategoryAction (Request $request)
    {
        $selectedStoreCategoryIds = $request->request->get('selectedStoreCategoryIds', null);
        $authenticatedUser = $this->getAuthenticatedUser();
        $isSuccessful = true;
        $message = null;

        if ($selectedStoreCategoryIds === null || !is_array($selectedStoreCategoryIds) || sizeof($selectedStoreCategoryIds) == 0) {
            $isSuccessful = false;
            $message = 'Invalid Product Category.';
        }
        else if (!($authenticatedUser instanceof User)) {
            $isSuccessful = false;
            $message = 'Invalid Access.';
        }

        /**
         * Persist
         */
        if ($isSuccessful === true) {
            $em = $this->getDoctrine()->getManager();
            $storeEntity = $em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($authenticatedUser);
            $this->get('yilinker_core.service.store_category_service')
                 ->processSelectedCategory($storeEntity, $selectedStoreCategoryIds);
        }

        return new JsonResponse(compact('isSuccessful', 'message'));
    }

    public function shareViaEmailAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        $recipient = $request->get("recipient");
        $message = $request->get("message");

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->getAuthenticatedUser();

            if($authenticatedUser->getUserType() != User::USER_TYPE_SELLER){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Unauthorized access.",
                    "data" => array()
                ), 403);
            }

            $recipients = explode(",", $recipient);
            $validRecipients = array();

            foreach ($recipients as $email) {
                $email = trim($email);
                if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                    return new JsonResponse(array(
                        "isSuccessful" => false,
                        "message" => "Invalid Email Address.",
                        "data" => array()
                    ), 400);
                }
                else{
                    array_push($validRecipients, $email);
                }
            }

            $baseUri = $this->getParameter("frontend_hostname");
            $mailer = $this->get('yilinker_core.service.user.mailer');
            $mailer->shareViaEmail($validRecipients, $message, $baseUri, $authenticatedUser->getStore());

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Email has been sent.",
                "data" => array()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Unauthorized access.",
            "data" => array()
        ), 403);
    }

    /**
     * Process User referrer
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processUserReferrerAction (Request $request)
    {
        $referrerCode = $request->get('referrerCode', null);
        $accountManager = $this->get('yilinker_core.service.account_manager');
        $response = $accountManager->processReferralCode($referrerCode, $this->getAuthenticatedUser());

        return new JsonResponse($response);
    }

    /**
     * Process Affiliate information in storeInfo for accreditation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processAffiliateAccreditationInformationAction (Request $request)
    {
        $response = array (
            'isSuccessful' => true,
            'message'      => '',
            'data'         => null
        );
        $userEntity = $this->getUser();
        $firstName = $request->get('firstName', null);
        $lastName = $request->get('lastName', null);
        $storeName = $request->get('storeName', null);
        $storeSlug = $request->get('storeSlug', null);
        $storeDesc = $request->get('storeDesc', null);

        $formData = [
            'firstName'        => $firstName,
            'lastName'         => $lastName,
            'storeName'        => $storeName,
            'storeSlug'        => $storeSlug,
            'storeDescription' => $storeDesc,
        ];

        $form = $this->createForm('affiliate_accreditation_application_information', null, array('user' => $userEntity));
        $form->submit($formData);
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        if (!($userEntity instanceof User)) {
            $response = array (
                'isSuccessful' => false,
                'message'      => 'Invalid Access',
                'data'         => null
            );
        }
        else if (!($form->isValid())) {
            $response = array (
                'isSuccessful' => false,
                'message' => $formErrorService->throwInvalidFields($form),
            );
        }

        // PERSIST
        if ($response['isSuccessful'] === true) {
            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $documentUploader = $this->get('yilinker_core.service.upload.document_uploader');
            $accreditationApplication = $applicationManager->processAffiliateAccreditationApplication(
                                                                 $userEntity,
                                                                 $firstName,
                                                                 $lastName,
                                                                 null,
                                                                 $storeName,
                                                                 $storeSlug,
                                                                 $storeDesc
                                                             );

            $response = array (
                'isSuccessful' => true,
                'message' => '',
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Process legal document in storeInfo for accreditation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processAffiliateAccreditationLegalDocumentAction (Request $request)
    {
        $response = array (
            'isSuccessful' => true,
            'message'      => '',
            'data'         => null
        );

        $user = $this->getUser();
        $tinId = $request->get('tinId', null);
        $tinImage = $request->files->get('tinImage', null);

        $formData = [
            'tin'              => $tinId,
            'tinImage'         => array($tinImage)
        ];

        $form = $this->createForm('affiliate_accreditation_application_information', null, array('user' => $user));
        $form->submit($formData);
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        if (!($user instanceof User)) {
            $response = array (
                'isSuccessful' => false,
                'message'      => 'Invalid Access',
                'data'         => null
            );
        }
        else if (!($form->isValid())) {
            $response = array (
                'isSuccessful' => false,
                'message' => $formErrorService->throwInvalidFields($form),
            );
        }

        // PERSIST
        if ($response['isSuccessful'] === true) {

            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $accreditationApplication = $this->get("doctrine.orm.entity_manager")
                                             ->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                             ->findOneByUser($user);

            if ($accreditationApplication instanceof AccreditationApplication) {
                if (!is_null($tinId) && ($user->getTin() == '' || (int) $user->getTin() == 0 || $accreditationApplication->getIsBusinessEditable())) {
                    $user->setTin($tinId);
                }
                $accreditationApplication->setIsBusinessEditable(false);
            }

            $documentUploader = $this->get('yilinker_core.service.upload.document_uploader');

            if ($tinImage !== null && $accreditationApplication instanceof AccreditationApplication) {
                $legalDocumentTypeValidId = LegalDocumentType::TYPE_VALID_ID;
                $validIdImageName = trim($user->getUserId() . '_' . rand(1, 9999). '_' . $legalDocumentTypeValidId . '_' . strtotime(Carbon::now()));
                $validIdFileLoc = $documentUploader->uploadFile ($tinImage, $user->getUserId(), $validIdImageName);
                $applicationManager->submitLegalDocument ($accreditationApplication, $legalDocumentTypeValidId, $validIdFileLoc);
            }

            $response = array (
                'isSuccessful' => true,
                'message' => '',
            );
        }

        return new JsonResponse($response);
    }

    private function createNewUser($response, $request, $storeType)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        $formData = array(
            'firstName' => $request->get("firstName", ""),
            'lastName' => $request->get("lastName", ""),
            "plainPassword" => array(
                "first" => $request->get("password", ""),
                "second" => $request->get("confirmPassword", "")
            ),
            'email' => $request->get("email", ""),
            'contactNumber' => $request->get("contactNumber", ""),
            '_token' => $request->get("token", ""),
        );

        $form = $this->transactForm('core_user_add', new User(), $formData);
        $accountManager = $this->get('yilinker_core.service.account_manager');

//        $userReferralResult = $accountManager->applyReferralCode($request->get('referralCode'), User::USER_TYPE_SELLER, $storeType);

        if ($form->isValid()) {
            $entityManager = $this->get('doctrine')->getManager();
            $entityManager->beginTransaction();

            try{

                $user = $form->getData();

                $user->setUserType(User::USER_TYPE_SELLER);
                $user = $accountManager->registerUser($user);

                if ($user instanceof User) {
                    $accountManager->generateReferralCode($user);
                }

                $storeService = $this->get('yilinker_core.service.entity.store');
                $store = $storeService->createStore($user, $storeType);

                $jwtService = $this->get("yilinker_core.service.jwt_manager");
                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

                $ylaService = $this->get("yilinker_core.service.yla_service");
                $ylaService->setEndpoint(false);

                $ylaResponse = $ylaService->sendRequest("user_create", "post", array("request" => $request));

                if(!is_array($ylaResponse) || !array_key_exists("isSuccessful", $ylaResponse) || !$ylaResponse["isSuccessful"]){
                    return $this->throwErrorJsonResponse(array("An error occured. Please try again later."), "Registration failed.");
                }

                $store->setStoreNumber($storeService->generateStoreNumber($store));
                $user->setAccountId($ylaResponse["data"]["userId"]);
                $entityManager->flush();

                $authService = $this->get('yilinker_core.security.authentication');

                if($storeType == Store::STORE_TYPE_MERCHANT){
                    $authService->authenticateUser($user, 'seller', array('ROLE_UNACCREDITED_MERCHANT'));
                }
                elseif($storeType == Store::STORE_TYPE_RESELLER){
                    $authService->authenticateUser($user, 'affiliate', array('ROLE_UNACCREDITED_MERCHANT'));
                }

                $response['message'] = 'Registration successful';
                $response['isSuccessful'] = true;
                $response['data'] = array();

                $entityManager->commit();
            }
            catch(Exception $e){
                $entityManager->rollback();
                $response['data']['errors'] = $e->getMessage();
            }
        }
        else{
            $errors = $formErrorService->throwInvalidFields($form);
            $response['data']['errors'] = $errors;
        }

        return $response;
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
     * @param $error
     * @param $message
     * @return JsonResponse
     */
    private function throwErrorJsonResponse($error, $message)
    {
        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $message,
            "data" => array(
                "errors" => $error,
            )
        ), 400);
    }

    /**
     * Render Affiliate Landing Page Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function affiliateLandingPageAction()
    {
        $securityContext = $this->get('security.context');

        if ($securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('home_page');
        }

        return $this->render('YilinkerMerchantBundle:Affiliate:landing_page.html.twig');
    }
}
