<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationType
 */
class LocationType
{
    /**
     * Location type country
     */
    const LOCATION_TYPE_COUNTRY = 1;

    /**
     * Location type island
     */
    const LOCATION_TYPE_ISLAND = 2;

    /**
     * Location type region
     */
    const LOCATION_TYPE_REGION = 3;

    /**
     * Location type city
     */
    const LOCATION_TYPE_CITY = 4;

    /**
     * Location type municipality
     */
    const LOCATION_TYPE_MUNICIPALITY = 5;

    /**
     * Location type province
     */
    const LOCATION_TYPE_PROVINCE = 6;

    /**
     * Location type barangay
     */
    const LOCATION_TYPE_BARANGAY = 7;

    /**
     * @var integer
     */
    private $locationTypeId;

    /**
     * @var string
     */
    private $name;


    /**
     * Get locationTypeId
     *
     * @return integer 
     */
    public function getLocationTypeId()
    {
        return $this->locationTypeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return LocationType
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
     * @var integer
     */
    private $lookupId;


    /**
     * Set lookupId
     *
     * @param integer $lookupId
     * @return LocationType
     */
    public function setLookupId($lookupId)
    {
        $this->lookupId = $lookupId;

        return $this;
    }

    /**
     * Get lookupId
     *
     * @return integer 
     */
    public function getLookupId()
    {
        return $this->lookupId;
    }
}
