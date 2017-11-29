<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AlphaSpaceValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if(!is_null($value)){
            
            $value = trim($value);

            if (!preg_match('/^[a-zA-Z]+([ -]*?[a-zA-Z]+)*$/', $value, $matches)) {
                $this->context
                     ->buildViolation($constraint->message)
                     ->setParameter('%string%', $value)
                     ->addViolation();
            }
        }
    }
}
