<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AlphaNumUnderscore extends Constraint
{
    public $message = 'Field contains invalid chars.';

    public function validatedBy()
    {
        return 'alphanum_underscore';
    }
}
