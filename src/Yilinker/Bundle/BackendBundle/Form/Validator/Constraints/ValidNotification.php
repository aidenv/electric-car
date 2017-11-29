<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidNotification extends Constraint
{
    public $message = 'Notification does not exists.';

    public $uneditable = 'Sent notifications are uneditable.';

    public $options;

    public function __construct($options){
    	$this->options = $options;
    }

    public function getOptions(){
    	return $this->options;
    }

    public function validatedBy()
    {
        return 'valid_notification';
    }
}
