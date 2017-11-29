<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class UniqueContactNumberValidator extends ConstraintValidator
{
    private $userRepository;
    private $countryRepository;

    public function __construct(EntityRepository $userRepository, EntityRepository $countryRepository)
    {
        $this->userRepository = $userRepository;
        $this->countryRepository = $countryRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);

        $options = $constraint->getOptions();
        $userType = array_key_exists('userType', $options) ? $options['userType']: null;
        $storeType = array_key_exists('storeType', $options) ? $options['storeType']: null;
        $excludeUserId = array_key_exists("excludeUserId", $options)? $options["excludeUserId"] : null;
        $areaCode = array_key_exists("areaCode", $options)? $options["areaCode"] : "63";

        if (!$options['repoMethod']){
            $country = $this->countryRepository->findOneByAreaCode($areaCode);

            $contactNumber = $value;

            if($country){
                switch($country->getCode()){
                    case Country::COUNTRY_CODE_PHILIPPINES:
                        if(strlen($value) == 10){
                            $contactNumber = "0".$value;
                        }
                    break;
                }

                $users = $this->userRepository->findUserByContactNumber(
                            $contactNumber,
                            $excludeUserId,
                            $userType,
                            $storeType
                        );
            }
            else{
                $value = "";
            }
        }
        else {
            array_unshift($options['repoMethodArgs'], $excludeUserId);
            array_unshift($options['repoMethodArgs'], $value);
            $users = call_user_func_array(array($this->userRepository, $options['repoMethod']), $options['repoMethodArgs']);
        }

        if (strlen(trim($value)) > 0 && $users) {
            $this->context
                 ->buildViolation($options['message'])
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }
}
