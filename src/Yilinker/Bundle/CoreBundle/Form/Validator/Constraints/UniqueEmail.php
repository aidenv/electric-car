<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueEmail extends Constraint
{
    public $message = "Email %string% is already taken.";
    
    /**
     * Exclude user_id from unique email validation
     *
     * @var integer
     */
    private $excludedUserId;

    /**
     * User type filter
     *
     * @var integer
     */
    private $userType;
    
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
        if (isset($this->options['excludedUserId'])) {
            $this->excludedUserId = $this->options['excludedUserId'];
        }

        $this->userType = null;
        if (isset($this->options['userType'])) {
            $this->userType = $this->options['userType'];
        }
    }

    public function getExcludedUserId()
    {
        return $this->excludedUserId;
    }

    public function getUserType()
    {
        return $this->userType;
    }

    public function getOptions()
    {
        return $this->options;
    }
    
    public function validatedBy()
    {
        return 'unique_email';
    }

}
