<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueSlug extends Constraint
{
    public $message = 'URL is already taken.';

    private $user;

    public function __construct($options = [])
    {
        $this->user = null;
        if (isset($options['user'])) {
            $this->user = $options['user'];
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function validatedBy()
    {
        return 'unique_slug';
    }
}
