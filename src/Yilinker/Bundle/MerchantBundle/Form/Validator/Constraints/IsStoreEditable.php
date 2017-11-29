<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class IsStoreEditable extends Constraint
{
    public $message = "Unable to update %type%.";

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
        return 'is_store_editable';
    }
}