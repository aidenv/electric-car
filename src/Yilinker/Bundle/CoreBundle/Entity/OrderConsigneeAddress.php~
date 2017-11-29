<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * OrderConsigneeAddress
 */
class OrderConsigneeAddress
{
    /**
     * @var integer
     */
    private $orderConsigneeAddressId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $streetAddress = '';

    /**
     * @var string
     */
    private $longitude = '';

    /**
     * @var string
     */
    private $latitude = '';

    /**
     * @var string
     */
    private $landline = '';

    /**
     * @var string
     */
    private $unitNumber;

    /**
     * @var string
     */
    private $buildingName;

    /**
     * @var string
     */
    private $streetNumber;

    /**
     * @var string
     */
    private $streetName;

    /**
     * @var string
     */
    private $subdivision;

    /**
     * @var string
     */
    private $zipCode;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Location
     */
    private $location;


    /**
     * Get orderConsigneeAddressId
     *
     * @return integer 
     */
    public function getOrderConsigneeAddressId()
    {
        return $this->orderConsigneeAddressId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderConsigneeAddress
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
     * Set streetAddress
     *
     * @param string $streetAddress
     * @return OrderConsigneeAddress
     */
    public function setStreetAddress($streetAddress)
    {
        $this->streetAddress = $streetAddress;

        return $this;
    }

    /**
     * Get streetAddress
     *
     * @return string 
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return OrderConsigneeAddress
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string 
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     * @return OrderConsigneeAddress
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string 
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set landline
     *
     * @param string $landline
     * @return OrderConsigneeAddress
     */
    public function setLandline($landline)
    {
        $this->landline = $landline;

        return $this;
    }

    /**
     * Get landline
     *
     * @return string 
     */
    public function getLandline()
    {
        return $this->landline;
    }

    /**
     * Set unitNumber
     *
     * @param string $unitNumber
     * @return OrderConsigneeAddress
     */
    public function setUnitNumber($unitNumber)
    {
        $this->unitNumber = $unitNumber;

        return $this;
    }

    /**
     * Get unitNumber
     *
     * @return string 
     */
    public function getUnitNumber()
    {
        return $this->unitNumber;
    }

    /**
     * Set buildingName
     *
     * @param string $buildingName
     * @return OrderConsigneeAddress
     */
    public function setBuildingName($buildingName)
    {
        $this->buildingName = $buildingName;

        return $this;
    }

    /**
     * Get buildingName
     *
     * @return string 
     */
    public function getBuildingName()
    {
        return $this->buildingName;
    }

    /**
     * Set streetNumber
     *
     * @param string $streetNumber
     * @return OrderConsigneeAddress
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * Get streetNumber
     *
     * @return string 
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * Set streetName
     *
     * @param string $streetName
     * @return OrderConsigneeAddress
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * Get streetName
     *
     * @return string 
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * Set subdivision
     *
     * @param string $subdivision
     * @return OrderConsigneeAddress
     */
    public function setSubdivision($subdivision)
    {
        $this->subdivision = $subdivision;

        return $this;
    }

    /**
     * Get subdivision
     *
     * @return string 
     */
    public function getSubdivision()
    {
        return $this->subdivision;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return OrderConsigneeAddress
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string 
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return OrderConsigneeAddress
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set location
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $location
     * @return OrderConsigneeAddress
     */
    public function setLocation(\Yilinker\Bundle\CoreBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Location 
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Convert the address into human readable format
     *
     * @return string
     */
    public function getAddressString()
    {
        $unitNumber =  trim($this->getUnitNumber());
        $buildingName =  trim($this->getBuildingName());
        $streetNumber =  trim($this->getStreetNumber());
        $streetName =  trim($this->getStreetName());
        $subdivision =  trim($this->getSubdivision());
        $zipCode =  trim($this->getZipCode());

        $unitNumber = $unitNumber === null || $unitNumber === ""  ? "" : $unitNumber;
        $buildingName = $buildingName === null || $buildingName === "" ? "" : $buildingName.",";
        $streetNumber =  $streetNumber === 0 || $streetNumber === ""  ? "" : $streetNumber;
        $streetName =  $streetName === null || $streetName === "" ? "" : $streetName.",";
        $subdivision =  $subdivision === null || $subdivision === "" ? "" : $subdivision.",";
        $zipCode =  $zipCode === null ? "" || $zipCode : $zipCode;

        $locationHierarchyString = "";
        if ($this->getLocation()) {
            $locationTree = $this->getLocation()->getLocalizedLocationTree();
            foreach($locationTree as $location){
                $locationHierarchyString .= $location->getLocation().", ";
            }
        }

        $addressString = $unitNumber." ".$buildingName." ".$streetNumber." ".$streetName." ".
                         $subdivision." ".$locationHierarchyString." ".$zipCode;

        $addressString = preg_replace('/\s+/', ' ', $addressString);
        $addressString = trim($addressString);
        $addressString = rtrim($addressString, ',');

        return $addressString;

    }

}
