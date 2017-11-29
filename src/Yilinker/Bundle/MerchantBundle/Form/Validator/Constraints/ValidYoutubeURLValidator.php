<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidYoutubeURLValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (
            trim($value) != "" &&
            !preg_match("/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/", $value)
        ){
            $this->context
                 ->buildViolation($constraint->message)
                 ->addViolation();
        }
    }

}