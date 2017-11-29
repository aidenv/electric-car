<?php

namespace Yilinker\Bundle\CoreBundle\Services\SMS;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Entity\SmsNewsletterSubscription;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Services\SMS\SmsSender;

class OneTimePasswordService
{
    const VERIFICATION_CODE_EXPIRATION_IN_MINUTES = 60;

    const OTP_TYPE_REGISTER = "register";

    const OTP_TYPE_FORGOT_PASSWORD = "forgot-password";

    const OTP_TYPE_CHECKOUT = "checkout";

    const OTP_TYPE_GUEST_CHECKOUT = "guest_checkout";

    const OTP_TYPE_PAYOUT_REQUEST = "withdrawal";

    const OTP_TYPE_VERIFY_CONTACT_NUMBER = "verify_contact_number";

    const OTP_TYPE_CHANGE_CONTACT_NUMBER = "change-contact-number";

    /**
     * Symfony service container
     */
    private $container;

    private $smsSender;

    private $em;

    public function getUnauthenticatedTokenTypes()
    {
        return array(
            self::OTP_TYPE_REGISTER,
            self::OTP_TYPE_FORGOT_PASSWORD,
            self::OTP_TYPE_GUEST_CHECKOUT
        );
    }

    public function getAuthenticatedTokenTypes()
    {
        return array(
            self::OTP_TYPE_CHECKOUT,
            self::OTP_TYPE_PAYOUT_REQUEST,
            self::OTP_TYPE_CHANGE_CONTACT_NUMBER,
            self::OTP_TYPE_VERIFY_CONTACT_NUMBER
        );
    }

    public function getUniqueContactNumberTokenTypes()
    {
        return array(
            self::OTP_TYPE_REGISTER,
            self::OTP_TYPE_CHANGE_CONTACT_NUMBER,
            self::OTP_TYPE_CHECKOUT
        );
    }

    public function getMustBeRegisteredTokenTypes()
    {
        return array(
            self::OTP_TYPE_FORGOT_PASSWORD
        );
    }

    public function getImmutableTokenTypes()
    {
        return array(
            self::OTP_TYPE_CHANGE_CONTACT_NUMBER,
            self::OTP_TYPE_CHECKOUT,
            self::OTP_TYPE_VERIFY_CONTACT_NUMBER
        );
    }

    public function getImmutableUser()
    {
        return array(
            self::OTP_TYPE_GUEST_CHECKOUT
        );
    }

    /**
     * Set the entityManager
     *
     */
    public function setContainer($container)
    {
        $this->container = $container;
        $this->em = $container->get("doctrine.orm.entity_manager");
        $this->smsSender = $container->get("yilinker_core.service.sms.sender");
    }

    /**
     * Send the user verification code
     *
     * @param User $user
     * @param Country $country
     * @param string $contactNumber
     * @param int $type
     * @param boolean $throwError
     * @return array
     */
    public function sendOneTimePassword(
        $user = null,
        $country = null,
        $contactNumber = null,
        $type = self::OTP_TYPE_REGISTER,
        $throwError = false
    ){
        if(strlen($contactNumber) > 0 && !is_null($contactNumber)){

            $this->em->beginTransaction();

            try{

                $rawContactNumber = $this->formatContactNumber($country->getCode(), $contactNumber);
                $oneTimePasswordRepository = $this->em->getRepository("YilinkerCoreBundle:OneTimePassword");
                $lastOtpRequests = $oneTimePasswordRepository->findBy(array(
                                    "contactNumber" => $rawContactNumber,
                                    "country" => $country,
                                    "tokenType" => $type,
                                    "isActive" => true
                                ), array(
                                    "dateAdded" => "DESC"
                                ), 1);

                $lastOtpRequest = !empty($lastOtpRequests)? array_shift($lastOtpRequests) : null;
               
                if(
                    $lastOtpRequest &&
                    Carbon::instance($lastOtpRequest->getDateAdded())->diffInMinutes(Carbon::now()) < 1
                ){                	
                    throw new YilinkerException("You can only send a confirmation once per minute.");
                }

                $this->smsSender->setMode(
                    $country->getAreaCode()== Country::AREA_CODE_CHINA ?
                    SmsSender::SMS_MODE_CN : SmsSender::SMS_MODE_BROADCAST
                );
                $this->smsSender->getService($country->getAreaCode(), null);             
                $expiration = Carbon::now()->addMinutes(self::VERIFICATION_CODE_EXPIRATION_IN_MINUTES);
                $hash = mt_rand(100000, 999999);

                $dateNow = Carbon::now();
                
                $oneTimePassword = new OneTimePassword();
                $oneTimePassword->setCountry($country);
                $oneTimePassword->setContactNumber($contactNumber);
                $oneTimePassword->setToken($hash);
                $oneTimePassword->setDateAdded($dateNow);
                $oneTimePassword->setDateLastModified($dateNow);
                $oneTimePassword->setUser($user);
                $oneTimePassword->setTokenExpiration($expiration);
                $oneTimePassword->setIsActive(true);
                $oneTimePassword->setTokenType($type);
                $areaCode = $country->getAreaCode();
                
                if($areaCode == Country::AREA_CODE_PHILIPPINES){
	                $message = $this->container->get("templating")->render(
	                    'YilinkerCoreBundle:SMS:user_verification_code.html.twig',
	                    array(
	                        'verificationCode' => $hash,
	                        'expirationInMinutes' => self::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
	                    )
	                );
                }
                else {
                	$message = $this->container->get("templating")->render(
                        'YilinkerCoreBundle:SMS:user_verification_code_cn.html.twig',
                        array(
                            'verificationCode' => $hash,
                            'expirationInMinutes' => self::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                        )
                	);
                	$message = '19190#'.$message;
                }
                
                $this->smsSender->sendMessage(
                    $message,
                    $contactNumber,
                    $country,
                    $oneTimePassword,
                    "setProvider"
                );

                $this->em->flush();
                $this->em->commit();

                return $oneTimePassword;
            }
            catch(YilinkerException $e){
                $this->em->rollback();
                return $e->getMessage();
            }
        }

        return false;
    }

    public function confirmOneTimePassword(
        $user = null,
        $contactNumber = null,
        $token = null,
        $type = self::OTP_TYPE_REGISTER,
        $setToInactive = true,
        $oneTimePassword = null,
        $countryCode = "PH"
    ){
        $oneTimePasswordRepository = $this->em->getRepository("YilinkerCoreBundle:OneTimePassword");

        if ($type == self::OTP_TYPE_GUEST_CHECKOUT) {
            $user = null;
        }

        $contactNumber = $this->formatContactNumber($countryCode, $contactNumber);
        if(!is_null($user) && is_null($contactNumber)){
            $contactNumber = $user->getContactNumber();
        }

        if(is_null($oneTimePassword)){
            $oneTimePassword = $oneTimePasswordRepository->findOneBy(array(
                                "user" => $user,
                                "contactNumber" => $contactNumber,
                                "token" => $token,
                                "tokenType" => $type,
                                "isActive" => true
                            ));
           
            if(!$oneTimePassword){            	
                return false;
            }
        }

        $tokenExpiration = $oneTimePassword->getTokenExpiration();
        if(Carbon::now()->gt(Carbon::instance($tokenExpiration))){
            return false;
        }

        if($type != self::OTP_TYPE_CHANGE_CONTACT_NUMBER){
            $oneTimePasswords = $oneTimePasswordRepository->findBy(array(
                                "user" => $user,
                                "contactNumber" => $contactNumber,
                                "tokenType" => $type
                            ));
        }
        else{
            $oneTimePasswords = $oneTimePasswordRepository->findBy(array(
                                "user" => $user,
                                "tokenType" => $type
                            ));

            $userRepository = $this->em->getRepository("YilinkerCoreBundle:User");
            $users = $userRepository->findUserByContactNumber(
                        $contactNumber,
                        $user->getUserId(),
                        $user->getUserType(),
                        $user->getStore()? $user->getStore()->getStoreType() : null
                    );

            if(!$users){
                $user->setContactNumber($contactNumber)
                     ->setIsMobileVerified(true);
            }
            else{
                return false;
            }
        }

        if(!$oneTimePasswords){
            return false;
        }

        if($setToInactive){
            foreach ($oneTimePasswords as $otp) {
                $otp->setIsActive(false);
            }
        }

        $this->em->flush();
        $this->confirmedHooks($oneTimePassword);

        return $oneTimePassword;
    }

    /**
     * function to execute post confirmed otp functions
     */
    public function confirmedHooks($oneTimePassword)
    {
        switch ($oneTimePassword->getTokenType()) {
            case self::OTP_TYPE_VERIFY_CONTACT_NUMBER:
                $user = $oneTimePassword->getUser();
                $user->setContactNumber($oneTimePassword->getContactNumber());
                $user->setIsMobileVerified(true);
                $this->em->flush();

                return;
            case self::OTP_TYPE_REGISTER:
                $user = $oneTimePassword->getUser();
                if ($user) {
                    $sms = $this->container->get('yilinker_core.service.sms.semaphore_sms');
                    $sms->setMobileNumber($oneTimePassword->getContactNumber());
                    $sms->setMessage("Hi ".$user->getFullName().", you have successfully verified your mobile number. We'll be using this to send you one-time passwords for your transactions as well as other important notifications. Thank you.");
                    $sms->sendSMS();
                }

                return;
            case self::OTP_TYPE_FORGOT_PASSWORD:
                $user = $oneTimePassword->getUser();
                if ($user) {
                    $sms = $this->container->get('yilinker_core.service.sms.semaphore_sms');
                    $sms->setMobileNumber($oneTimePassword->getContactNumber());
                    $sms->setMessage("Hi ".$user->getFullName().", you have successfully changed your password.");
                    $sms->sendSMS();
                }

                return;
        }
    }

    public function createSmsSubscription($contactNumber, $user)
    {
        $smsSubscription = new SmsNewsletterSubscription();
        $smsSubscription->setContactNumber($contactNumber);
        $smsSubscription->setUserId($user->getUserId());
        $smsSubscription->setDateCreated(Carbon::now());
        $smsSubscription->setDateLastModified(Carbon::now());
        $smsSubscription->setIsActive(true);
        $this->em->persist($smsSubscription);
    }

    public function getSmsSender($user, $country, $contactNumber, $type, $isActive)
    {
        $oneTimePasswordRepository = $this->em->getRepository("YilinkerCoreBundle:OneTimePassword");
        $entries = $oneTimePasswordRepository->getOneTimePasswordEntryCount(
                    $user,
                    $country,
                    $this->formatContactNumber($country->getCode(), $contactNumber),
                    $type,
                    $isActive,
                    Carbon::now()->subMinutes(5)
                );

        $areaCode = $country ? $country->getAreaCode(): '63';

        return $this->smsSender->getService($areaCode, $entries);
    }

    public function validateTokenType($type, $contactNumber, $userType, $storeType)
    {
        $user = null;
        $unauthenticatedTypes = $this->getUnauthenticatedTokenTypes();
        $authenticatedTypes = $this->getAuthenticatedTokenTypes();
        $uniqueContactNumberTypes = $this->getUniqueContactNumberTokenTypes();
        $immutableTypes = $this->getImmutableTokenTypes();
        $mustBeRegisteredTypes = $this->getMustBeRegisteredTokenTypes();
        $userRepository = $this->em->getRepository("YilinkerCoreBundle:User");

        if(in_array($type, $authenticatedTypes)){
            $authorizationChecker = $this->container->get("security.authorization_checker");
            if($authorizationChecker->isGranted("IS_AUTHENTICATED_FULLY")){
                $user = $this->container->get('security.token_storage')->getToken()->getUser();

                $duplicate = $userRepository->loadUserByContactOrEmail(
                    $contactNumber,
                    $userType == User::USER_TYPE_SELLER,
                    $storeType == Store::STORE_TYPE_RESELLER,
                    $user->getUserId()
                );

                if(in_array($type, $uniqueContactNumberTypes)){
                    if($duplicate || is_null($contactNumber)){
                        return array(
                            "isSuccessful" => false,
                            "message" => "Contact number already exists.",
                            "data" => array(
                                "errors" => array("Contact number already exists.")
                            )
                        );
                    }
                }

                if(!in_array($type, $immutableTypes)){
                    $contactNumber = $user->getContactNumber();
                }
            }
            else{
                return array(
                    "isSuccessful" => false,
                    "message" => "Unauthorized request.",
                    "data" => array(
                        "errors" => array("Unauthorized request.")
                    )
                );
            }
        }
        elseif(in_array($type, $unauthenticatedTypes)){

            $user = $userRepository->loadUserByContactOrEmail(
                $contactNumber,
                $userType == User::USER_TYPE_SELLER,
                $storeType == Store::STORE_TYPE_RESELLER
            );

            if(in_array($type, $uniqueContactNumberTypes)){
                if($user || is_null($contactNumber)){
                    return array(
                        "isSuccessful" => false,
                        "message" => "Contact number already exists.",
                        "data" => array(
                            "errors" => array("Contact number already exists.")
                        )
                    );
                }
            }
            elseif(in_array($type, $mustBeRegisteredTypes)){
                if(is_null($user) || is_null($contactNumber)){
                    return array(
                        "isSuccessful" => false,
                        "message" => "Account does not exists.",
                        "data" => array(
                            "errors" => array("Account does not exists.")
                        )
                    );
                }
            }
        }
        else{
            return array(
                "isSuccessful" => false,
                "message" => "Invalid token type request.",
                "data" => array(
                    "errors" => array("Invalid token type request.")
                )
            );
        }

        $user = in_array($type, $this->getImmutableUser())? null : $user;

        return array(
            "isSuccessful" => true,
            "message" => "Token is valid",
            "data" => array(
                "mutable" => in_array($type, $this->getImmutableTokenTypes())? false : true,
                "user" => $user,
                "contactNumber" => $contactNumber
            )
        );
    }

    private function formatContactNumber($countryCode, $contactNumber)
    {
        switch($countryCode){
            case Country::COUNTRY_CODE_PHILIPPINES:
                if(strlen($contactNumber) == 10){
                    return "0".$contactNumber;
                }
                break;
        }

        return $contactNumber;
    }
}
