<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class IsTinEditable extends Constraint
{
    public $message = "Unable to update tin.";

    private $options = array();

    public function __construct ($options = array())
    {
        $this->options = $options;
    }

    public function getOptions ()
    {
        return $this->options;
    }

    public function validatedBy()
    {
        return 'is_tin_editable';
    }
}