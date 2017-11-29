<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AlphaNumUnderscoreValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);
        if (!preg_match('/^\w+$/', $value, $matches)) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }
}
