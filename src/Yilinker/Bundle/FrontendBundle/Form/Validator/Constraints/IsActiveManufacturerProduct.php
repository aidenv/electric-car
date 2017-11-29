<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsActiveManufacturerProduct extends Constraint
{
    /**
     * @var string
     */
    public $message = 'ManufacturerProduct with ID "%string%" is not available.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'is_active_manufacturer_product';
    }
}
