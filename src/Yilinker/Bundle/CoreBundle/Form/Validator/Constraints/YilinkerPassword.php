<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class YilinkerPassword extends Constraint
{
    public $message = 'Password must contain at least one number and minimum of 8 alphabetic characters';

    public function validatedBy()
    {
        return 'yilinker_password';
    }
}
