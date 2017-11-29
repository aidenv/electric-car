<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidSlug extends Constraint
{
    public $message = 'URL contains invalid characters.';

    public function validatedBy()
    {
        return 'valid_slug';
    }
}
