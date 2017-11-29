<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\Country;


class ValidVerificationCodeValidator extends ConstraintValidator
{
    private $oneTimePasswordRepository;
    private $userRepository;
    private $countryRepository;

    public function __construct(
        EntityRepository $oneTimePasswordRepository,
        EntityRepository $userRepository,
        EntityRepository $countryRepository
    ){
        $this->oneTimePasswordRepository = $oneTimePasswordRepository;
        $this->userRepository = $userRepository;
        $this->countryRepository = $countryRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();

        $isValid = true;
        if(array_key_exists("mustVerify", $options) && $options["mustVerify"]){

            $country = $this->countryRepository->findOneByAreaCode($options["areaCode"]);

            $contactNumber = $options["contactNumber"];
            $countryCode = $country ? $country->getCode() : Country::COUNTRY_CODE_PHILIPPINES;
            
            switch($countryCode){
                case Country::COUNTRY_CODE_PHILIPPINES:
                    if(strlen($options["contactNumber"]) == 10){
                        $contactNumber = "0".$options["contactNumber"];
                    }
                break;
            }

            $oneTimePassword = $this->oneTimePasswordRepository->findOneBy(array(
                "contactNumber" => $contactNumber,
                "token" => $value,
                "tokenType" => $options["type"],
                "user" => $options["user"],
                "isActive" => true
            ));

            if(
                is_null($oneTimePassword) || Carbon::now()->gt(Carbon::instance($oneTimePassword->getTokenExpiration()))
            ){
                $isValid = false;
            }
        }

        if($value == '' || !$isValid){
            $this->context->addViolation(
                $options["message"],
                array('%string%' => $value)
            );
        }
    }
}
