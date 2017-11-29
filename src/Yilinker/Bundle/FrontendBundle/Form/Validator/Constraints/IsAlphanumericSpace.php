<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsAlphanumericSpace extends Constraint
{
    public $message = 'The string "%string%" contains an illegal character: it can only contain letters, numbers and space';
}
