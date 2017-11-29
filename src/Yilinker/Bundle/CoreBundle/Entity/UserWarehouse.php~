<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\Traits\LocationTrait;

/**
 * UserWarehouse
 */
class UserWarehouse
{
    use LocationTrait;

    /**
     * @var integer
     */
    private $userWarehouseId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $zipCode;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Location
     */
    private $location;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * Get userWarehouseId
     *
     * @return integer 
     */
    public function getUserWarehouseId()
    {
        return $this->userWarehouseId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserWarehouse
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
     * Set address
     *
     * @param string $address
     * @return UserWarehouse
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

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return UserWarehouse
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
     * Set location
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $location
     * @return UserWarehouse
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
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserWarehouse
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
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;


    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserWarehouse
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
     * @return UserWarehouse
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
    /**
     * @var boolean
     */
    private $isDelete = 0;


    /**
     * Set isDelete
     *
     * @param boolean $isDelete
     * @return UserWarehouse
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

    public function __toString()
    {
        $barangay = $this->location;
        $city = $barangay->getParent();
        $province = $city->getParent();

        return $this->address . ', ' . $barangay->getLocation() . ' ' . $city->getLocation() . ', ' .
            $province->getLocation();
    }

    public function toArray()
    {
        return array(
            'id'       => $this->userWarehouseId,
            'name'     => $this->name,
            'fullAddress'  => $this->__toString(),
            'address'   => $this->address,
            'isDelete' => $this->isDelete,
            'zipCode'  => $this->getZipCode(),
        );
    }    
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productUnitWarehouses;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productUnitWarehouses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add productUnitWarehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses
     * @return UserWarehouse
     */
    public function addProductUnitWarehouse(\Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses)
    {
        $this->productUnitWarehouses[] = $productUnitWarehouses;

        return $this;
    }

    /**
     * Remove productUnitWarehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses
     */
    public function removeProductUnitWarehouse(\Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses)
    {
        $this->productUnitWarehouses->removeElement($productUnitWarehouses);
    }

    /**
     * Get productUnitWarehouses
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductUnitWarehouses()
    {
        return $this->productUnitWarehouses;
    }
}
