<?php
namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidSlugValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if(!is_null($value) OR $value != ""){
            $value = trim($value);
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value, $matches)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('%string%', $value)
                    ->addViolation();
            }
        }
    }
}
