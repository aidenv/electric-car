<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidRecipient extends Constraint
{
    public $message = 'Invalid Recipient';

    public function validatedBy()
    {
        return 'valid_recipient';
    }
}
