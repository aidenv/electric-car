<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueStoreName extends Constraint
{
    public $message = "Store name %string% already exists.";

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
        return 'unique_store_name';
    }
}
