<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueUsername extends Constraint
{
    public $message = 'Username "%string%" already exists.';

    public function validatedBy()
    {
        return 'unique_username';
    }
}
