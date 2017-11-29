<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccreditationLevel
 */
class AccreditationLevel
{

    const TYPE_LEVEL_ONE = 1;

    const TYPE_LEVEL_TWO = 2;

    const TYPE_LEVEL_THREE = 3;

    /**
     * @var integer
     */
    private $accreditationLevelId;

    /**
     * @var string
     */
    private $name;


    /**
     * Get AccreditationLevelId
     *
     * @return integer 
     */
    public function getAccreditationLevelId()
    {
        return $this->accreditationLevelId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return AccreditationLevel
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
}
