<?php

namespace Yilinker\Bundle\CoreBundle\Services\SMS;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Carbon\Carbon;

class SmsService
{
    private $em;

    /**
     * The SMS Sender Library
     *
     * @var Yilinker\Bundle\CoreBundle\Services\SMS\Senders\SemephoreSms
     */
    private $smsSender;

    /**
     * The user verification service
     *
     * @var Yilinker\Bundle\CoreBundle\Services\User\Verification
     */
    private $userVerificationService;

    /**
     * View templater
     *
     * @var Symfony\Bundle\FrameworkBundle\Templating
     */
    private $twigTemplater;

    /**
     * Constructor
     *
     * @param Yilinker\Bundle\CoreBundle\Services\SMS\Senders\SemephoreSms $smsSender
     * @param Yilinker\Bundle\CoreBundle\Services\User\Verification
     * @param Symfony\Bundle\FrameworkBundle\Templating
     */
    public function __construct(
        $em, 
        $smsSender, 
        $userVerificationService, 
        $twigTemplater
    ){
        $this->em = $em;
        $this->smsSender = $smsSender;
        $this->userVerificationService = $userVerificationService;
        $this->twigTemplater = $twigTemplater;
    }

    /**
     * Send the user verification code
     *
     * @param User $user
     * @return array
     */
    public function sendUserVerificationCode($user, $contactNumber = null)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Invalid contact number.',
            'data'         => array(),
        );

        $contactNumber = $contactNumber !== null ? $contactNumber : $user->getContactNumber();
        if(strlen($contactNumber) > 0 && null !== $contactNumber){

            $this->userVerificationService->createVerificationToken($user, $contactNumber, UserVerificationToken::TYPE_CONTACT_NUMBER);

            if(!is_null($user)){
                $newVerificationCode = $user->getVerificationToken(UserVerificationToken::TYPE_CONTACT_NUMBER);
            }
            else{
                $userVerificationRepository = $this->em->getRepository("YilinkerCoreBundle:UserVerificationToken");
                $userVerificationToken = $userVerificationRepository->findBy(array(
                                            "user" => null,
                                            "field" => $contactNumber
                                        ),
                                        array(
                                            "dateAdded" => "DESC"
                                        ), 1);

                $newVerificationCode = $userVerificationToken? $userVerificationToken[0]->getToken() : null;
            }

            $message = $this->twigTemplater->render(
                'YilinkerCoreBundle:SMS:user_verification_code.html.twig',
                array(
                    'verificationCode' => $newVerificationCode,
                    'expirationInMinutes' => Verification::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                )
            );

            try{
                $response['isSuccessful'] = true;
                $response['message'] = 'Verification code sent to '.$contactNumber;
                $this->smsSender->setMessage($message);
                $this->smsSender->setMobileNumber($contactNumber);
                $this->smsSender->sendSMS();
            }
            catch(\Exception $e){
                $response['isSuccessful'] = false;
                $response['message'] = 'SMS Provider is currently unavailable';
            }
        }

        return $response;
    }

    /**
     * Send the user forgot password verification code
     *
     * @param User $user
     * @return array
     */
    public function sendUserForgotPasswordCode(User $user)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'Invalid contact number.',
            'data' => array(),
        );

        $contactNumber = $user->getContactNumber();
        if("" !== $contactNumber && null !== $contactNumber){

            $this->userVerificationService->createForgotPasswordCode($user);
            $newVerificationCode = $user->getForgotPasswordCode();

            $message = $this->twigTemplater->render(
                'YilinkerCoreBundle:SMS:user_forgot_password_code.html.twig',
                array(
                    'verificationCode' => $newVerificationCode,
                    'expirationInMinutes' => Verification::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                )
            );

            try{
                $response['isSuccessful'] = true;
                $response['message'] = 'Verification code sent to '.$contactNumber;
                $this->smsSender->setMessage($message);
                $this->smsSender->setMobileNumber($contactNumber);
                $response['data'] = $this->smsSender->sendSMS();
            }
            catch(\Exception $e){
                $response['isSuccessful'] = false;
                $response['message'] = 'SMS Provider is currently unavailable';
            }
        }

        return $response;
    }

    /**
     * Send Accreditation notification
     *
     * @param $contactNumber
     * @param $message
     * @return mixed
     */
    public function sendAccreditationNotification ($contactNumber, $message)
    {

        try {
            $response['isSuccessful'] = true;
            $response['message'] = 'Notification sent to ' . $contactNumber;
            $this->smsSender->setMessage($message);
            $this->smsSender->setMobileNumber($contactNumber);
            $response['data'] = $this->smsSender->sendSMS();
        }
        catch (\Exception $e) {
            $response['isSuccessful'] = false;
            $response['message'] = 'SMS Provider is currently unavailable';
        }

        return $response;
    }

}
