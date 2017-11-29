<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsTinEditableValidator extends ConstraintValidator
{
    public function validate($tin, Constraint $constraint)
    {
        $tin = trim($tin);
        
        $options = $constraint->getOptions();
        $user = array_key_exists("user", $options)? $options["user"] : null;

        if($tin && $user && $user->getTin() != $tin){

            $accreditationApplication = $user->getAccreditationApplication();

            if(
                (
                    $accreditationApplication && 
                    !$accreditationApplication->getIsBusinessEditable() &&
                    $user->getTin()
                ) ||
                !preg_match('/^\d+$/', $tin)
            ){
                $this->context
                     ->buildViolation($constraint->message)
                     ->addViolation();
            }
        }
    }

}