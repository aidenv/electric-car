<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidVariants extends Constraint
{
    private $options;

    public $message = "Invalid variant format.";

    public $variantsNotFound = "Translated variants are not found in this unit.";

    public $noAttributeNameExists = "The default value for some attribute names does not exists.";

    public $notEqualAttributeValues = "The translated attribute values is not equal to the default attribute values.";

    public $attributeValueDoesNotExists = "The translated attribute value does not exists on this attribute name.";

    public function __construct ($options = array())
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function validatedBy()
    {
        return 'valid_variants';
    }
}