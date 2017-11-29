<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueBuyerSlug extends Constraint
{
    public $message = 'Slug is already taken.';

    public function validatedBy()
    {
        return 'unique_buyer_slug';
    }
}
