<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidReferralCode extends Constraint
{
    public $message = "Invalid Referral Code";

    public $hasBeenReferred = "You can only refer once per account";

    public $options = array();

    public function __construct($options = array())
    {
    	$this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function validatedBy()
    {
        return "valid_referral_code";
    }
}
