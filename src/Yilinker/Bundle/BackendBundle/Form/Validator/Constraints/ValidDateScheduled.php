<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidDateScheduled extends Constraint
{
    public $message = 'Date schedule should be ahead of current date and time.';

    public $options = array();

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    public function validatedBy()
    {
        return 'valid_date_scheduled';
    }
}
