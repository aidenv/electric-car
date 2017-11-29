<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerProductUnit
 */
class ManufacturerProductUnit
{
    const MANUFACTURER_PRODUCT_STATUS_ACTIVE = 0;

    const MANUFACTURER_PRODUCT_STATUS_ARCHIVED = 1;

    /**
     * @var integer
     */
    private $manufacturerProductUnitId;

    /**
     * @var integer
     */
    private $quantity = '0';

    /**
     * @var string
     */
    private $sku = '';

    /**
     * @var string
     */
    private $price = '0.00';

    /**
     * @var string
     */
    private $discountedPrice = '0.00';

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var integer
     */
    private $status = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct
     */
    private $manufacturerProduct;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $manufacturerProductAttributeValues;

    /**
     * @var string
     */
    private $weight = '0.0000';

    /**
     * @var string
     */
    private $length = '0.0000';

    /**
     * @var string
     */
    private $width = '0.0000';

    /**
     * @var string
     */
    private $height = '0.0000';

    /**
     * @var integer
     */
    private $moq = '0';

    /**
     * The is the OEM price without any additional price (usually less than the SRP)
     *
     * @var string
     */
    private $unitPrice = '0.00';

    /**
     * Get review count
     * Populated by elastica
     *
     * @var int
     */
    private $reviewCount = null;

    /**
     * Product page view count
     * Populated by elastica
     *
     * @var int
     */
    private $viewCount = null;

    /**
     * Wishlist count
     * Populated by elastica
     *
     * @var int
     */
    private $wishlistCount = null;

    /**
     * Reference Number
     * Populated by elastica
     *
     * @var string
     */
    private $referenceNumber = null;

    /**
     * Store count
     * Populated by elastica
     *
     * @var int
     */
    private $storeCount = null;

    /**
     * Average Rating
     * Populated by elastica
     *
     * @var string
     */
    private $averageRating;
    
    /**
     * @var string
     */
    private $shippingFee = '0.00';

    /**
     * @var string
     */
    private $commission;

    /**
     * @var string
     */
    private $referenceId = '';
   
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->manufacturerProductAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get manufacturerProductUnitId
     *
     * @return integer 
     */
    public function getManufacturerProductUnitId()
    {
        return $this->manufacturerProductUnitId;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return ManufacturerProductUnit
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return ManufacturerProductUnit
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get sku
     *
     * @return string 
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set price
     *
     * @param string $price
     * @return ManufacturerProductUnit
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set discountedPrice
     *
     * @param string $discountedPrice
     * @return ManufacturerProductUnit
     */
    public function setDiscountedPrice($discountedPrice)
    {
        $this->discountedPrice = $discountedPrice;

        return $this;
    }

    /**
     * Get discountedPrice
     *
     * @return string 
     */
    public function getDiscountedPrice()
    {
        return $this->discountedPrice;
    }

    /**
     * Retrieve the discounted percentage
     *
     * @return string
     */
    public function getDiscountPercentage()
    {
        if(bccomp($this->price, "0") === 0){
            return "0.00";
        }

        return bcmul(bcdiv($this->discountedPrice, $this->price, 4), "100.00", 2);
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return ManufacturerProductUnit
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return ManufacturerProductUnit
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
     * Set status
     *
     * @param integer $status
     * @return ManufacturerProductUnit
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set manufacturerProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct
     * @return ManufacturerProductUnit
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
     * Retrieve the values of the entity in an array
     *
     * @return mixed
     */
    public function toArray()
    {
        return array(
            'quantity'           => $this->getQuantity(),
            'sku'                => $this->getSku(),
            'price'              => $this->getPrice(),
            'discountedPrice'    => $this->getDiscountedPrice(),
            'discountPercentage' => $this->getDiscountPercentage(),
            'dateCreated'        => $this->getDateCreated(),
            'dateLastModified'   => $this->getDateLastModified(),
            'retailPrice'        => $this->getRetailPrice(),
            'commission'         => $this->getCommission(),
            'status'             => $this->getStatus(),
            'width'              => $this->getWidth(),
            'length'             => $this->getLength(),
            'height'             => $this->getHeight(),
            'weight'             => $this->getWeight(),
        );
    }

    /**
     * Add manufacturerProductAttributeValues
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues
     * @return ManufacturerProductUnit
     */
    public function addManufacturerProductAttributeValue(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues)
    {
        $this->manufacturerProductAttributeValues[] = $manufacturerProductAttributeValues;

        return $this;
    }

    /**
     * Remove manufacturerProductAttributeValues
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues
     */
    public function removeManufacturerProductAttributeValue(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues)
    {
        $this->manufacturerProductAttributeValues->removeElement($manufacturerProductAttributeValues);
    }

    /**
     * Get manufacturerProductAttributeValues
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManufacturerProductAttributeValues()
    {
        return $this->manufacturerProductAttributeValues;
    }

    /**
     * @return array combination of attributes and values
     */
    public function getCombination()
    {
        $attributeValues = $this->getManufacturerProductAttributeValues();
        $combination = array();
        foreach ($attributeValues as $attributeValue) {
            if($attributeValue && $attributeValue->getManufacturerProductAttributeName()){
                $attributeNameId = $attributeValue->getManufacturerProductAttributeName()
                                                  ->getManufacturerProductAttributeNameId();
                $combination[$attributeNameId] = $attributeValue->toArray();
            }           
        }

        return $combination;
    }

    /**
     * @return json encoded string of the combination attribute-value pairs
     */
    public function getCombinationString()
    {
        $attributeValues = $this->getManufacturerProductAttributeValues();
        $combination = array();
        foreach ($attributeValues as $attributeValue) {
            $attributeName = $attributeValue->getManufacturerProductAttributeName();
            $combination[] = array(
                $attributeName->getName() => $attributeValue->getValue(),
            );
        }

        return json_encode($combination);
    }


    /**
     * Set weight
     *
     * @param string $weight
     * @return ManufacturerProductUnit
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return string 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set length
     *
     * @param string $length
     * @return ManufacturerProductUnit
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return string 
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set width
     *
     * @param string $width
     * @return ManufacturerProductUnit
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return string 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param string $height
     * @return ManufacturerProductUnit
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return string 
     */
    public function getHeight()
    {
        return $this->height;
    }
    /**
     * @var boolean
     */
    private $isInventoryConfirmed = false;

    /**
     * Set isInventoryConfirmed
     *
     * @param boolean $isInventoryConfirmed
     * @return ManufacturerProductUnit
     */
    public function setIsInventoryConfirmed($isInventoryConfirmed)
    {
        $this->isInventoryConfirmed = $isInventoryConfirmed;

        return $this;
    }

    /**
     * Get isInventoryConfirmed
     *
     * @return boolean 
     */
    public function getIsInventoryConfirmed()
    {
        return $this->isInventoryConfirmed;
    }

    /**
     * Set moq
     *
     * @param integer $moq
     * @return ManufacturerProductUnit
     */
    public function setMoq($moq)
    {
        $this->moq = $moq;

        return $this;
    }

    /**
     * Get moq
     *
     * @return integer 
     */
    public function getMoq()
    {
        return $this->moq;
    }

    /**
     * Set unitPrice
     *
     * @param string $unitPrice
     * @return ManufacturerProductUnit
     */
    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Get unitPrice
     *
     * @return string 
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    public function getReviewCount()
    {
        return $this->reviewCount;
    }

    public function setReviewCount($reviewCount)
    {
        $this->reviewCount = $reviewCount;
    }
    
    public function getViewCount()
    {
        if($this->viewCount === null){
            $this->viewCount = $this->getManufacturerProduct()->getProductPageViews();
        }
        return $this->viewCount;
    }

    public function setViewCount($viewCount)
    {
        $this->viewCount = $viewCount;
    }

    public function getWishlistCount()
    {
        if($this->wishlistCount === null){
            $this->wishlistCount = $this->getManufacturerProduct()->getFavoriteCount();
        }

        return $this->wishlistCount;
    }

    public function setWishlistCount($wishlistCount)
    {
        $this->wishlistCount = $wishlistCount;
    }

    public function getStoreCount()
    {
        if($this->storeCount === null){
            $this->averageRating = $this->getManufacturerProduct()->getSellerCount();
        }

        return $this->storeCount;
    }

    public function setStoreCount($storeCount)
    {
        $this->storeCount = $storeCount;
    }

    public function getReferenceNumber()
    {
        if($this->referenceNumber === null){
            $this->referenceNumber = $this->getManufacturerProduct()->getReferenceNumber().'_'.$this->getManufacturerProductUnitId();
        }

        return $this->referenceNumber;
    }

    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function getAverageRating()
    {
        if($this->averageRating === null){
            $this->averageRating = $this->getManufacturerProduct()->getRating();
        }

        return $this->averageRating;
    }

    public function setAverageRating($averageRating)
    {
        $this->averageRating = $averageRating;
    }


    /**
     * Set shippingFee
     *
     * @param string $shippingFee
     * @return ManufacturerProductUnit
     */
    public function setShippingFee($shippingFee)
    {
        $this->shippingFee = $shippingFee;

        return $this;
    }

    /**
     * Get shippingFee
     *
     * @return string 
     */
    public function getShippingFee()
    {
        return $this->shippingFee;
    }
    /**
     * @var string
     */
    private $retailPrice = null;


    /**
     * Set retailPrice
     *
     * @param string $retailPrice
     * @return ManufacturerProductUnit
     */
    public function setRetailPrice($retailPrice)
    {
        $this->retailPrice = $retailPrice;

        return $this;
    }

    /**
     * Get retailPrice
     *
     * @return string 
     */
    public function getRetailPrice()
    {
        return $this->retailPrice;
    }

    /**
     * Set commission
     *
     * @param string $commission
     * @return ManufacturerProductUnit
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * Get commission
     *
     * @return string 
     */
    public function getCommission()
    {
        return $this->commission ? $this->commission: 0.0;
    }

    /**
     * Set referenceId
     *
     * @param string $referenceId
     * @return ManufacturerProductUnit
     */
    public function setReferenceId($referenceId)
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    /**
     * Get referenceId
     *
     * @return string 
     */
    public function getReferenceId()
    {
        return $this->referenceId;
    }
}
