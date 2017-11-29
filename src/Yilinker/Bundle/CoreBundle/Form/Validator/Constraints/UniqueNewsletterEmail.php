<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueNewsletterEmail extends Constraint
{
    public $message = "Email address is already subscribed";

    public function validatedBy()
    {
        return 'unique_newsletter_email';
    }

}
