<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OTP extends Constraint
{
    public $message = "Confirmation code is either invalid or expired.";
    private $options = array();

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;   
    }

    public function validatedBy()
    {
        return 'otp';
    }
}