<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Carbon\Carbon;

class ValidDateEndValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->options;
        $format = array_key_exists("format", $options)? $options["format"] : "m/d/Y (H:i:s)";

        $dateScheduled = array_key_exists("dateStart", $options)? 
                            Carbon::createFromFormat($format, $options["dateStart"]) : 
                            Carbon::now();

        $dateEnd = Carbon::createFromFormat($format, $value);

        if ($dateScheduled->gt($dateEnd)) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }

}
