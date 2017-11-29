<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ValidYoutubeURL extends Constraint
{
    public $message = "Invalid youtube url.";

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
        return 'valid_youtube_url';
    }
}