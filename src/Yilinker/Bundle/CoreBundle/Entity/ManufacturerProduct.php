<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * ManufacturerProduct
 */
class ManufacturerProduct
{

    const STATUS_ACTIVE = 0;

    const STATUS_INACTIVE = 1;

    const STATUS_DELETED = 2;

    const SHORT_DESCRIPTION_LENGTH = 512;

    /**
     * @var integer
     */
    private $manufacturerProductId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastEmptied;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Manufacturer
     */
    private $manufacturer;

    /**
     * @var boolean
     */
    private $isCod;

    /**
     * @var string
     */
    private $keywords;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCondition
     */
    private $condition;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Brand
     */
    private $brand;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $units;

    /**
     * @var  \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    private $defaultUnit;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage
     */
    private $primaryImage;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $images;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $manufacturerProductAttributeNames;

    /**
     * @var string
     */
    private $referenceNumber = '';
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $manufacturerProductMaps;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var tinyint
     */
    private $status = '0';

    private $firstUnit;

    private $viewCount = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->units = new \Doctrine\Common\Collections\ArrayCollection();
        $this->manufacturerProductAttributeNames = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dateLastEmptied = Carbon::now();
    }

    /**
     * Get manufacturerProductId
     *
     * @return integer 
     */
    public function getManufacturerProductId()
    {
        return $this->manufacturerProductId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ManufacturerProduct
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
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ManufacturerProduct
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
     * Set dateLastEmptied
     *
     * @param \DateTime $dateLastEmptied
     * @return ManufacturerProduct
     */
    public function setDateLastEmptied($dateLastEmptied)
    {
        $this->dateLastEmptied = $dateLastEmptied;

        return $this;
    }

    /**
     * Get dateLastEmptied
     *
     * @return \DateTime 
     */
    public function getDateLastEmptied()
    {
        return $this->dateLastEmptied;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ManufacturerProduct
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        $description = str_replace(
            'https://trading.yilinker.com/ueditor/php/upload/',
            'https://oajhesrt9.qnssl.com/ueditor/php/upload/',
            $this->description
        );

        return $description;
    }

    /**
     * Set manufacturer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Manufacturer $manufacturer
     * @return ManufacturerProduct
     */
    public function setManufacturer(\Yilinker\Bundle\CoreBundle\Entity\Manufacturer $manufacturer = null)
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * Get manufacturer
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Manufacturer 
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * Set isCod
     *
     * @param boolean $isCod
     * @return ManufacturerProduct
     */
    public function setIsCod($isCod)
    {
        $this->isCod = $isCod;

        return $this;
    }

    /**
     * Get isCod
     *
     * @return boolean 
     */
    public function getIsCod()
    {
        return $this->isCod;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     * @return ManufacturerProduct
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string 
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set condition
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCondition $condition
     * @return ManufacturerProduct
     */
    public function setCondition(\Yilinker\Bundle\CoreBundle\Entity\ProductCondition $condition = null)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCondition 
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set brand
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Brand $brand
     * @return ManufacturerProduct
     */
    public function setBrand(\Yilinker\Bundle\CoreBundle\Entity\Brand $brand = null)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Brand 
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return ManufacturerProduct
     */
    public function setProductCategory(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCategory 
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }

    /**
     * Get the default unit
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    public function getDefaultUnit()
    {
        if (is_null($this->defaultUnit)) {
            $criteria = Criteria::create()
                                ->andWhere(Criteria::expr()->gt("quantity", 0))
                                ->andWhere(Criteria::expr()->neq("status", ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ARCHIVED))
                                ->setFirstResult(0)
                                ->setMaxResults(1);
            $this->defaultUnit = $this->getUnits()->matching($criteria)->first();
        }

        return $this->defaultUnit;
    }

    /**
     * Retrive the retail price set units
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    public function getRetailPriceSetUnits()
    {
        $criteria = Criteria::create()
                                ->andWhere(Criteria::expr()->neq("status", ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ARCHIVED))
                                ->andWhere(Criteria::expr()->andX(
                                    Criteria::expr()->neq("retailPrice", NULL),
                                    Criteria::expr()->gt("retailPrice", "0.0000")
                                ));

        return $this->getUnits()->matching($criteria);
    }


    /**
     * Get the first unit
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    public function getFirstUnit()
    {
        if (is_null($this->firstUnit)) {
            $this->firstUnit = $this->getUnits()->first();
        }

        return $this->firstUnit;
    }

    /**
     * Add units
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $units
     * @return ManufacturerProduct
     */
    public function addUnit(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $units)
    {
        $this->units[] = $units;

        return $this;
    }

    /**
     * Remove units
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $units
     */
    public function removeUnit(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $units)
    {
        $this->units->removeElement($units);
    }

    /**
     * Get units
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUnits()
    {
        return $this->units;
    }
    
    /**
     * Get active units
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActiveUnits()
    {
        $criteria = Criteria::create()
                            ->where(Criteria::expr()->eq("status", ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ACTIVE));

        return $this->units->matching($criteria);
    }
    
    /**
     * Set primaryImage
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage $primaryImage
     * @return ManufacturerProduct
     */
    public function setPrimaryImage(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage $primaryImage = null)
    {
        $this->primaryImage = $primaryImage;

        return $this;
    }

    /**
     * Get primaryImage
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage 
     */
    public function getPrimaryImage()
    {
        return $this->primaryImage;
    }

    /**
     * Add images
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage $images
     * @return ManufacturerProduct
     */
    public function addImage(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage $images)
    {
        $this->images[] = $images;

        return $this;
    }

    /**
     * Remove images
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage $images
     */
    public function removeImage(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage $images)
    {
        $this->images->removeElement($images);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Get active images
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActiveImages()
    {
        $criteria = Criteria::create()
                            ->where(Criteria::expr()->eq("isDelete", false));

        return $this->images->matching($criteria);
    }

    /**
     * Retrieves the first available non deleted image
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage
     */
    public function getFirstImage()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isDelete", false))
                            ->setFirstResult(0)
                            ->setMaxResults(1);

        return $this->getImages()->matching($criteria)->first();
    }

    /**
     * Retrieve all available attributes in a manufacturer product
     */
    public function getAvailableAttributes()
    {
        $attributes = array();
        $productAttributes = $this->getManufacturerProductAttributeNames();
        if (!$productAttributes) {
            return $attributes;
        }

        foreach ($productAttributes as $productAttrname) {
            $attr = array(
                'id'        => $productAttrname->getManufacturerProductAttributeNameId(),
                'groupName' => $productAttrname->getName(),
                'items'     => array(),
            );
            foreach ($productAttrname->getManufacturerProductAttributeValues() as $productAttrValue) {
                $attr['items'][] = array(
                    'id'    => $productAttrValue->getManufacturerProductAttributeValueId(),
                    'name'  => $productAttrValue->getValue(),
                );
            }
            $attributes[] = $attr;
        }

        return $attributes;
    }


    /**
     * Add manufacturerProductAttributeNames
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName $manufacturerProductAttributeNames
     * @return ManufacturerProduct
     */
    public function addManufacturerProductAttributeName(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName $manufacturerProductAttributeNames)
    {
        $this->manufacturerProductAttributeNames[] = $manufacturerProductAttributeNames;

        return $this;
    }

    /**
     * Remove manufacturerProductAttributeNames
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName $manufacturerProductAttributeNames
     */
    public function removeManufacturerProductAttributeName(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName $manufacturerProductAttributeNames)
    {
        $this->manufacturerProductAttributeNames->removeElement($manufacturerProductAttributeNames);
    }

    /**
     * Get manufacturerProductAttributeNames
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManufacturerProductAttributeNames()
    {
        return $this->manufacturerProductAttributeNames;
    }

    /**
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return ManufacturerProduct
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

    /**
     * Add manufacturerProductMaps
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap $manufacturerProductMaps
     * @return ManufacturerProduct
     */
    public function addManufacturerProductMap(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap $manufacturerProductMaps)
    {
        $this->manufacturerProductMaps[] = $manufacturerProductMaps;

        return $this;
    }

    /**
     * Remove manufacturerProductMaps
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap $manufacturerProductMaps
     */
    public function removeManufacturerProductMap(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap $manufacturerProductMaps)
    {
        $this->manufacturerProductMaps->removeElement($manufacturerProductMaps);
    }

    /**
     * Get manufacturerProductMaps
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManufacturerProductMaps()
    {
        return $this->manufacturerProductMaps;
    }

    public function setProductPageViews($viewCount)
    {
        $this->viewCount;
    }

    /**
     * Get Product page views
     */
    public function getProductPageViews()
    {
        if(is_null($this->viewCount)){

            $maps = $this->getManufacturerProductMaps();
            $this->viewCount = 0;
            if($maps){
                foreach($maps as $map){
//                    $this->viewCount += count($map->getProduct()->getProductVisits());
                    $this->viewCount = 1;
                }
            }
        }
            
        return $this->viewCount;
    }

    public function getFavoriteCount()
    {
        $maps = $this->getManufacturerProductMaps();
        $favoriteCount = 0;
        if($maps){
            foreach($maps as $map){
                $favoriteCount += $map->getProduct()->getWishlistCount();
            }
        }
            
        return $favoriteCount;
    }

    public function getRating()
    {
        $maps = $this->getManufacturerProductMaps();
        $rating = 0;
        if($maps){
            foreach($maps as $map){
                $rating += $map->getProduct()->getReviewRating();
            }
        }
        
        $averageRating = "0.00";
        $mapCounts = count($maps);
        if($mapCounts > 0){
            $averageRating = bcdiv($rating, count($maps), 2);
        }

        return $averageRating;
    }

    public function getSellerCount()
    {
        $sellers = array();
        $maps = $this->getManufacturerProductMaps();
        if($maps){
            foreach($maps as $map){
                $sellers[] = $map->getProduct()->getUser()->getUserId();
            }
        }

        $sellers = array_unique($sellers);
        
        return count($sellers);
    }

    public function getInventory()
    {
        $inventory = 0;

        $units = $this->getUnits();

        foreach ($units as $unit) {
            $inventory += (int)$unit->getQuantity();
        }

        return $inventory;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ManufacturerProduct
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \tinyint 
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getEquivalentProductStatus()
    {
        switch ($this->status) {
            case self::STATUS_ACTIVE:
                return Product::ACTIVE;
            case self::STATUS_INACTIVE:
                return Product::INACTIVE;
            case self::STATUS_DELETED:
                return Product::DELETE;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Get the flattened category
     *
     * @return mixed
     */
    public function getFlattenedCategory()
    {
        $category = $this->getProductCategory();

        return array(
            'categoryId' => $category ? $category->getProductCategoryId() : "0",
            'categoryName' => $category ? $category->getName() : '',
        );
    }

    /**
     * Get the flattened manufacturer
     *
     * @return mixed
     */
    public function getFlattenedManufacturer()
    {
        $supplier = $this->getManufacturer();

        return array(
            'manufacturerId'   => $supplier ? $supplier->getManufacturerId() : '0',
            'manufacturerName' => $supplier ? $supplier->getName() : '',
        );
    }

    /**
     * Get the flattened brand
     *
     * @return mixed
     */
    public function getFlattenedBrand()
    {
        $brand = $this->getBrand();
        
        return array(
            'brandId'   => $brand ? $brand->getBrandId() : 0,
            'brandName' => $brand ? $brand->getName() : '',
        );
    }

    /**
     * Implement custom brand string getter to align with product entity
     *
     * @return string
     */
    public function getCustomBrandString()
    {
        return $this->brand->getName();
    }

    
    /**
     * Implement isEditable boolean getter to align with product entity
     *
     * @return boolean
     */
    public function getIsEditable()
    {
        return false;
    }

    /**
     * @var \DateTime
     */
    private $dateLastModified;


    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return ManufacturerProduct
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
     * @var string
     */
    private $shortDescription = '';


    /**
     * Set shortDescription
     *
     * @param string $shortDescription
     * @return ManufacturerProduct
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = substr($shortDescription, 0, (self::SHORT_DESCRIPTION_LENGTH - 1));

        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string 
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    public function imageDir()
    {
        $id = $this->getManufacturerProductId();

        return 'assets/images/uploads/manufacturer_products/'.$id;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $manufacturerProductCountries;


    /**
     * Add manufacturerProductCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductCountry $manufacturerProductCountries
     * @return ManufacturerProduct
     */
    public function addManufacturerProductCountry(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductCountry $manufacturerProductCountries)
    {
        $this->manufacturerProductCountries[] = $manufacturerProductCountries;

        return $this;
    }

    /**
     * Remove manufacturerProductCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductCountry $manufacturerProductCountries
     */
    public function removeManufacturerProductCountry(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductCountry $manufacturerProductCountries)
    {
        $this->manufacturerProductCountries->removeElement($manufacturerProductCountries);
    }

    /**
     * Get manufacturerProductCountries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManufacturerProductCountries()
    {
        return $this->manufacturerProductCountries;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ManufacturerProduct
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }
}
