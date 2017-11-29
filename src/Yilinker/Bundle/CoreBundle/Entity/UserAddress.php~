<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User Address
 */
class UserAddress
{

    const STATUS_DELETED = 1;

    const STATUS_ACTIVE = 0;

    /**
     * @var integer
     */
    private $userAddressId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $streetAddress;

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
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var integer
     */
    private $unitNumber;

    /**
     * @var string
     */
    private $buildingName;

    /**
     * @var integer
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
     * @var \Yilinker\Bundle\CoreBundle\Entity\Location
     */
    private $location;

    /**
     * @var boolean
     */
    private $isDefault;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var boolean
     */
    private $isDelete = 0;

    public function __construct()
    {
        $this->dateAdded = new \DateTime();
        $this->streetAddress = '';
    }

    /**
     * Get userAddressId
     *
     * @return integer 
     */
    public function getUserAddressId()
    {
        return $this->userAddressId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserAddress
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
     * @return UserAddress
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
     * @return UserAddress
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
        return strlen($this->longitude) > 0 ? $this->longitude : "0.0000";
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     * @return UserAddress
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
        return strlen($this->latitude) > 0 ? $this->latitude : "0.0000";
    }

    /**
     * Set landline
     *
     * @param string $landline
     * @return UserAddress
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserAddress
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set unitNumber
     *
     * @param integer $unitNumber
     * @return UserAddress
     */
    public function setUnitNumber($unitNumber)
    {
        $this->unitNumber = $unitNumber;

        return $this;
    }

    /**
     * Get unitNumber
     *
     * @return integer 
     */
    public function getUnitNumber()
    {
        return $this->unitNumber;
    }

    /**
     * Set buildingName
     *
     * @param string $buildingName
     * @return UserAddress
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
     * @param integer $streetNumber
     * @return UserAddress
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * Get streetNumber
     *
     * @return integer 
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * Set streetName
     *
     * @param string $streetName
     * @return UserAddress
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
     * @return UserAddress
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
     * @return UserAddress
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

    public function getFullAddress()
    {
        $fullAddress = 
            $this->getUnitNumber().' '.
            $this->getBuildingName().', '.
            $this->getStreetNumber().' '.
            $this->getStreetName().', '.
            $this->getSubdivision().', '.
            $this->getLocation().', '.
            $this->getZipCode()
        ;

        return $fullAddress;
    }

    /**
     * Set location
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $location
     * @return UserAddress
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
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return UserAddress
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    public function toArray()
    {
        $location = $this->getLocation();

        $data = array(
            'address_id'    => $this->getUserAddressId(),
            'dateAdded'     => $this->getDateAdded(),
            'title'         => $this->getTitle(),
            'streetAddress' => $this->getStreetAddress(),
            'longitude'     => $this->getLongitude(),
            'latitude'      => $this->getLatitude(),
            'landline'      => $this->getLandline(),
            'unitNumber'    => $this->getUnitNumber(),
            'buildingName'  => $this->getBuildingName(),
            'streetNumber'  => $this->getStreetNumber(),
            'streetName'    => $this->getStreetName(),
            'subdivision'   => $this->getSubdivision(),
            'zipCode'       => $this->getZipCode(),
            'isDefault'     => $this->getIsDefault(),
            'fullAddress'   => $this->getAddressString(),
            'location'      => is_null($location) === false ? $location->toArray() : array(),
        );

        return $data;
    }

    public function fromArray($data)
    {
        extract($data);

        $this->setStreetAddress($streetAddress);
        $this->setLongitude($longitude);
        $this->setLatitude($latitude);
        $this->setLandline($landline);
        $this->setUnitNumber($unitNumber);
        $this->setBuildingName($buildingName);
        $this->setStreetNumber($streetNumber);
        $this->setStreetName($streetName);
        $this->setSubdivision($subdivision);
        $this->setZipCode($zipCode);
        $this->setIsDefault($isDefault);
    }

    /**
     * Convert the address into human readable format
     *
     * @return string
     */
    public function getAddressString($includeLocation = true, $includeZipCode = true)
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
                         $subdivision;

        if ($includeLocation) {
            $addressString .= " ".$locationHierarchyString;
        }

        if ($includeZipCode) {
            $addressString .= " ".$zipCode;
        }

        $addressString = preg_replace('/\s+/', ' ', $addressString);
        $addressString = trim($addressString);
        $addressString = rtrim($addressString, ',');

        return $addressString;

    }

    public function __toString()
    {
        return $this->getAddressString(false, false);
    }

    /**
     * Get the short address string
     *
     * @return string
     */
    public function getShortAddressString()
    {
        $locationTypes = array(
            LocationType::LOCATION_TYPE_CITY,
            LocationType::LOCATION_TYPE_MUNICIPALITY,
            LocationType::LOCATION_TYPE_PROVINCE,
        );

        $addressString = "";
        if ($this->getLocation()) {
            $locationTree = $this->getLocation()->getLocalizedLocationTree();
            foreach($locationTree as $location){
                if(in_array($location->getLocationType()->getLocationTypeId(), $locationTypes)){
                    $addressString .= $location->getLocation().",";
                }
            }
        }
        $addressString = rtrim($addressString, ',');

        return $addressString;
    }


    /**
     * Set title
     *
     * @param string $title
     * @return UserAddress
     */
    public function setTitle($title)
    {
        $this->title = $title ? $title: '';

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
     * Set isDelete
     *
     * @param boolean $isDelete
     * @return UserAddress
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete
     *
     * @return boolean 
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }
}
