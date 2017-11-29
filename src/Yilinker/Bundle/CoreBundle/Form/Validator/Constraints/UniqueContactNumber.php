<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueContactNumber extends Constraint
{    
    public $message = "Contact number %string% already exists.";
    /**
     * Exclude user_id from unique contcat number validation
     *
     * @var integer
     */
    private $excludedUserId;
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

        $this->excludedUserId = null;
        if (isset($options['excludedUserId'])) {
            $this->excludedUserId = $options['excludedUserId'];
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getExcludedUserId()
    {
        return $this->excludedUserId;
    }

    public function validatedBy()
    {
        return 'unique_contact_number';
    }

}
