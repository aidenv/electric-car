<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductCondition;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable as Translatable;
use Yilinker\Bundle\CoreBundle\Entity\Traits\CountryCodeTrait;

/**
 * Product
 */
class Product extends Translatable
{
    use CountryCodeTrait;

    /**
     * Drafted products
     */
    const DRAFT = 0;

    /**
     * Products for review by CSR
     */
    const FOR_REVIEW = 1;

    /**
     * Viewable product
     */
    const ACTIVE = 2;

    /**
     * Product has been soft-deleted (out of merchant suport)
     *
     */
    const DELETE = 3;

    /**
     * Product has been full-deleted
     */
    const FULL_DELETE = 4;

    /**
     * Product has been rejected by CSR
     */
    const REJECT = 5;

    /**
     * Product is temporarily inactivated
     * Also triggered by quantity reaching 0
     *
     */
    const INACTIVE = 6;

    /**
     * Product is newly created and does not have inventory, price and discount.
     */
    const FOR_COMPLETION = 7;

    /**
     * @var integer
     */
    private $productId;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productGroups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productCountries;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \DateTime
     */
    private $dateLastEmptied;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var integer
     */
    private $clickCount = '0';

    /**
     * @var string
     */
    private $keywords = '';

    /**
     * @var string
     *
     * Cannot be initialized for the GEDMO sluggable behavior to work.
     */
    private $slug;

    /**
     * @var boolean
     */
    private $isCod = '0';

    /**
     * @var boolean
     */
    private $isFreeShipping = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Brand
     */
    private $brand;

    /**
     * @var ProductCategory
     */
    private $productCategory;

    /**
     * @var ProductCondition
     */
    private $condition;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $units;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $attributes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $images;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $reviews;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $customBrand;

    /**
     * @var integer
     */
    private $status = self::DRAFT;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $customizedCategoryLookup;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderProducts;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap
     */
    private $manufacturerProductMap;

    /**
     * @var string
     */
    private $shortDescription = '';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productVisits;

    /**
     * @var string
     */
    private $youtubeVideoUrl = '';

    /**
     * @var string
     */
    private $defaultLocale = 'en';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productRemarks;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $inhouseProductUsers;

    /**
     * @var boolean
     */
    private $isNotShippable = '0';

    private $productDetails = null;
    private $defaultUnit = null;
    private $featuredUnit = null;
    private $primaryImages = null;
    private $visibleReviews = null;
    private $reviewRating = null;
    private $wishlistCount = 0;

    private static $editableStatuses = array(
        Product::DRAFT,
        Product::ACTIVE,
        Product::INACTIVE,
        Product::DELETE,
        Product::REJECT,
        Product::FOR_COMPLETION
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->units = new \Doctrine\Common\Collections\ArrayCollection();
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productVisits = new \Doctrine\Common\Collections\ArrayCollection();
        $this->customBrand = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productCountries = new \Doctrine\Common\Collections\ArrayCollection();
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
        $this->inhouseProductUsers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dateLastEmptied = Carbon::now();
    }

    /**
     * Get productId
     *
     * @return integer
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return Product
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
     * @return Product
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
     * Set dateLastEmptied
     *
     * @param \DateTime $dateLastEmptied
     * @return Product
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
     * Set name
     *
     * @param string $name
     * @return Product
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
     * Set description
     *
     * @param string $description
     * @return Product
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
    public function getDescription($escape = false)
    {
        $description = str_replace(
            'https://trading.yilinker.com/ueditor/php/upload/',
            'https://oajhesrt9.qnssl.com/ueditor/php/upload/',
            $this->description
        );

        $description = str_replace(
            'http://trading.yilinker.com/ueditor/php/upload/',
            'http://oajhesrt9.qnssl.com/ueditor/php/upload/',
            $description
        );

        $description = str_replace(
            'https://images-trading.yilinker.com',
            'https://oajheekw8.qnssl.com',
            $description
        );

        return $escape ? htmlentities($description): $description;
    }

    /**
     * Set clickCount
     *
     * @param integer $clickCount
     * @return Product
     */
    public function setClickCount($clickCount)
    {
        $this->clickCount = $clickCount;

        return $this;
    }

    /**
     * Get clickCount
     *
     * @return integer
     */
    public function getClickCount()
    {
        return $this->clickCount;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     * @return Product
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
     * Set slug
     *
     * @param string $slug
     * @return Product
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set isCod
     *
     * @param boolean $isCod
     * @return Product
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
     * Set isFreeShipping
     *
     * @param boolean $isFreeShipping
     * @return Product
     */
    public function setIsFreeShipping($isFreeShipping)
    {
        $this->isFreeShipping = $isFreeShipping;

        return $this;
    }

    /**
     * Get isFreeShipping
     *
     * @return boolean
     */
    public function getIsFreeShipping()
    {
        return $this->isFreeShipping;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return Product
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
     * Set brand
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Brand $brand
     * @return Product
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
     * @param ProductCategory $productCategory
     * @return Product
     */
    public function setProductCategory(ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return ProductCategory
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }

    /**
     * Set condition
     *
     * @param ProductCondition $condition
     * @return Product
     */
    public function setCondition(ProductCondition $condition = null)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return ProductCondition
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set short_description
     *
     * @param string $shortDescription
     * @return Product
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get short_description
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Add units
     *
     * @param ProductUnit $units
     * @return Product
     */
    public function addUnit(ProductUnit $units)
    {
        $this->units[] = $units;

        return $this;
    }

    /**
     * Remove units
     *
     * @param ProductUnit $units
     */
    public function removeUnit(ProductUnit $units)
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

    public function getActiveUnits()
    {
        if (is_null($this->units)) {
            $criteria = Criteria::create()
                                ->andWhere(Criteria::expr()->eq("status", ProductUnit::STATUS_ACTIVE));

            $this->units = $this->getUnits()->matching($criteria);
        }

        return $this->units;
    }

    public function getUnitsById($unitIds = array())
    {
        if (is_null($this->units)) {
            $criteria = Criteria::create()
                                ->andWhere("productUnitId IN (:unitIds)")
                                ->setParameter(":unitIds", $unitIds);

            return $this->getUnits()->matching($criteria);
        }

        return array();
    }

    /**
     * Add attributes
     *
     * @param ProductAttributeName $attributes
     * @return Product
     */
    public function addAttribute(ProductAttributeName $attributes)
    {
        $this->attributes[] = $attributes;

        return $this;
    }

    /**
     * Remove attributes
     *
     * @param ProductAttributeName $attributes
     */
    public function removeAttribute(ProductAttributeName $attributes)
    {
        $this->attributes->removeElement($attributes);
    }

    /**
     * Get attributes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttributeByName($name)
    {
        $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('name', $name))
                        ->setMaxResults(1);

        return $this->getAttributes()->matching($criteria)->findOneOrNullResult();
    }

    public function getAvailableAttributes()
    {
        $attributes = array();
        $productAttributes = $this->getAttributes();
        if (!$productAttributes) {
            return $attributes;
        }

        foreach ($productAttributes as $productAttrname) {
            $attr = array(
                'id'        => $productAttrname->getProductAttributeNameId(),
                'groupName' => $productAttrname->getName(),
                'items'     => array(),
            );
            foreach ($productAttrname->getProductAttributeValues() as $productAttrValue) {
                $attr['items'][] = array(
                    'id'    => $productAttrValue->getProductAttributeValueId(),
                    'name'  => $productAttrValue->getValue(),
                );
            }
            $attr['choices'] = array_map(function($item) {
                return $item['name'];
            }, $attr['items']);
            $attr['choices'] = array_values(array_unique($attr['choices']));

            $attributes[] = $attr;
        }

        return $attributes;
    }

    /**
     * @return $attributeValues[] - {{ attributeName|attributeValue }}
     */
    public function getAttributeValues()
    {
        $attributes = $this->getAvailableAttributes();

        $attributeValues = array();
        foreach ($attributes as $attribute) {
            foreach ($attribute['items'] as $item) {
                $attributeValues[] = $attribute['groupName'].'|'.$item['name'];
            }
        }

        return $attributeValues;
    }

    /**
     * @return $attributeValues[] - {{ attributeName|attributeValue }}
     */
    public function getCapitalizedAttributeValues()
    {
        $attributes = $this->getAvailableAttributes();

        $attributeValues = array();
        foreach ($attributes as $attribute) {
            foreach ($attribute['items'] as $item) {
                $attributeValues[] = ucfirst($attribute['groupName']).'|'.ucfirst($item['name']);
            }
        }

        return $attributeValues;
    }

    public function getDetails($productUnitsHaveId = true, $activeUnits = false)
    {
        if (is_null($this->productDetails)) {
            $images = array();
            foreach ($this->getImages() as $image) {
                $images[] = $image->toArray();
            }

            $attributes = $this->getAvailableAttributes();
            $image = $this->getPrimaryImageLocation();
            $productUnits = array();

            $units = $this->getUnits();

            //set default unit
            if (is_null($this->defaultUnit)) {
                foreach ($units as $unit) {
                    $unitData = $unit->toArray(false, true, false, $activeUnits);
                    if ($unitData) {
                        if ($unitData['quantity'] > 0) {
                            $this->defaultUnit = $unit;
                            continue;
                        }
                    }
                }
            }

            foreach ($units as $unit) {
                $unitData = $unit->toArray(false, true, false, $activeUnits);

                if($unitData){
                    $attributeIds = array_map(function($attribute) {
                        return $attribute['id'];
                    }, $attributes);
                    $combination = $unit->getCombination($attributeIds);
                    $idCombination = array();
                    $nameCombination = array();
                    $variantCombination = array();
                    foreach ($attributeIds as $attributeId) {
                        if (array_key_exists($attributeId, $combination)) {
                            $idCombination[] = $combination[$attributeId]['productAttributeValueId'];
                            $nameCombination[] = $combination[$attributeId]['value'];
                            $variantCombination[] = array(
                                'name' => $combination[$attributeId]['name'],
                                'value' => $combination[$attributeId]['value']
                            );
                        }
                        else {
                            $idCombination[] = '0';
                            $nameCombination[] = '';
                            $variantCombination[] = array();
                        }
                    }
                    $unitData['combination'] = $idCombination;
                    $unitData['combinationNames'] = $nameCombination;
                    $unitData['variantCombination'] = $variantCombination;
                    if ($productUnitsHaveId) {
                        $productUnits[$unit->getProductUnitId()] = $unitData;
                    }
                    else {
                        $productUnits[] = $unitData;
                    }
                }
            }
            $this->filterAvailabeAttributes($attributes, $productUnits);
            $this->getWarehouse();

            $this->productDetails = array(
                'id'                => $this->getProductId(),
                'title'             => $this->getName(),
                'slug'              => $this->getSlug(),
                'image'             => $image,
                'status'            => $this->getStatus(),
                'raw'               => $this->getPrimaryImageLocation(true),
                'thumbnail'         => $this->getPrimaryImageLocationBySize("thumbnail"),
                'small'             => $this->getPrimaryImageLocationBySize("small"),
                'medium'            => $this->getPrimaryImageLocationBySize("medium"),
                'large'             => $this->getPrimaryImageLocationBySize("large"),
                'images'            => $images,
                'shortDescription'  => !is_null($this->getShortDescription())? $this->getShortDescription() : "",
                'fullDescription'   => !is_null($this->getDescription())? $this->getDescription() : "",
                'sellerId'          => $this->getUser()->getId(),
                'storeId'           => $this->getUser()->getStore()->getStoreId(),
                'isAffiliate'       => $this->getUser()->getStore()->isAffiliate(),
                'brandId'           => $this->getBrand() ? $this->getBrand()->getBrandId() : null,
                'productCategoryId' => $this->getProductCategory() ? $this->getProductCategory()->getProductCategoryId(): 0,
                'attributes'        => $attributes,
                'dateCreated'       => $this->getDateCreated(),
                'dateLastModifed'   => $this->getDateLastModified(),
                'productUnits'      => $productUnits,
                'shippingCost'      => $this->productWarehouse ? $this->productWarehouse->getHandlingFee(): 0,
                'hasCOD'            => $this->hasCOD(),
                'elastica'          => isset($this->elastica) ? $this->elastica: null
            );
        }

        return $this->productDetails;
    }

    /**
     * additional filter for attribute values that use this
     * product's attribute name but the product unit
     * associated with the value is not for this product
     */
    private function filterAvailabeAttributes(&$attributes, &$productUnits)
    {
        if (!$attributes) {
            return array();
        }

        $attr = array_fill(0, count($attributes), array());
        $allow = array_fill(0, count($attributes), array());
        foreach ($productUnits as $productUnit) {
            foreach ($productUnit['combination'] as $key => $value) {
                $allow[$key][] = $value;
            }
        }

        foreach ($attributes as $key => &$attribute) {
            $unset = array();
            foreach ($attribute['items'] as $attrValueKey => &$attributeValue) {
                $allowed = array_search($attributeValue['id'], $allow[$key]) > -1;
                if (!$allowed) {
                    $unset[] = $attrValueKey;
                }
            }
            rsort($unset);
            foreach ($unset as $unsetKey) {
                unset($attribute['items'][$unsetKey]);
            }
        }
        $unsetAttributeNameKeys = array();
        foreach ($attributes as $key => $value) {
            if (empty($value['items'])) {
                $unsetAttributeNameKeys[] = $key;
            }

            $attributes[$key]['items'] = array_values($attributes[$key]['items']);
        }
        rsort($unsetAttributeNameKeys);
        foreach ($unsetAttributeNameKeys as $unsetKey) {
            unset($attributes[$unsetKey]);
            foreach ($productUnits as &$productUnit) {
                unset($productUnit['combination'][$unsetKey]);
            }
        }
    }

    /**
     * Add images
     *
     * @param ProductImage $images
     * @return Product
     */
    public function addImage(ProductImage $images)
    {
        $this->images[] = $images;

        return $this;
    }

    /**
     * Remove images
     *
     * @param ProductImage $images
     */
    public function removeImage(ProductImage $images)
    {
        $this->images->removeElement($images);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImages($asArray = false, $rawCollection = false, $locale = null)
    {
        if($rawCollection){
            return $this->images;
        }
        else{

            $targetLocale = $locale? $locale : $this->getLocale('en');

            $criteria = Criteria::create()
                                ->where(Criteria::expr()->eq('isDeleted', 0))
                                ->andWhere(
                                    Criteria::expr()->eq('defaultLocale', $targetLocale)
                                )
                                ->orderBy(array('isPrimary' => Criteria::DESC));
            $imagesEntity = $this->images->matching($criteria);

            if ($asArray) {
                $images = array();

                foreach($imagesEntity as $image) {
                    $images[] = $image->toArray();
                }

                return $images;

            }

            return $imagesEntity;
        }
    }

    public function getActiveImagesByLocale($locale = "en")
    {
        $criteria = Criteria::create()
                            ->where(Criteria::expr()->eq('isDeleted', 0))
                            ->andWhere(Criteria::expr()->eq('defaultLocale', $locale))
                            ->orderBy(array('isPrimary' => Criteria::DESC));

        return $this->images->matching($criteria);
    }

    public function getPrimaryImages()
    {
        if (is_null($this->primaryImages)) {
            $criteria = Criteria::create()
                                ->andWhere(Criteria::expr()->gt("isPrimary", 0))
                                ->andWhere(Criteria::expr()->eq('defaultLocale', $this->getLocale()));
            $this->primaryImages = $this->getImages()->matching($criteria);
        }

        return $this->primaryImages;
    }

    /**
     * Force set the default unit. This is useful for reloading the default product unit
     * with a different locale
     *
     * @param ProductUnit $productUnit
     * @return Product
     */
    public function setDefaultUnit(ProductUnit $productUnit)
    {
        $this->defaultUnit = $productUnit;

        return $this;
    }

    /**
     * Retrieve the default unit
     *
     * @return Yilinker\Bundle\CoreBundle\ProductUnit
     */
    public function getDefaultUnit($activeUnit = false)
    {
        if (is_null($this->defaultUnit)) {
            /**
             * Force retrieval of default unit from PHP Collection due to determination of quantity
             * based on warehouse
             */
            foreach($this->getUnits() as $unit){
                if($unit->getQuantity() > 0){
                    $this->defaultUnit = $unit;
                    break;
                }
            }
        }

        if (!$this->defaultUnit) {
            /**
             * Marked for refactor. This no longer applies for the warehouse implementation
             */
            $this->defaultUnit = $this->getUnits()->first();
        }

        if($activeUnit && $this->defaultUnit->getStatus() != ProductUnit::STATUS_ACTIVE){
            return null;
        }

        return $this->defaultUnit;
    }

    /**
     * Retrieve the featured unit
     *
     * @return Yilinker\Bundle\CoreBundle\ProductUnit
     */
    public function getFeaturedUnit($activeUnit = true)
    {
        if (is_null($this->featuredUnit)) {
            foreach($this->getUnits() as $unit){
                if(
                    $unit->getPublicQuantity(false) > 0 &&
                    (
                        !is_null($this->featuredUnit) ||
                        ($this->featuredUnit && $this->featuredUnit->hasCurrentPromo)
                    )
                ){
                    $this->featuredUnit = $unit;
                    break;
                }
            }
        }

        if (!$this->featuredUnit) {
            $this->featuredUnit = $this->getUnits()->first();
        }

        if($activeUnit && $this->featuredUnit->getStatus() != ProductUnit::STATUS_ACTIVE){
            return null;
        }

        return $this->featuredUnit;
    }

    public function getFirstUnit()
    {
        if (is_null($this->defaultUnit)) {
            $criteria = Criteria::create()
                                ->setFirstResult(0)
                                ->setMaxResults(1);
            $this->defaultUnit = $this->getUnits()->matching($criteria)->first();
        }

        return $this->defaultUnit;
    }

    public function setPrimaryImage($primaryImage)
    {
        $currentPrimaryImage = $this->getPrimaryImage();

        if ($currentPrimaryImage) {
            $currentPrimaryImage->setIsPrimary(false);    
        }
        
        if ($primaryImage) {
            $primaryImage->setIsPrimary(true);
        }
    }

    public function getPrimaryImage()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isPrimary", true))
                            ->andWhere(Criteria::expr()->eq('defaultLocale', $this->getLocale('en')))
                            ->setFirstResult(0)
                            ->setMaxResults(1);
        $productImage = $this->getImages()->matching($criteria)->first();
        if (!$productImage) {
            $criteria = Criteria::create()
                                  ->orderBy(array('productImageId' => 'ASC'))
                                  ->andWhere(Criteria::expr()->eq('defaultLocale', $this->getLocale('en')))
                                  ->setFirstResult(0)
                                  ->setMaxResults(1);
            $productImage = $this->getImages()->matching($criteria)->first();
        }

        return $productImage;
    }

    public function imageDir()
    {
        $id = $this->getProductId();

        return 'assets/images/uploads/products/'.$id;
    }

    public function getPrimaryImageLocation($isRaw = false)
    {
        return $this->getPrimaryImage() ? $this->getPrimaryImage()->getImageLocation($isRaw) : '';
    }

    public function getPrimaryImageLocationBySize($size = null)
    {
        return $this->getPrimaryImage() ? $this->getPrimaryImage()->getImageLocationBySize($size) : '';
    }

    /**
     * Add reviews
     *
     * @param ProductReview $reviews
     * @return Product
     */
    public function addReview(ProductReview $reviews)
    {
        $this->reviews[] = $reviews;

        return $this;
    }

    /**
     * Remove reviews
     *
     * @param ProductReview $reviews
     */
    public function removeReview(ProductReview $reviews)
    {
        $this->reviews->removeElement($reviews);
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * Add customBrand
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomBrand $customBrand
     * @return Product
     */
    public function addCustomBrand(\Yilinker\Bundle\CoreBundle\Entity\CustomBrand $customBrand)
    {
        $this->customBrand[] = $customBrand;

        return $this;
    }

    /**
     * Remove customBrand
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomBrand $customBrand
     */
    public function removeCustomBrand(\Yilinker\Bundle\CoreBundle\Entity\CustomBrand $customBrand)
    {
        $this->customBrand->removeElement($customBrand);
    }

    /**
     * Get customBrand
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCustomBrand()
    {
        return $this->customBrand;
    }

    public function getCustomBrandString()
    {
        $customBrands = array();

        foreach($this->customBrand as $brand){
            array_push($customBrands, $brand->getName());
        }

        return implode(", ", $customBrands);
    }

    public function getVisibleReviews()
    {
        if (is_null($this->visibleReviews)) {
            $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('isHidden', false))
                                          ->orWhere(Criteria::expr()->isNull('isHidden'));
            $this->visibleReviews = $this->getReviews()->matching($criteria);
        }

        return $this->visibleReviews;
    }

    public function getVisibleReviewsCount()
    {
        if (is_null($this->visibleReviews)) {
            $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('isHidden', false));
            $this->visibleReviews = $this->getReviews()->matching($criteria);
        }

        return count($this->visibleReviews);
    }

    public function getReviewRating()
    {
        if (is_null($this->reviewRating)) {
            $reviews = $this->getVisibleReviews()->toArray();
            $totalRating = array_reduce($reviews, function($carry, $item) {
                $carry += $item->getRating();

                return $carry;
            });

            if(!is_null($totalRating) && count($reviews) > 0){
                $this->reviewRating = $totalRating / count($reviews);
                $this->reviewRating = round($this->reviewRating);
            }
            else {
                $this->reviewRating = 0;
            }
        }

        return $this->reviewRating;
    }

    /**
     * Get the category details
     *
     * @return mixed
     */
    public function getFlattenedCategory()
    {
        $category = $this->getProductCategory();

        if(!is_null($category)){
            return array(
                'categoryId' => $category->getProductCategoryId(),
                'categoryName' => $category->getName(),
            );
        }
        else{
            return array(
                'categoryId' => '',
                'categoryName' => '',
            );
        }
    }

    /**
     * Get category keyword. Used for generating the search index
     *
     * @return string
     */
    public function getCategoryKeyword()
    {
        $category = $this->getProductCategory();

        if(!is_null($category)){
            return $category->getProductCategoryId() . '|' . $category->getName();
        }
        else{
            return "";
        }
    }

    /**
     * Get the seller details
     *
     * @return mixed
     */
    public function getFlattenedSeller()
    {
        $seller = $this->getUser();
        return array(
            'sellerId'        => $seller->getUserId(),
            'sellerStorename' => $seller->getStorename(),
            'sellerIsActive'  => $seller->getIsActive(),
        );
    }

    /**
     * Get the brand name of the product
     *
     * @return string
     */
    public function getBrandName()
    {
        $brand = $this->getBrand();
        $customBrand = $this->getCustomBrand();
        if((!$brand || $brand->getBrandId() === Brand::CUSTOM_BRAND_ID) && count($customBrand) > 0){
            return $customBrand->first()->getName();
        }

        return $brand ? $brand->getName(): '';
    }

    /**
     * Get the brand details
     *
     * @return mixed
     */
    public function getFlattenedBrand()
    {
        $brand = $this->getBrand();

        return array(
            'brandId' => $brand ? $brand->getBrandId() : null,
            'brandName' => $brand ? $this->getBrandName() : null,
        );
    }

    /**
     * Get the default price
     *
     * @return mixed
     */
    public function getDefaultPrice()
    {
        $defaultUnit = $this->getDefaultUnit();
        $discountedPrice = $defaultUnit ? $defaultUnit->getDiscountedPrice() : "0";

        return $discountedPrice ? $discountedPrice: $this->getOriginalPrice();
    }

    public function getOriginalPrice()
    {
        $defaultUnit = $this->getDefaultUnit();

        return $defaultUnit ? $defaultUnit->getPrice() : "0";
    }

    public function getDiscount()
    {
        $defaultUnit = $this->getDefaultUnit();

        return $defaultUnit ? $defaultUnit->getDiscount() : 0;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Product
     */
    public function setStatus($status, $mainStatus = false)
    {
        $country = $this->getCountry();
        if (!$mainStatus && $country && $productCountry = $this->getProductCountry($country)) {
            $productCountry->setStatus($status);
        }
        else {
            $this->status = $status;
        }

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus($mainStatus = false)
    {
        $country = $this->getCountry();
        if (!$mainStatus && $country && $status = $this->getProductCountryStatus($country)) {
            return $status;
        }

        return $this->status;
    }

    /**
     * Set wishlistCount
     *
     * @param wishlistCount
     * @return Product
     */
    public function setWishlistCount($wishlistCount)
    {
        $this->wishlistCount = $wishlistCount;

        return $this;
    }

    /**
     * Get wishlistCount
     *
     * @return $wishlistCount
     */
    public function getWishlistCount()
    {
        return $this->wishlistCount;
    }

    /**
     * Add customizedCategoryLookup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $customizedCategoryLookup
     * @return Product
     */
    public function addCustomizedCategoryLookup(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $customizedCategoryLookup)
    {
        $this->customizedCategoryLookup[] = $customizedCategoryLookup;

        return $this;
    }

    /**
     * Remove customizedCategoryLookup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $customizedCategoryLookup
     */
    public function removeCustomizedCategoryLookup(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $customizedCategoryLookup)
    {
        $this->customizedCategoryLookup->removeElement($customizedCategoryLookup);
    }

    /**
     * Get customizedCategoryLookup
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCustomizedCategoryLookup()
    {
        return $this->customizedCategoryLookup;
    }

    /**
     * Add orderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts
     * @return Product
     */
    public function addOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts)
    {
        $this->orderProducts[] = $orderProducts;

        return $this;
    }

    /**
     * Remove orderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts
     */
    public function removeOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts)
    {
        $this->orderProducts->removeElement($orderProducts);
    }

    /**
     * Get orderProducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrderProducts()
    {
        return $this->orderProducts;
    }

    /**
     * Get orderProducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function filterOrderProductsByDate($dateStart, $dateEnd,$sku=null)
    {
        $gte = Criteria::expr()->gte("dateAdded", $dateStart);
        $lte = Criteria::expr()->lte("dateAdded", $dateEnd);

        $criteria = Criteria::create()->andWhere($lte)->andWhere($gte);

        if (!is_null($sku)) {
            $equalSku = Criteria::expr()->eq("sku", $sku);
            $criteria = Criteria::create()->andWhere($lte)->andWhere($gte)->andWhere($equalSku);
        }

        return $this->getOrderProducts()->matching($criteria);
    }

    /**
     * Set manufacturerProductMap
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap $manufacturerProductMap
     * @return Product
     */
    public function setManufacturerProductMap(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap $manufacturerProductMap = null)
    {
        $this->manufacturerProductMap = $manufacturerProductMap;

        return $this;
    }

    /**
     * Get manufacturerProductMap
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap
     */
    public function getManufacturerProductMap()
    {
        return $this->manufacturerProductMap;
    }

    /**
     * Add productVisits
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductVisit $productVisits
     * @return Product
     */
    public function addProductVisit(\Yilinker\Bundle\CoreBundle\Entity\ProductVisit $productVisits)
    {
        $this->productVisits[] = $productVisits;

        return $this;
    }

    /**
     * Remove productVisits
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductVisit $productVisits
     */
    public function removeProductVisit(\Yilinker\Bundle\CoreBundle\Entity\ProductVisit $productVisits)
    {
        $this->productVisits->removeElement($productVisits);
    }

    /**
     * Get productVisits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductVisits()
    {
        return $this->productVisits;
    }

    public function getProductVisitTodayByIp($ipAddress)
    {
        $lte = Criteria::expr()->lte("dateAdded", Carbon::now()->endOfDay());
        $gte = Criteria::expr()->gte("dateAdded", Carbon::now()->startOfDay());
        $eq = Criteria::expr()->eq("ipAddress", $ipAddress);

        $criteria = Criteria::create()
                        ->andWhere($lte)
                        ->andWhere($gte)
                        ->andWhere($eq);

        $productVisits = $this->getProductVisits()->matching($criteria);

        return $productVisits;
    }

    public function getInventory(
        $forPublic = false,
        $handleMultiple = false,
        $isRaw = false
    ){
        $inventory = 0;

        $units = $this->getUnits();

        foreach ($units as $unit) {
            if($forPublic){
                $inventory += (int)$unit->getPublicQuantity($handleMultiple);
            }
            else{
                $inventory += (int)$unit->getQuantity($isRaw);
            }
        }

        return $inventory;
    }

    public function getSkus()
    {
        $units = $this->getUnits();
        $skus = array();
        foreach ($units as $unit) {
            $skus[] = $unit->getSku();
        }

        return $skus;
    }

    /**
     * Returns a boolean field that determines if the product is a reseller product
     *
     * @return boolean
     */
    public function getIsResold()
    {
        $store = $this->getUser()->getStore();
        if($store){
            return intval($store->getStoreType()) === Store::STORE_TYPE_RESELLER;
        }

        return false;
    }

    /**
     * Returns a boolean field that determines if the product is an inhouse seller/affiliate product
     *
     * @return boolean
     */
    public function getIsInhouse()
    {
        $store = $this->getUser()->getStore();
        if($store){
            return $store->getIsInhouse();
        }

        return false;
    }

    /**
     * Returns if the product is editable
     *
     * @return boolean
     */
    public function getIsEditable()
    {
        return in_array($this->getStatus(), self::$editableStatuses) && $this->getIsResold() === false;
    }

    public function getIsTranslatable()
    {
        // if the name does not exist then the product is always translatable
        // check only for status when the intent is edit not when the intent is
        // add a translation
        if (!$this->getName()) {
            return true;
        }

        return $this->getIsEditable() && $this->getIsResold() === false;
    }

    /**
     * Set youtubeVideoUrl
     *
     * @param string $youtubeVideoUrl
     * @return Product
     */
    public function setYoutubeVideoUrl($youtubeVideoUrl)
    {
        $this->youtubeVideoUrl = $youtubeVideoUrl;

        return $this;
    }

    /**
     * Get youtubeVideoUrl
     *
     * @return string
     */
    public function getYoutubeVideoUrl()
    {
        return $this->youtubeVideoUrl;
    }

    /**
     * @return ProductCategory|CustomizedCategory
     */
    public function getStoreCategory()
    {
        $category = null;

        $lookups = $this->getCustomizedCategoryLookup();
        if($lookups){
            foreach ($lookups as $lookup) {
                $category = $lookup->getCustomizedCategory();
                break;
            }
        }

        if (!$category) {
            $category = $this->getProductCategory();
        }

        return $category;
    }

    /**
     * @return string '{product_category_id}|{name}'
     */
   public function getCustomCategories()
    {
        $productCategory = $this->getProductCategory();
        $categoriesLookup = $this->getCustomizedCategoryLookup();

        $customCategoryKeyword = array();
        if(!empty($categoriesLookup)){
            foreach($categoriesLookup as $categoryLookup){

                $customCategory = $categoryLookup->getCustomizedCategory();

                $object = array(
                    "id" => $customCategory->getCustomizedCategoryId(),
                    "name" => $customCategory->getName()
                );

                if(!in_array($object, $customCategoryKeyword)){
                    array_push($customCategoryKeyword, $object);
                }
            }
        }

        return json_encode($customCategoryKeyword);
    }

    /**
     * @return string '{product_category_id}|{name}'
     */
   public function getCustomCategoryIds()
    {
        $productCategory = $this->getProductCategory();
        $categoriesLookup = $this->getCustomizedCategoryLookup();

        $customCategoryIds = array();
        if(!empty($categoriesLookup)){
            foreach($categoriesLookup as $categoryLookup){

                $customCategory = $categoryLookup->getCustomizedCategory();

                if(!in_array($customCategory->getCustomizedCategoryId(), $customCategoryIds)){
                    array_push($customCategoryIds, array(
                        "customCategoryId" => $customCategory->getCustomizedCategoryId()
                    ));
                }
            }
        }

        return $customCategoryIds;
    }

    /**
     * Add productRemarks
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductRemarks $productRemarks
     * @return Product
     */
    public function addProductRemark(\Yilinker\Bundle\CoreBundle\Entity\ProductRemarks $productRemarks)
    {
        $this->productRemarks[] = $productRemarks;

        return $this;
    }

    /**
     * Remove productRemarks
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductRemarks $productRemarks
     */
    public function removeProductRemark(\Yilinker\Bundle\CoreBundle\Entity\ProductRemarks $productRemarks)
    {
        $this->productRemarks->removeElement($productRemarks);
    }

    /**
     * Get productRemarks
     *
     * @param bool $isDesc
     * @param int $productStatus
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductRemarks($isDesc = false, $productStatus = Product::REJECT)
    {
        $eq = Criteria::expr()->eq("productStatus", $productStatus);
        $criteria = Criteria::create ()
                        ->where($eq);

        if ($isDesc === true) {
            $criteria->orderBy(array('dateAdded' => 'DESC'));
        }

        return $this->productRemarks ? $this->productRemarks->matching($criteria): array();
    }

    /**
     * Get latest remarks
     *
     * @return mixed
     */
    public function getLatestRemark ()
    {
        $criteria = Criteria::create ()
                            ->orderBy(array('dateAdded' => 'DESC'))
                            ->setMaxResults(1);

        $productRemark = $this->getProductRemarks()->matching($criteria);

        return sizeof($productRemark) > 0 ? $productRemark[0] : null;
    }

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ShippingCategory
     */
    private $shippingCategory;


    /**
     * Set shippingCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ShippingCategory $shippingCategory
     * @return Product
     */
    public function setShippingCategory(\Yilinker\Bundle\CoreBundle\Entity\ShippingCategory $shippingCategory = null)
    {
        $this->shippingCategory = $shippingCategory;

        return $this;
    }

    /**
     * Get shippingCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ShippingCategory
     */
    public function getShippingCategory()
    {
        return $this->shippingCategory;
    }

    /**
     * Get status name
     *
     * @return string
     */
    public function getStatusName($status = null)
    {
        $value = "";
        if (is_null($status)) {
            $status = $this->getStatus();
        }

        switch($status){
            case self::DRAFT:
                $value = "Draft";
                break;
            case self::FOR_REVIEW:
                $value = "For Review";
                break;
            case self::ACTIVE:
                $value = "Active";
                break;
            case self::DELETE:
                $value = "Delete";
                break;
            case self::FULL_DELETE:
                $value = "Full Delete";
                break;
            case self::REJECT:
                 $value = "Rejected";
                 break;
            case self::INACTIVE:
                $value = "Inactive";
                break;
            case self::FOR_COMPLETION:
                $value = "For Completion";
                break;
        }

        return $value;
    }

    /**
     * Set defaultLocale
     *
     * @param string $defaultLocale
     * @return Product
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Get defaultLocale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    public static function getProductStatuses($exclude = array(self::FULL_DELETE))
    {
        $statuses = array(
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DRAFT => 'Draft',
            self::DELETE => 'Deleted',
            self::FULL_DELETE => 'Full Deleted',
            self::FOR_REVIEW => 'Under Review',
            self::REJECT => 'Rejected',
            self::FOR_COMPLETION => 'For Completion'
        );

        foreach ($exclude as $value) {
            unset($statuses[$value]);
        }

        return $statuses;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productWarehouses;

    public $productWarehouse;

    /**
     * Add productWarehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse $productWarehouses
     * @return Product
     */
    public function addProductWarehouse(\Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse $productWarehouses)
    {
        $this->productWarehouses[] = $productWarehouses;

        return $this;
    }

    /**
     * Remove productWarehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse $productWarehouses
     */
    public function removeProductWarehouse(\Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse $productWarehouses)
    {
        $this->productWarehouses->removeElement($productWarehouses);
    }

    /**
     * Get productWarehouses
     *
     * @param boolean $all
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductWarehouses($all = false)
    {
        if($all){
            $productWarehouses = $this->productWarehouses;
        }
        else{
            $criteria = Criteria::create()
                      ->where(Criteria::expr()->eq('countryCode', $this->getCountryCode()));

            $productWarehouses = $this->productWarehouses;

            if($productWarehouses){
                $productWarehouses = $this->productWarehouses->matching($criteria);
            }
        }

        return $productWarehouses;
    }

    /**
     * Get the user warehouse based on priority
     *
     * @param int $priority
     * @return Yilinker\Bundle\CoreBundle\Entity\UserWarehouse
     */
    public function getWarehouse($priority = ProductWarehouse::DEFAULT_PRIORITY)
    {
        $this->productWarehouse = $this->getProductWarehouse($priority);
        if ($this->productWarehouse) {
            return $this->productWarehouse->getUserWarehouse();
        }

        return null;
    }

    public function getProductWarehouse($priority = ProductWarehouse::DEFAULT_PRIORITY)
    {
        $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('priority', (int) $priority));

        if ($productWarehouses = $this->getProductWarehouses()) {
            $result = $productWarehouses->matching($criteria);

            $productWarehouse = $result->first();
            if ($productWarehouse) {
                $userWarehouse = $productWarehouse->getUserWarehouse();
                if ($userWarehouse && $userWarehouse->getIsDelete() === false) {
                    return $productWarehouse;
                }
            }
        }

        return null;
    }

    /**
     * Add productGroups
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductGroup $productGroups
     * @return Product
     */
    public function addProductGroup(\Yilinker\Bundle\CoreBundle\Entity\ProductGroup $productGroups)
    {
        $this->productGroups[] = $productGroups;

        return $this;
    }

    /**
     * Remove productGroups
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductGroup $productGroups
     */
    public function removeProductGroup(\Yilinker\Bundle\CoreBundle\Entity\ProductGroup $productGroups)
    {
        $this->productGroups->removeElement($productGroups);
    }

    /**
     * Get productGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductGroups()
    {
        return $this->productGroups;
    }

    public function getProductGroupsName()
    {
        $productGroups = array();

        foreach ($this->productGroups as $group) {
            $productGroups[] = (string) $group;
        }

        return $productGroups;
    }

    /**
     * Returns codes of the countries where the product is available
     *
     * @return string[]
     */
    public function getAllCountryCodes()
    {
        $codes = array();
        $productWarehouses = $this->productWarehouses;
        foreach($productWarehouses as $warehouse){
            $codes[] = $warehouse->getCountryCode();
        }
        $manufacturerProductMap = $this->getManufacturerProductMap();
        if ($manufacturerProductMap && $manufacturerProduct = $manufacturerProductMap->getManufacturerProduct()) {
            if ($manufacturerProduct) {
                $manufacturerProductCountries = $manufacturerProduct->getManufacturerProductCountries();
                foreach ($manufacturerProductCountries as $manufacturerProductCountry) {
                    $country = $manufacturerProductCountry->getCountry();
                    $codes[] = $country->getCode(true);
                }
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Add productCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCountry $productCountries
     * @return Product
     */
    public function addProductCountry(\Yilinker\Bundle\CoreBundle\Entity\ProductCountry $productCountries)
    {
        $this->productCountries[] = $productCountries;

        return $this;
    }

    /**
     * Remove productCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCountry $productCountries
     */
    public function removeProductCountry(\Yilinker\Bundle\CoreBundle\Entity\ProductCountry $productCountries)
    {
        $this->productCountries->removeElement($productCountries);
    }

    /**
     * Get productCountries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductCountries()
    {
        return $this->productCountries;
    }

    public function getProductCountry($country)
    {
        $criteria = Criteria::create()
                  ->where(Criteria::expr()->eq('country', $country));
        $productCountry = $this->productCountries->matching($criteria)->first();

        return $productCountry;
    }

    /**
     * Get product country status
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country
     * @return int
     */
    public function getProductCountryStatus($country)
    {
        $productCountry = $this->getProductCountry($country);

        return $productCountry ? $productCountry->getStatus() : $this->getStatus(true);
    }

    public function getProductCountryByCountry($country)
    {
       $criteria = Criteria::create()
                 ->where(Criteria::expr()->eq('country', $country));

        if($this->productCountries){
            return $this->productCountries->matching($criteria)->first();;
        }

       return null;
    }

    public function getProductCountryCodes()
    {
        $data = array();
        foreach ($this->getProductCountries() as $productCountry) {
            $data[] = $productCountry->getCountry()->getCode(true);
        }

        return $data;
    }

    public function getProductCountryByExcludedStatus($status)
    {
       $criteria = Criteria::create()
                 ->where(Criteria::expr()->neq('status', $status));

       $productCountries = $this->productCountries->matching($criteria);

       return $productCountries;
    }

    public function getProductCountryStatusName($country)
    {
        $status = $this->getProductCountryStatus($country);

        return $this->getStatusName($status);
    }

    public function hasCOD()
    {
        if ($this->isInhouseProduct()) {
            return true;
        }

        $warehouse = $this->getWarehouse();

        if (!$warehouse) {
            return false;
        }
        $warehouseCountry = $warehouse->getCountry();
        if ($warehouseCountry->getCode() != $this->getCountryCode()) {
            return false;
        }


        return $this->productWarehouse ? $this->productWarehouse->getIsCod(): false;
    }

    /**
     * Set isNotShippable
     *
     * @param boolean $isNotShippable
     * @return Product
     */
    public function setIsNotShippable($isNotShippable)
    {
        $this->isNotShippable = $isNotShippable;

        return $this;
    }

    /**
     * Get isNotShippable
     *
     * @return boolean
     */
    public function getIsNotShippable()
    {
        return $this->isNotShippable;
    }

    public function isInhouseProduct()
    {
//        return $this instanceof InhouseProduct;
      return $this->getIsInhouse();
    }

    /**
     * Add inhouseProductUsers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers
     * @return Product
     */
    public function addInhouseProductUser(\Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers)
    {
        $this->inhouseProductUsers[] = $inhouseProductUsers;

        return $this;
    }

    /**
     * Remove inhouseProductUsers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers
     */
    public function removeInhouseProductUser(\Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers)
    {
        $this->inhouseProductUsers->removeElement($inhouseProductUsers);
    }

    /**
     * Get inhouseProductUsers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInhouseProductUsers()
    {
        return $this->inhouseProductUsers;
    }

    public function getInhouseProductUserByUser($user)
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('user', $user));

        return $this->getInhouseProductUsers()->matching($criteria)->first();
    }
}
