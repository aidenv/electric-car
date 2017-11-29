<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\Country;


class ValidContactNumberValidator extends ConstraintValidator
{
    private $countryRepository;

    public function __construct(EntityRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function validate($contactNumber, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $areaCode = array_key_exists("areaCode", $options)? $options["areaCode"] : "63";
        $country = $this->countryRepository->findOneBy(array(
            "areaCode" => $areaCode
        ));

        $isValid = false;
        if($country){
            switch($country->getCode()) {
                case Country::COUNTRY_CODE_PHILIPPINES:                
                    if($contactNumber[0] == '0'){
                        $contactNumber = substr($contactNumber, 1, strlen($contactNumber));
                    }
                    
                    if(!(0 === preg_match('/^(8|9)[0-9]{9}$/', $contactNumber))){
                        $isValid = true;
                    }
                
                    break;
                case Country::COUNTRY_CODE_CHINA:
                
                    if(preg_match('/^1[34578]\d{9}$/', $contactNumber)){
                        $isValid = true;
                    }

                    break;
                default:
                    break;
            }
        }
        
        if($contactNumber && !preg_match('/^\d+$/', $contactNumber)){
            $isValid = false;
        }
        
        if(!$isValid && $contactNumber){
            $this->context->addViolation(
                $constraint->message,
                array('%string%' => $contactNumber)
            );
        }
    }

}
