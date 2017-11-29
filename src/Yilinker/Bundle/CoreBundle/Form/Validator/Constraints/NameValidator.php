<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NameValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if($value !== null){
            if (!preg_match("/^[a-zA-z0-9 _\-'.,]*$/", $value, $matches)) {
                $this->context
                     ->buildViolation($constraint->message)
                     ->setParameter('%string%', $value)
                     ->addViolation();
            }
        }
    }
}
