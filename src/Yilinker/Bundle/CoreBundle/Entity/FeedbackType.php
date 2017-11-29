<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FeedbackType
 */
class FeedbackType
{

    const FEEDBACK_TYPE_COMMUNICATION = 1;

    const FEEDBACK_TYPE_QUALITY = 2;

    /**
     * @var integer
     */
    private $feedbackTypeId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;


    /**
     * Get feedbackTypeId
     *
     * @return integer 
     */
    public function getFeedbackTypeId()
    {
        return $this->feedbackTypeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return FeedbackType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return FeedbackType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }
}
