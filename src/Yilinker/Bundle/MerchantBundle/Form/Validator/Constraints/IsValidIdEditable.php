<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class IsValidIdEditable extends Constraint
{
    public $message = "Unable to update valid ID.";

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
        return 'is_valid_id_editable';
    }
}