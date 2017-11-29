<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Carbon\Carbon;
use \DateTime;

class ValidDateScheduledValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->options;

        $format = array_key_exists("format", $options)? $options["format"] : "m/d/Y (H:i:s)";

        $dateNow = Carbon::now();

        $dateScheduled = Carbon::createFromFormat($format, $value);

        if ($dateNow->gt($dateScheduled)) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }

    }

}
