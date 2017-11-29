<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidAreaCode extends Constraint
{
    public $message = "SMS support is not available in your country.";
    
    private $options;

    public function __construct($options = [])
    {
        $this->options = array_merge(
            array(
                'repoMethod'        => array(),
                'repoMethodArgs'    => array(),
                'message'           => $this->message
            ),
            $options
        );
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function validatedBy()
    {
        return 'valid_area_code';
    }

}
