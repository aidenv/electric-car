<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidDateEnd extends Constraint
{
    public $message = 'End date should be later than the start date.';

    public $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function validatedBy()
    {
        return 'valid_end_date';
    }
}
