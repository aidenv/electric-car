<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidUnits extends Constraint
{
    public $message = "Units %string% are invalid";

    public $invalidRequestFormat = "Invalid JSON format"; //

    public $mustHaveAtleastOneUnit = "Product should have atleast 1 unit."; //

    public $mustHaveAtleastOneCombination = "%string% should have atleast 1 combination."; //

    public $mustHaveAtleastOneImage = "%string% should have atleast 1 image.";
    
    public $imageDoesntExists = "%string%' s image doesnt exists. Please reupload the image.";

    public $invalidDimensions = "Invalid dimensions on %string%"; //

    public $duplicateSku = "Duplicate SKU %string%"; //

    public $skuRequired = "Invalid SKU"; //

    public $invalidCombinationFormat = "Invalid combination format"; //

    public $duplicateAttribute = "Duplicate attribute"; //

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
        return 'valid_units';
    }
}