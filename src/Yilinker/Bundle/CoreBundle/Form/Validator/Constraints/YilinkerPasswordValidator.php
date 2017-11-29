<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class YilinkerPasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);

        if (!preg_match('/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9!*_-]+)$/', $value, $matches)) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }
}
