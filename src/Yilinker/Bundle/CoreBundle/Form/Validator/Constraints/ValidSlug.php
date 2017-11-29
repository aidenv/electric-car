<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidSlug extends Constraint
{
    public $message = 'Slug contains invalid chars.';

    public function validatedBy()
    {
        return 'valid_slug';
    }
}
