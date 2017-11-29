<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class IsValidMobileValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if(!( $value == ''  || preg_match('/^\d{11}$/', $value) )){
            $this->context->addViolation(
                $constraint->message,
                array('%string%' => $value)
            );
        }
    }
}
