<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerProductImage
 */
class ManufacturerProductImage
{

    /**
     * @var integer
     */
    private $manufacturerProductImageId;


    /**
     * Get manufacturerProductImageId
     *
     * @return integer 
     */
    public function getManufacturerProductImageId()
    {
        return $this->manufacturerProductImageId;
    }

    /**
     * @var string
     */
    private $imageLocation;

    /**
     * @var boolean
     */
    private $isDelete = false;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct
     */
    private $manufacturerProduct;


    /**
     * Set imageLocation
     *
     * @param string $imageLocation
     * @return ManufacturerProductImage
     */
    public function setImageLocation($imageLocation)
    {
        $this->imageLocation = $imageLocation;

        return $this;
    }

    /**
     * Get imageLocation
     *
     * @return string 
     */
    public function getImageLocation()
    {
        return $this->manufacturerProduct->getManufacturerProductId().'/'.$this->imageLocation;
    }
    
    /**
     * Get raw imageLocation
     *
     * @return string 
     */
    public function getRawImageLocation()
    {
        return $this->imageLocation;
    }

    /**
     * Set isDelete
     *
     * @param boolean $isDelete
     * @return ManufacturerProductImage
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

    /**
     * Set manufacturerProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct
     * @return ManufacturerProductImage
     */
    public function setManufacturerProduct(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct = null)
    {
        $this->manufacturerProduct = $manufacturerProduct;

        return $this;
    }

    /**
     * Get manufacturerProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct 
     */
    public function getManufacturerProduct()
    {
        return $this->manufacturerProduct;
    }

    /**
     * Get if the image is the primary image
     *
     * @return boolean
     */
    public function getIsPrimary()
    {
        return $this->getManufacturerProduct()->getPrimaryImage() && $this->getManufacturerProduct()->getPrimaryImage()->getManufacturerProductImageId() === $this->getManufacturerProductImageId();
    }

    /**
     * @var string
     */
    private $referenceNumber = '';


    /**
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return ManufacturerProductImage
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    /**
     * Get referenceNumber
     *
     * @return string 
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }
}
