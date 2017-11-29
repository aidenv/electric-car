<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidTargetType extends Constraint
{
    public $message = 'Invalid Target';

    public function validatedBy()
    {
        return 'valid_target_type';
    }
}
