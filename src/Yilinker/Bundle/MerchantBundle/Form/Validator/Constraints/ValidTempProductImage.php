<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidTempProductImage extends Constraint
{
    public $message = "Some images are not found please reupload the pictures again";

    public $hasMultiplePrimary = "You can only have one primary product image";

    public $mustHaveAtleastOnePrimary = "Please set a primary image for this product";

    public $invalidRequestFormat = "Invalid JSON format";

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
        return 'valid_temp_product_image';
    }
}