<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * Location
 */
class Location
{

    const LOCATION_CODE_PHILIPPINES = 'ph';

    /**
     * @var integer
     */
    private $locationId;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $code = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Location
     */
    private $parent;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\LocationType
     */
    private $locationType;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ShippingLeadTime
     */
    private $shippingLeadTime;
    
    /**
     * @var boolean
     */
    private $isActive = true;

    /**
     * @var integer
     */
    private $lookupId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get locationId
     *
     * @return integer 
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Set location
     *
     * @param string $location
     * @return Location
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set parent
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $parent
     * @return Location
     */
    public function setParent(\Yilinker\Bundle\CoreBundle\Entity\Location $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Location 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set locationType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LocationType $locationType
     * @return Location
     */
    public function setLocationType(\Yilinker\Bundle\CoreBundle\Entity\LocationType $locationType)
    {
        $this->locationType = $locationType;

        return $this;
    }

    /**
     * Get locationType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\LocationType 
     */
    public function getLocationType()
    {
        return $this->locationType;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Location
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

    /**
     * Set lookupId
     *
     * @param integer $lookupId
     * @return Location
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

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Location
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
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return Location
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    public function __toString()
    {
        return $this->getLocation();
    }

    public function toArray()
    {
        $data = array(
            'locationId'    => $this->getLocationId(),
            'location'      => $this->getLocation()
        );

        return $data;
    }

    /**
     * Retrieves the localized location ancestors of a certain location
     */
    public function getLocalizedLocationTree($hydrateAsArray = false)
    {
        /**
         * TODO: Implement address localization here
         */
        $locationTypes = array(
            'PH' => array(
                LocationType::LOCATION_TYPE_BARANGAY,
                LocationType::LOCATION_TYPE_PROVINCE,
                LocationType::LOCATION_TYPE_CITY,
                LocationType::LOCATION_TYPE_MUNICIPALITY,
            )
        );

        $localizedLocation = array();
        
        $currentLocation = $this;
        while(true){
            $locationType = $currentLocation->getLocationType()->getLocationTypeId();
            if($locationType === LocationType::LOCATION_TYPE_COUNTRY || 
               $currentLocation->getParent() === null)
            {
                break;
            }
            if(in_array($locationType, $locationTypes['PH'])){
                $locationIndex = null;
                switch($locationType){
                    case LocationType::LOCATION_TYPE_BARANGAY:
                        $locationIndex = "barangay";
                        break;
                    case LocationType::LOCATION_TYPE_CITY:
                        $locationIndex = "city";
                        break;
                    case LocationType::LOCATION_TYPE_MUNICIPALITY:
                        $locationIndex = "city";
                        break;
                    case LocationType::LOCATION_TYPE_PROVINCE:
                        $locationIndex = "province";
                        break;
                }
                $localizedLocation[$locationIndex] = $hydrateAsArray ? $currentLocation->toArray() : $currentLocation; 
            }
            $currentLocation = $currentLocation->getParent();
        }
        
        return $localizedLocation;
    }


    /**
     * Add children
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $children
     * @return Location
     */
    public function addChild(\Yilinker\Bundle\CoreBundle\Entity\Location $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $children
     */
    public function removeChild(\Yilinker\Bundle\CoreBundle\Entity\Location $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActiveChildren()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isActive", true));
        
        $activeChildren = $this->getChildren()->matching($criteria);

        return $activeChildren;
    }

    public function getTreeParameter()
    {
        $locationTree = $this->getLocalizedLocationTree(true);
        $params = array();
        foreach ($locationTree as $key => $value) {
            $params[$key.'Id'] = $value['locationId'];
        }

        return $params;
    }

    /**
     * Set shippingLeadTime
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ShippingLeadTime $shippingLeadTime
     * @return Location
     */
    public function setShippingLeadTime(\Yilinker\Bundle\CoreBundle\Entity\ShippingLeadTime $shippingLeadTime = null)
    {
        $this->shippingLeadTime = $shippingLeadTime;

        return $this;
    }

    /**
     * Get shippingLeadTime
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ShippingLeadTime 
     */
    public function getShippingLeadTime()
    {
        return $this->shippingLeadTime;
    }

    public function getLeadTime()
    {
        $leadTime = $this->getShippingLeadTime();
        $currentLocation = $this;
        while (!$leadTime && $currentLocation) {
            $parent = $currentLocation->getParent();
            if ($parent && $parent != $currentLocation) {
                $leadTime = $parent->getShippingLeadTime();
                $currentLocation = $parent;
            }
            else {
                $currentLocation = null;
            }
        }

        return $leadTime ? $leadTime: '3-4 days';
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Location
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $locationType
     * @return null|Location
     */
    public function getParentByLocationType($locationType)
    {
        $parent = $this->getParent();
        if (!$parent || $parent->getLocationId() == $this->getLocationId() || !$parent->getLocationType()) {
            return null;
        }
        elseif ($parent->getLocationType()->getLocationTypeId() == $locationType) {
            return $parent;
        }
        else {
            return $parent->getParentByLocationType($locationType);
        }
    }

}
