<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api\v2;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Country;
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
    use AccessTokenGenerator;


    /**
     * Handles Request For Sending SMS
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="One Time Password",
     *     statusCodes={
     *         200={
                    "sentTo (string)",
                    "sentOn (string)",
                    "provider (string)",
                    "expiration (string)"
     *         },
     *         400={
     *             "Field errors or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="contactNumber", "dataType"="string", "required"=false, "description"="Required if unauthenticated"},
     *         {"name"="areaCode", "dataType"="string", "required"=false, "description"="Required if unauthenticated"},
     *         {"name"="storeType", "dataType"="int", "required"=false, "description"="0 for seller, 1 for affiliate. Not required if buyer, defaults to affiliate. "},
     *         {"name"="type", "dataType"="string", "required"=true, "description"="Available types atm : register, forgot-password, checkout, guest_checkout, withdrawal, verify_contact_number, change-contact-number"},
     *     },
     *     views = {"otp", "default", "v2"}
     * )
     */
    public function sendSmsAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $kernel = $this->get("kernel");

        $userRepository = $em->getRepository("YilinkerCoreBundle:User");
        $countryRepository = $em->getRepository("YilinkerCoreBundle:Country");

        $storeType = $request->get("storeType", Store::STORE_TYPE_RESELLER);

        if ($kernel->getName() == 'frontend') {
            $userType = User::USER_TYPE_BUYER;
        }
        elseif ($kernel->getName() == 'merchant') {
            $userType = User::USER_TYPE_SELLER;
        }

        $areaCode = $request->get("areaCode", Country::AREA_CODE_PHILIPPINES);
        if($request->getLocale() == 'cn'){
            $areaCode = Country::AREA_CODE_CHINA;
        }  	
        
        $contactNumber = $request->get("contactNumber", null);
        $type = $request->get("type", null);

        $oneTimePasswordService = $this->get('yilinker_core.service.sms.one_time_password');

        $country = $countryRepository->findOneBy(array(
            "areaCode" => $areaCode
        ));

        if(is_null($country)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "SMS support is not available in your country.",
                "data" => array(
                    "errors" => array("SMS support is not available in your country.")
                )
            ), 400);
        }

        if(is_null($type)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Token type is required.",
                "data" => array(
                    "errors" => array("Token type is required.")
                )
            ), 400);
        }

        $contactNumber = $this->formatContactNumber($country->getCode(), $contactNumber);;
        $validationData = $oneTimePasswordService->validateTokenType($type, $contactNumber, $userType, $storeType);

        if($validationData["isSuccessful"]){
            
            $user = $validationData["data"]["user"];

            if($validationData["data"]["mutable"]){
                $contactNumber = $validationData["data"]["contactNumber"];
            }
        }
        else{
            return new JsonResponse($validationData, 400);
        }

        $oneTimePassword = $oneTimePasswordService->sendOneTimePassword($user, $country, $contactNumber, $type);

        if($oneTimePassword && $oneTimePassword instanceof OneTimePassword){

            if($request->isXmlHttpRequest()){

                switch($type){
                    case OneTimePasswordService::OTP_TYPE_REGISTER:
                        $sessionKey = OneTimePasswordService::OTP_TYPE_REGISTER;
                        break;
                    default:
                        $sessionKey = OneTimePasswordService::OTP_TYPE_REGISTER;
                        break;
                }

                $this->get('session')->set($sessionKey, array(
                    "expiration" => $oneTimePassword->getTokenExpiration()
                ));
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Message sent to {$contactNumber}",
                "data" => array(
                    "sentTo" => $oneTimePassword->getContactNumber(),
                    "sentOn" => $oneTimePassword->getDateAdded()->format("Y-m-d H:i:s"),
                    "provider" => $oneTimePassword->getProviderName(),
                    "expiration" => $oneTimePassword->getTokenExpiration()->format("Y-m-d H:i:s")
                )
            ), 200);
        }

        if($oneTimePassword === false){
            $message = "Sms provider not available";
        }
        else{
            $message = $oneTimePassword;
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $message,
            "data" => array(
                "errors" => array($message)
            )
        ), 400);
    }

    /**
     * Handles Request For Validating Token
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="One Time Password",
     *     statusCodes={
     *         200={
                    "true"
     *         },
     *         400={
     *             "Field errors or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="contactNumber", "dataType"="string", "required"=false, "description"="Required if unauthenticated and in some types"},
     *         {"name"="verificationCode", "dataType"="string", "required"=true, "description"="Verification Code"},
     *         {"name"="type", "dataType"="string", "required"=true, "description"="Available types atm : register, forgot-password, checkout, guest_checkout, withdrawal, verify_contact_number, change-contact-number"},
     *         {"name"="storeType", "dataType"="int", "required"=false, "description"="0 for seller, 1 for affiliate. Not required if buyer, defaults to affiliate. "},
     *     },
     *     views = {"otp", "default", "v2"}
     * )
     */
    public function validateSmsAction(Request $request)
    {
        $contactNumber = $request->get("contactNumber", null);
        $verificationCode = $request->get("verificationCode", null);
        $type = $request->get("type", null);
        $storeType = $request->get("storeType", Store::STORE_TYPE_RESELLER);

        if(is_null($type) || is_null($contactNumber) || is_null($verificationCode)){
            $errors = array();

            is_null($type)? array_push($errors, "Token type is required.") : null;
            is_null($contactNumber)? array_push($errors, "Contact number is required.") : null;
            is_null($verificationCode)? array_push($errors, "Verification code is required.") : null;

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid fields supplied.",
                "data" => array(
                    "errors" => $errors
                )
            ), 400);
        }

        $kernel = $this->get("kernel");
        if ($kernel->getName() == 'frontend') {
            $userType = User::USER_TYPE_BUYER;
        }
        elseif ($kernel->getName() == 'merchant') {
            $userType = User::USER_TYPE_SELLER;
        }

        $oneTimePasswordService = $this->get('yilinker_core.service.sms.one_time_password');

        $validationData = $oneTimePasswordService->validateTokenType($type, $contactNumber, $userType, $storeType);

        $user = null;
        if($validationData["isSuccessful"] && $validationData["data"]["mutable"]){
            $user = $validationData["data"]["user"];
            $contactNumber = $validationData["data"]["contactNumber"];
        }
        elseif($validationData["isSuccessful"] && !$validationData["data"]["mutable"]){
            $user = $validationData["data"]["user"];
        }
        else{
            return new JsonResponse($validationData, 400);
        }

        $isValid = $oneTimePasswordService->confirmOneTimePassword(
                        $user,
                        $contactNumber,
                        $verificationCode,
                        $type,
                        ($type == OneTimePasswordService::OTP_TYPE_CHANGE_CONTACT_NUMBER)
                        || ($type == OneTimePasswordService::OTP_TYPE_VERIFY_CONTACT_NUMBER)
                    );

        if($isValid){
            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Token is valid",
                "data" => array()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Token is either invalid or expired.",
            "data" => array(
                "errors" => array("Token is either invalid or expired.")
            )
        ), 400);
    }

    /**
     * Handles Request For Registration
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="User",
     *     statusCodes={
     *         200={
                  "access_token (string)",
                  "expires_in (int)",
                  "token_type (string)",
                  "scope (string)",
                  "refresh_token (string)"
     *         },
     *         400={
     *             "Field errors or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="client_id", "dataType"="string", "required"=true, "description"="Oauth client ID"},
     *         {"name"="client_secret", "dataType"="string", "required"=true, "description"="Oauth client secret"},
     *         {"name"="grant_type", "dataType"="string", "required"=true, "description"="Oauth grant type"},
     *         {"name"="contactNumber", "dataType"="string", "required"=true, "description"="Contact number to be registered, must be unique."},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="Minimum of 8 atleast 1 number."},
     *         {"name"="verificationCode", "dataType"="string", "required"=true, "description"="Value from OTP."},
     *         {"name"="areaCode", "dataType"="string", "required"=true, "description"="Country area code."},
     *         {"name"="storeType", "dataType"="string", "required"=false, "description"="(Optional) defaults to affiliate"},
     *         {"name"="language", "dataType"="int", "required"=false }
     *     },
     *     views = {"user", "default", "v2"}
     * )
     */
    public function registerUserAction (Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
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
                "second" => $request->get("password", null)
            ),
            "contactNumber" => $request->get("contactNumber", null),
            "verificationCode" => $request->get("verificationCode", null),
            "areaCode" => $request->get("areaCode", "63"),
            "referralCode" => $request->get('referrerCode', '')
        );

        $form = $this->transactForm("core_user_add", null, $formData, array(
            "csrf_protection" => false,
            "storeType" => $storeType,
            "mustVerify" => true,
            "contactNumber" => $formData["contactNumber"],
            "token" => $formData["verificationCode"],
            "user" => null,
            "type" => OneTimePasswordService::OTP_TYPE_REGISTER,
            "areaCode" => $formData["areaCode"],
            "userType" => $userType
        ));

        if($form->isValid()){

            $data = $form->getData();
            if (!is_null($request->get('language'))) {
                $data["language"] = $entityManager->getRepository('YilinkerCoreBundle:Language')->find($request->get('language'));
            }

            $accountManager = $this->get("yilinker_core.service.account_manager");
            $user = $accountManager->mapUser($data, $userType, $storeType);

            if($user instanceof User){

                $token = array();
                if(!$request->isXmlHttpRequest()){
                   $token = $this->generateAccessToken($request);
                }

                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Successfully registered!",
                    "data" => $token
                ), 200);

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

    /**
     * Handles Request For Reset Password
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="User",
     *     statusCodes={
     *         200={
                    "true"
     *         },
     *         400={
     *             "Field errors"
     *         },
     *     },
     *     parameters={
     *         {"name"="verificationCode", "dataType"="string", "required"=true, "description"="Verification Code"},
     *         {"name"="newPassword", "dataType"="string", "required"=true, "description"="New password to be set."},
     *         {"name"="storeType", "dataType"="int", "required"=false, "description"="0 for seller, 1 for affiliate. Not required if buyer, defaults to affiliate. "},
     *     },
     *     views = {"user", "default", "v2"}
     * )
     */
    public function resetPasswordAction (Request $request)
    {
        $storeType = $request->get("storeType", Store::STORE_TYPE_RESELLER);
        $areaCode = $request->get("areaCode", "63");

        $formData = array(
            "plainPassword" => array(
                "first" => $request->get("newPassword", null),
                "second" => $request->get("newPassword", null)
            ),
            "verificationCode" => $request->get("verificationCode", null),
        );

        $em = $this->getDoctrine()->getManager();

        $kernel = $this->get("kernel");
        if ($kernel->getName() == 'frontend') {
            $userType = User::USER_TYPE_BUYER;
        }
        elseif ($kernel->getName() == 'merchant') {
            $userType = User::USER_TYPE_SELLER;
        }

        $user = $this->getDoctrine()
                     ->getManager()
                     ->getRepository("YilinkerCoreBundle:User")
                     ->loadUserByOneTimePassword(
                        $formData["verificationCode"],
                        $userType == User::USER_TYPE_SELLER,
                        $storeType == Store::STORE_TYPE_RESELLER
                    );

        $form = $this->transactForm('core_reset_password', null, $formData, array(
            "csrf_protection" => false,
            "storeType" => $storeType,
            "mustVerify" => true,
            "contactNumber" => !is_null($user)? $user->getContactNumber() : null,
            "token" => $formData["verificationCode"],
            "user" => $user,
            "type" => OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD,
            "areaCode" => $areaCode
        ));

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $passwordEncoder = $this->get('security.encoder_factory')->getEncoder($user);

        if($form->isValid()){
            $data = $form->getData();

            $accountManager = $this->get("yilinker_core.service.account_manager");
            $isOldPassword =  $passwordEncoder->isPasswordValid($user->getPassword(), $request->get("newPassword", null), null);

            if($isOldPassword){
                return $formErrorService->throwCustomErrorResponse(array("Password is same to the old password.."), "Password is same to the old password..");
            }

            $isValid = $accountManager->resetPassword($data, $user, $userType, $storeType);
            if($isValid){
                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Password successfully reset.",
                    "data" => array()
                ), 200);
            }
        }

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
