<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken;
use Yilinker\Bundle\CoreBundle\Entity\SmsNewsletterSubscription;
use Yilinker\Bundle\CoreBundle\Entity\EmailNewsletterSubscription;

class Verification
{
    const VERIFICATION_CODE_EXPIRATION_IN_MINUTES = 5;

    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Creates verification token for the unverified user
     * Used for email links
     *
     * @param $user
     * @param string $fieldValue
     * @param int $type
     * @return Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken
     */
    public function createVerificationToken($user, $fieldValue, $type = UserVerificationToken::TYPE_EMAIL)
    {
        if($type === UserVerificationToken::TYPE_EMAIL){
            $expiration = Carbon::now()->addDay();
            $hash = sha1(uniqid('yilnker').$user->getEmail().uniqid('online'));
        }
        else{
            $expiration = Carbon::now()->addMinutes(self::VERIFICATION_CODE_EXPIRATION_IN_MINUTES);
            $hash = mt_rand(100000, 999999);
        }
        $dateNow = new \DateTime();
        $verificationToken = new UserVerificationToken();
        $verificationToken->setField($fieldValue);
        $verificationToken->setToken($hash);
        $verificationToken->setDateAdded($dateNow);
        $verificationToken->setDateLastModified($dateNow);
        $verificationToken->setUser($user);
        $verificationToken->setTokenExpiration($expiration);
        $verificationToken->setIsActive(true);
        $verificationToken->setTokenType($type);

        $this->em->persist($verificationToken);
        $this->em->flush();

        if(!is_null($user)){
            $user->addUserVerificationToken($verificationToken);
        }

        return $verificationToken;
    }

    /**
     * Confirms the email verification of the user
     *
     * @param User $user
     * @param string $token
     * @param string $type
     * @ return bool
     */
    public function confirmVerificationToken($user, $token, $type = UserVerificationToken::TYPE_EMAIL)
    {
        $timeNow = Carbon::now()->getTimestamp();

        $verificationToken = $this->em-> getRepository('YilinkerCoreBundle:UserVerificationToken')
                                  ->findOneBy(array(
                                      'token'     => $token,
                                      'isActive'  => true,
                                      'tokenType' => $type,
                                      'user'      => $user,
                                  ));

        if($verificationToken){
            $tokenExpiration = $verificationToken->getTokenExpiration()->getTimestamp();
            if($timeNow <= $tokenExpiration){
                if($type === UserVerificationToken::TYPE_EMAIL){

                    $user->setIsEmailVerified(true);

                    $emailSubscription = new EmailNewsletterSubscription();
                    $emailSubscription->setEmail($verificationToken->getField());
                    $emailSubscription->setUserId($user->getUserId());
                    $emailSubscription->setDateCreated(Carbon::now());
                    $emailSubscription->setDateLastModified(Carbon::now());
                    $emailSubscription->setIsActive(true);
                    $this->em->persist($emailSubscription);
                }
                else{
                    if(!is_null($user)){
                        $user->setContactNumber($verificationToken->getField());
                        $user->setIsMobileVerified(true);
                        $this->createSmsSubscription($verificationToken->getField(), $user);
                    }
                }

                if(!is_null($user)){
                    $activeVerificationTokens = $user->getActiveUserVerificationTokens($type);
                }
                else{
                    $userVerificationTokenRepository = $this->em->getRepository("YilinkerCoreBundle:UserVerificationToken");

                    $activeVerificationTokens = $userVerificationTokenRepository->findBy(array(
                                                "field" => $verificationToken->getField(),
                                                "token" => $verificationToken->getToken(),
                                                "user" => null
                                            ));
                }

                foreach($activeVerificationTokens as $token){
                    $token->setIsActive(false);
                }

                $verificationToken->setIsActive(false);
                $this->em->flush();

                return true;
            }
        }

        return false;
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

    /**
     * Creates forgot password verification code for the unverified user
     * Used for SMS verification
     *
     * @param User $user
     */
    public function createForgotPasswordCode(User $user)
    {
        $verificationCode = mt_rand(100000, 999999);

        $user->setForgotPasswordCode($verificationCode)
             ->setForgotPasswordCodeExpiration(Carbon::now()->addMinutes(self::VERIFICATION_CODE_EXPIRATION_IN_MINUTES));

        $this->em->flush();
    }

    /**
     * Creates reactivation code for the user
     *
     * @param User $user
     */
    public function createReactivationCode(User $user)
    {
        $reactivationCode = sha1(uniqid('yilinker').$user->getUserId().$user->getEmail().uniqid('reactivation'));
        $user->setReactivationCode($reactivationCode);

        $this->em->flush();
    }

}
