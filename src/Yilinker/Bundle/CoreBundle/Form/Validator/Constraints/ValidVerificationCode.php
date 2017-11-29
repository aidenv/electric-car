<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */

class ValidVerificationCode extends Constraint
{
    public $message = "Verification code is either invalid or expired.";

    private $excludedUserId;
    private $options;

    public function __construct($options = [])
    {
        $this->options = array_merge(
            array('message' => $this->message),
            $options
        );

        $this->excludedUserId = null;
        if (isset($options['excludedUserId'])) {
            $this->excludedUserId = $options['excludedUserId'];
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function validatedBy()
    {
        return 'valid_verification_code';
    }
}
