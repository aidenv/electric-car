<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Logistics
 */
class Logistics
{
    const YILINKER_EXPRESS = 1;
    const YILINKER_3PL = 2;
    
    /**
     * @var integer
     */
    private $logisticsId;

    /**
     * @var string
     */
    private $name;


    /**
     * Get logisticsId
     *
     * @return integer 
     */
    public function getLogisticsId()
    {
        return $this->logisticsId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Logistics
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
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var boolean
     */
    private $isActive = true;


    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Logistics
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Logistics
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }


    public function toArray()
    {
        return array(
            'id' => $this->getLogisticsId(),
            'name'  => $this->getName(),
        );
    }
}
