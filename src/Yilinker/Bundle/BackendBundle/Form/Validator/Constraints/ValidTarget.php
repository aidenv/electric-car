<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidTarget extends Constraint
{
    public $message = 'Invalid Target Parameter';

    public $options;

    public function __construct($options){
    	$this->options = $options;
    }

    public function getOptions(){
    	return $this->options;
    }

    public function validatedBy()
    {
        return 'valid_target';
    }
}
