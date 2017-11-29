<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ExistingUserImage extends Constraint
{
    public $message = "Unable to set %type%. Please try again later";

    private $options;

    public function __construct($options = array())
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function validatedBy()
    {
        return "existing_user_image";
    }
}
