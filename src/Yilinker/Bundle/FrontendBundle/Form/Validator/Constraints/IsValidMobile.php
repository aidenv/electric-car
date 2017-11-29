<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class IsValidMobile extends Constraint
{
    public $message = 'Mobile number must be 11 digits longs';
}
