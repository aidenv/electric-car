<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Exception;
use Carbon\Carbon;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use ReCaptcha\ReCaptcha;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;

/**
 * Class UserController
 * @package Yilinker\Bundle\FrontendBundle\Controller
 */
class UserController extends Controller
{

    /**
     * @param null $referralCode
     * @return RedirectResponse
     */
    public function setCookieAction ($referralCode = null)
    {

        if (!is_null($referralCode)) {
            setcookie('referralCode', '', time() - 3600);
            setcookie('referralCode', $referralCode, time() + 3600, '/');
        }

        return $this->redirect($this->generateUrl('home_page'));
    }

    /**
     * Registration success page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerSuccessAction()
    {
         return $this->render('YilinkerFrontendBundle:User:register_success.html.twig');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(Request $request, $tab = "login")
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if(
            !$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticationUtils = $this->get('security.authentication_utils');
            $error = $authenticationUtils->getLastAuthenticationError();
            $email = $authenticationUtils->getLastUsername();

            $siteKey = $this->getParameter("grecaptcha_buyer_site_key");

            if ($error instanceof UsernameNotFoundException) {
                $error = new Exception('Email is required.');
            }

            if ($error instanceof AuthenticationServiceException) {
                $error = new Exception('Something went wrong. Please try again later.');
            }

            $token = $this->get('form.csrf_provider')->generateCsrfToken('core_user_add');
            $merchantHostName = $this->getParameter('merchant_hostname');

            return $this->render('YilinkerFrontendBundle:User:login.html.twig', compact('email', 'error', 'token', 'siteKey', 'tab', 'merchantHostName'));
        }
        else{
            return $this->redirect($this->generateUrl('home_page'));
        }
    }

    /**
    * Render Reset Password Verification Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function resetPasswordVerificationCodeAction()
    {
        $resetSession = $this->get('session')->get(OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD);
        if(!is_null($resetSession)){
            $expiration = $resetSession["expiration"];
            $timeNow = Carbon::now()->getTimestamp();

            if($expiration > $timeNow){
                $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('user_forgot_password_code');
                return $this->render('YilinkerFrontendBundle:User:reset_password_verification_code.html.twig', compact('csrfToken', 'expiration'));
            }
            else {
                // TODO : page for reset password verification code
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
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ){
            $this->get("Security.token_storage")->setToken(null);
        }

        if(!is_null($user)){
            $timeNow = Carbon::now()->getTimestamp();
            $expiration = $user->getForgotPasswordTokenExpiration()->getTimestamp();

            if($expiration > $timeNow){
                $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('user_reset_password');

                return $this->render('YilinkerFrontendBundle:User:reset_password.html.twig', compact("token", "csrfToken"));
            }
            else{
                $redirectUrl = $this->generateUrl('user_buyer_forgot_password_request');
                $verificationErrors = array(
                    "message" => "Unfortunately, this link has already expired.",
                    "buttonMessage" => "Request for reset password."
                );

                return $this->render('YilinkerCoreBundle:Error:404.html.twig', compact("verificationErrors", "redirectUrl"));
            }
        }

        $redirectUrl = $this->generateUrl('user_buyer_forgot_password_request');
        $verificationErrors = array(
            "message" => "Unfortunately, this request is either expired or invalid.",
            "buttonMessage" => "Request for reset password."
        );

        return $this->render('YilinkerCoreBundle:Error:404.html.twig', compact("verificationErrors", "redirectUrl"));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmResetPasswordAction(Request $request)
    {
        $postData = array(
            "plainPassword" => array(
                "first" => $request->request->get("password", ""),
                "second" => $request->request->get("confirmPassword", "")
            ),
            "_token" => $request->request->get('_token', "")
        );

        $token = $request->request->get("forgotPasswordToken", "");

        $entityManager = $this->get('doctrine')->getManager();
        $user = $entityManager->getRepository("YilinkerCoreBundle:User")
                              ->findOneBy(array("forgotPasswordToken" => $token));

        if(!is_null($user)){
            $timeNow = Carbon::now()->getTimestamp();
            $expiration = $user->getForgotPasswordTokenExpiration()->getTimestamp();

            if($expiration > $timeNow){

                $form = $this->transactForm('user_reset_password', $user, $postData);

                if(!$form->isValid()){
                    $formErrorsService = $this->get('yilinker_core.service.form.form_error');
                    return $this->throwErrorJsonResponse($formErrorsService->throwInvalidFields($form), "Reset password failed.");
                }

                $entityManager->beginTransaction();

                try{

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
                    $this->get('session')->getFlashBag()->add('resetPassword', 'true');

                    return new JsonResponse(array(
                        "isSuccessful" => true,
                        "message" => "Password successfully changed.",
                        "data" => array()
                    ), 200);
                }
                catch(Exception $e){
                    return $this->throwErrorJsonResponse(array("Reset password failed."), "Reset password failed.");
                }
            }
            else{
                // TODO : page for expired confirm reset password
                return $this->render('YilinkerCoreBundle:Error:404.html.twig', array(
                    'redirectUrl' =>  $this->generateUrl('home_page'),
                ));
            }
        }

        return $this->render('YilinkerCoreBundle:Error:404.html.twig', array(
            'redirectUrl' =>  $this->generateUrl('home_page'),
        ));
    }

    /**
     * Logs out the authenticated user
     */
    public function logoutAction()
    {
        $authService = $this->get('yilinker_front_end.security.authentication');
        $authService->removeAuthentication();

        return $this->redirect($this->generateUrl('user_buyer_login'));
    }

    /**
     * Renders Merge Account Page
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function renderSocialMediaMergeAction(Request $request)
    {
        $session = $this->container->get('session');

        if ($session->has('userId')) {
            $data = base64_encode(serialize([
                'userId'          => $session->get('userId'),
                'socialMediaId'   => $session->get('socialMediaId'),
                'oauthProviderId' => $session->get('oauthProviderId')
            ]));

            $email = $session->get('userEmail');
            $url = '/socialMedia/mergeAccount?h=' . $data;

            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository("YilinkerCoreBundle:User")->findOneBy(array(
                        "userId" => $session->get('userId'),
                        "userType" => User::USER_TYPE_BUYER
                    ));

            if(is_null($user)){
                $session->getFlashBag()->add('failedRegisteredMerge', 'true');
                return $this->redirect($this->generateUrl('user_buyer_login'));
            }

            $session->remove('userId');
            $session->remove('userEmail');
            $session->remove('socialMediaId');
            $session->remove('oauthProviderId');

            $data = compact(
                'email',
                'url'
            );

            return $this->render('YilinkerFrontendBundle:User:merge_account.html.twig', $data);
        }

        return $this->redirect($this->generateUrl('user_buyer_login'));

    }

    /**
     * Sends Merge Account notification to user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendMergeNotificationAction(Request $request)
    {
        $url = $request->request->get('url', null);
        $email = $request->request->get('email', null);
        $isSuccessful = false;

        if ($url !== null && $email !== null) {
            $mailer = $this->get('yilinker_core.service.user.mailer');
            $mailer->sendMergeAccountVerification($url, $email);
            $isSuccessful = true;
        }

        return new JsonResponse($isSuccessful);
    }

    /**
     * Merge Account
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function socialMediaMergeAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = unserialize(base64_decode($request->query->get("h")));
        $userEntity = $em->getRepository('YilinkerCoreBundle:User')->find($data['userId']);
        $oauthProvider = $em->getRepository('YilinkerCoreBundle:OauthProvider')->find($data['oauthProviderId']);
        $socialMediaManager = $this->get('yilinker_front_end.service.social_media.social_media_manager');

        $em->beginTransaction();

        try{

            $userEntity = $socialMediaManager->mergeAccount($userEntity, $data['socialMediaId'], $oauthProvider);

            $jwtService = $this->get("yilinker_core.service.jwt_manager");
            $request = $jwtService->setKey("ylo_secret_key")->encodeUser($userEntity)->encodeToken(null);

            $ylaService = $this->get("yilinker_core.service.yla_service");
            $ylaService->setEndpoint(false);

            $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

            if(is_array($response) && array_key_exists("isSuccessful", $response) && $response["isSuccessful"]){
                $em->commit();
            }
            else{
                throw new Exception("Error Processing Request");
            }

            $token = new UsernamePasswordToken($userEntity, null, 'buyer', $userEntity->getRoles());
            $this->get('security.context')->setToken($token);
            $this->get('session')->set('_security_main',serialize($token));
        }
        catch(Exception $e){
            $em->rollback();
            $this->get('session')->getFlashBag()->add('failedMerge', 'true');
        }

        return $this->redirect($this->generateUrl('user_buyer_login'));
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
    * Render Profile Activity Log Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileActivityLogAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $tbUserActivities = $em->getRepository('YilinkerCoreBundle:UserActivityHistory');
        $user = $this->getUser();
        $timeline = $tbUserActivities->getTimelinedActivities($user->getId());
        $data = compact('timeline');

        return $this->render('YilinkerFrontendBundle:Profile:profile_activity_log.html.twig', $data);
    }

    /**
    * Render Profile Following page
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileFollowingAction(Request $request)
    {
        $securityContext = $this->container->get('security.context');
        $em = $this->getDoctrine()->getManager();

        if (
            !$securityContext->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $url = $this->generateUrl("user_buyer_login");
            return $this->redirect($url);
        }

        $authenticatedUser = $this->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();
        $locationRepository = $this->getDoctrine()
                                   ->getRepository('YilinkerCoreBundle:Location');
        $provinces = $locationRepository->getLocationsByType(LocationType::LOCATION_TYPE_PROVINCE);
        $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');

        $page = (int) $request->request->get("page", 1);
        $limit = (int) $request->request->get("limit", 10);

        if ($page < 1 OR $limit < 1) {
            return $userFollowService->throwInvalidFields(null, false, array("Invalid limit or offset supplied"));
        }

        $userFollowService->setAuthenticatedUser($authenticatedUser);
        $offset = $userFollowService->getOffset(10, $page);
        $userFollowRepository = $em->getRepository("YilinkerCoreBundle:UserFollow");
        $followedSellers = $userFollowRepository->loadFollowedSellers($authenticatedUser, '', $limit, $offset);
        $followedSellersData = $userFollowService->constructSellers($followedSellers);

        $data = array (
            "user" => $authenticatedUser,
            "provinces" => $provinces,
            "listOfFollowedSeller" => $followedSellersData
        );

        return $this->render('YilinkerFrontendBundle:Profile:profile_following.html.twig', $data);
    }

    /**
    * Render Profile Messages Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileMessagesAction()
    {
        return $this->render('YilinkerFrontendBundle:Profile:profile_messages.html.twig');
    }


    /**
    * Render Profile My Points Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileMyPointsAction()
    {
        return $this->render('YilinkerFrontendBundle:Profile:profile_my_points.html.twig');
    }

    /**
    * Render Profile Settings Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileSettingsAction()
    {
        return $this->render('YilinkerFrontendBundle:Profile:profile_settings.html.twig');
    }

    /**
    * Render Profile Transactions Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileTransactionAction()
    {
        return $this->render('YilinkerFrontendBundle:Profile:profile_transaction.html.twig');
    }

    /**
    * Render Profile Transactions Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function profileTransactionViewAction()
    {
        return $this->render('YilinkerFrontendBundle:Profile:profile_transaction_view.html.twig');
    }

    /**
    * Render Profile Help Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */

    public function profileHelpAction()
    {
        return $this->render('YilinkerFrontendBundle:Profile:profile_help.html.twig');
    }

    /**
     * Render Store Page Home Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function storeHomepageAction()
    {
        return $this->render('YilinkerFrontendBundle:Store:store_home.html.twig');
    }

    /**
    * Render Store Category View Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function storeCategoryViewAction()
    {
        return $this->render('YilinkerFrontendBundle:Store:store_category_view.html.twig');
    }

    /**
    * Render Store About Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function storeAboutAction()
    {
        return $this->render('YilinkerFrontendBundle:Store:store_about.html.twig');
    }

    /**
    * Render Store Feedback Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function storeFeedbackAction()
    {
        return $this->render('YilinkerFrontendBundle:Store:store_feedback.html.twig');
    }

}
