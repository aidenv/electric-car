<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsAlphanumericSpaceValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /**
         * Accept only string containing alphanumeric with spaces
         */
        if (!preg_match('/^[a-zA-Z0-9\s]*$/i', $value, $matches)) {
            $this->context->addViolation(
                $constraint->message,
                ['%string%' => $value]
            );
        }
    }
}
