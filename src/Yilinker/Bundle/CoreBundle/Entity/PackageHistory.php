<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Package
 */
class PackageHistory

{

    /**
     * @var integer
     */
    private $packageHistoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Package
     */
    private $package;

    /**
     * @var string
     */
    private $personInCharge = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PackageStatus
     */
    private $packageStatus;
    
    /**
     * @var string
     */
    private $contactNumber = '';

    /**
     * @var string
     */
    private $address = '';


    /**
     * Get packageHistoryId
     *
     * @return integer 
     */
    public function getPackageHistoryId()
    {
        return $this->packageHistoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return PackageHistory
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
    public function getDateAdded($format = '')
    {
        $dateAdded = $this->dateAdded;
        if ($format && $dateAdded instanceof \DateTime) {
            $dateAdded = $dateAdded->format($format);
        }

        return $dateAdded;
    }

    /**
     * Set package
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Package $package
     * @return PackageHistory
     */
    public function setPackage(\Yilinker\Bundle\CoreBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Package 
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set packageStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageStatus $packageStatus
     * @return PackageHistory
     */
    public function setPackageStatus(\Yilinker\Bundle\CoreBundle\Entity\PackageStatus $packageStatus = null)
    {
        $this->packageStatus = $packageStatus;

        return $this;
    }

    /**
     * Get packageStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PackageStatus 
     */
    public function getPackageStatus()
    {
        return $this->packageStatus;
    }

    /**
     * Set personInCharge
     *
     * @param string $personInCharge
     * @return PackageHistory
     */
    public function setPersonInCharge($personInCharge)
    {
        $this->personInCharge = $personInCharge;

        return $this;
    }

    /**
     * Get personInCharge
     *
     * @return string 
     */
    public function getPersonInCharge()
    {
        return $this->personInCharge;
    }

    /**
     * Convert the object to an array
     */
    public function toArray()
    {
        return array(
            'date'               => $this->getDateAdded(),
            'riderName'          => $this->getPersonInCharge(),
            'actionType'         => $this->getPackageStatus()->getName(),
            'location'           => $this->getAddress(),
            'riderContactNumber' => $this->getContactNumber(),
            'clientSignature'    => '',
            'waybillNumber'      => $this->getPackage() ? $this->getPackage()->getWaybillNumber() : null,
        );
    }


    /**
     * Set contactNumber
     *
     * @param string $contactNumber
     * @return PackageHistory
     */
    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Get contactNumber
     *
     * @return string 
     */
    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return PackageHistory
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }
}
