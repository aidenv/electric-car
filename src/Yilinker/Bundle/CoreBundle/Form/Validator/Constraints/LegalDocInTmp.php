<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LegalDocInTmp extends Constraint
{
    public $message = "Document not found. Please reupload the document again.";

    public $options = array();

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
        return "legal_doc_in_tmp";
    }
}
