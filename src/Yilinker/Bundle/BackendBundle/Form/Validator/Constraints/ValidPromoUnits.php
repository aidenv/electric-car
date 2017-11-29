<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidPromoUnits extends Constraint
{
    public $message = 'Some products are invalid.';

    public $invalidPromoType = 'Promo type is invalid.';

    public $productsIsRequired = 'Promo should have atleast one product.';

    public $invalidDiscountedPrice = 'Price is invalid for %string%.';

    public $invalidSetDiscountedPrice = 'Discounted price should be lesser than regular price for %string%.';

    public $maxQuantityRequired = 'Max quantity is required for %string%';

    public $maxQuantityInvalid = 'Max quantity is higher than actual quantity for %string%';

    public $unitInActivePromo = '%string% is already in an active promo (%promo%).';

    public $invalidQuantityRequired = 'Invalid quantity required for %string%.';

    public $invalidMinPercent = 'Invalid minimum percentage for %string%.';
    
    public $invalidMaxPercent = 'Invalid maximum percentage for %string%.';

    public $invalidPercentPerHour = 'Invalid percentage per hour for %string%.';

    public $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function validatedBy()
    {
        return 'valid_promo_units';
    }
}
