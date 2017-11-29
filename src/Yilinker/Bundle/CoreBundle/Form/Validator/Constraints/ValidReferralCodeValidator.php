<?php
namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class ValidReferralCodeValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct($userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($referralCode, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $message = $constraint->message;

        $isValid = false;
        
        $user = array_key_exists("user", $options)? $options["user"] : null;
        $userType = array_key_exists("userType", $options)? $options["userType"] : null;
        $storeType = array_key_exists("storeType", $options)? $options["storeType"] : null;

        $referrer = $this->userRepository->getUserByReferralCode($referralCode, (!is_null($user)? $user->getUserId() : null));

        $referralCodeOwner = $this->userRepository->findOneByReferralCode($referralCode);

        $userReferrer = ($user && $user->getUserReferral())? $user->getUserReferral()->getReferrer() : null;

        if($user && $user->getUserReferral() && $userReferrer !== $referralCodeOwner){
            $message = $constraint->hasBeenReferred;
        }
        elseif(!$user || is_null($user->getUserReferral())) {
            if ($referrer instanceof User && !is_null($userType) && !is_null($storeType)) {

                $referrerType = $referrer->getUserType();

                $referrerStoreType = is_null($referrer->getStore()) ? null : $referrer->getStore()->getStoreType();

                //buyer to buyer
                if ($referrerType == User::USER_TYPE_BUYER && $userType == User::USER_TYPE_BUYER) {
                    $isValid = true;
                }
                elseif ($referrerType == User::USER_TYPE_BUYER && $user && $user->getUserType() == User::USER_TYPE_BUYER) {
                    $isValid = true;
                }
                //affiliate to affiliate
                else if ($referrerStoreType == Store::STORE_TYPE_RESELLER && $storeType == Store::STORE_TYPE_RESELLER) {
                    $isValid = true;
                }
                //affiliate to buyer
                else if ($referrerStoreType == Store::STORE_TYPE_RESELLER && $userType == User::USER_TYPE_BUYER) {
                    $isValid = true;
                }
                //seller to buyer
                else if ($referrerType == User::USER_TYPE_SELLER && $userType == User::USER_TYPE_BUYER) {
                    $isValid = true;
                }
            }
        }
        else{
            $isValid = true;
        }

        if(!$isValid && !is_null($referralCode)){
            $this->context
                ->buildViolation($message)
                ->addViolation();
        }
    }
}
