<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AlphaSpace extends Constraint
{
    public $message = 'Field contains invalid characters.';

    public function validatedBy()
    {
        return 'alphaspace';
    }
}
