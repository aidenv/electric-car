<?php

namespace Yilinker\Bundle\CoreBundle\Model;

use Yilinker\Bundle\CoreBundle\Repository\ProductRepository;
use Yilinker\Bundle\CoreBundle\Entity\Product;

class ProductSearch
{
    /**
     * Query String
     *
     * @var string
     */
    private $queryString;

    /**
     * Price range from
     *
     * @var string
     */
    private $priceFrom;

    /**
     * Price range to
     *
     * @var string
     */
    private $priceTo;

    /**
     * Product name
     *
     * @var boolean
     */
    private $name;

    /**
     * Product slug
     *
     * @var string
     */
    private $slug;

    /**
     * @var int[]
     */
    private $sellerIds;

    /**
     * Category Ids
     *
     * @var int[]
     */
    private $categoryIds;

    /**
     * Page to display
     *
     * @var int
     */
    private $page;

    /**
     * Results per page
     *
     * @var int
     */
    private $perPage;

    /**
     * Sort by field
     *
     * @var string
     */
    private $sortField;

    /**
     * Sort direction
     *
     * @var string
     */
    private $sortDirection;

    /**
     * Set brands
     *
     * @var string[]
     */
    private $brands;

    private $attributValues;

    private $beginDate;

    private $endDate;

    /**
     * Set subcategoryIds
     *
     * @var int[]
     */
    private $subcategoryIds;

    /**
     * Product statuses
     *
     * @var int[]
     */
    private $statuses;

    /**
     * is affiliate
     *
     * @var boolean
     */
    private $isInhouseProduct;

    /**
     * customCategoryIds
     *
     * @var boolean
     */
    private $customCategoryIds;

    /**
     * is promo products
     *
     * @var boolean
     */
    private $isPromoProduct;

    /**
     * Exact product id search
     *
     * @var int[]
     */
    private $productIds;

    /**
     * Country codes
     *
     * @var string[]
     */
    private $countryCode;

    /**
     * Warehouses
     * 
     * @var string[]
     */
    private $warehouses;

    /**
     * Elasticsearch field prefix
     *
     * @var string
     */
    private $fieldPrefix;

    /**
     * Set the store active status
     *
     * @var boolean
     */
    private $isActiveStore;

    public function __construct()
    {
        $this->priceFrom = "0.0000";
        $this->priceTo = null;
        $this->categoryIds = array();
        $this->brands = array();
        $this->subcategoryIds = array();
        $this->sortField = null;
        $this->sortDirection = null;
        $this->statuses = array();
        $this->isInhouseProduct = null;
        $this->customCategoryIds = array();
        $this->isPromoProducts = null;
        $this->productIds = array();
        $this->fieldPrefix = "";
        $this->countryCode = array();
        $this->warehouses = array();
        $this->isActiveStore = true;
        $this->slug = null;
    }

    public function setPriceFrom($priceFrom)
    {
        if($priceFrom != ""){
            $this->priceFrom = $priceFrom;
        }

        return $this;
    }

    public function getPriceFrom()
    {
        return $this->priceFrom;
    }

    public function setSlug($slug)
    {
        if($slug){
            $this->slug = $slug;
        }

        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setPriceTo($priceTo)
    {
        if($priceTo != ""){
            $this->priceTo = $priceTo;
        }

        return $this;
    }

    public function getPriceTo()
    {
        return $this->priceTo;
    }

    public function clearPrices()
    {
        $this->priceFrom = "0.0000";
        $this->priceTo = null;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }
    
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
        
        return $this;
    }

    public function setSellerIds($sellerIds)
    {
        if (!is_array($sellerIds) && $sellerIds) {
            $sellerIds = array($sellerIds);
        }

        $this->sellerIds = $sellerIds;

        return $this;
    }

    public function getSellerIds()
    {
        return $this->sellerIds;
    }

    public function setCategoryIds($categoryIds)
    {
        if(!is_array($categoryIds)){
            $categoryIds = $categoryIds ? array($categoryIds) : null;
        } 

        $this->categoryIds = $categoryIds;

        return $this;
    }

    public function getCategoryIds()
    {
        return $this->categoryIds;
    }

    public function setCustomCategoryIds($customCategoryIds)
    {
        if(!is_array($customCategoryIds)){
            $customCategoryIds = $customCategoryIds ? array($customCategoryIds) : null;
        } 

        $this->customCategoryIds = $customCategoryIds;

        return $this;
    }

    public function getCustomCategoryIds()
    {
        return $this->customCategoryIds;
    }

    public function getPage()
    {
        return $this->page ? $this->page : 1;
    }

    public function setPage($page)
    {
        if ($page != null) {
            $this->page = $page;
        }

        return $this;
    }

    public function getPerPage()
    {
        return $this->perPage ? $this->perPage : 15;
    }

    public function setPerPage($perPage = null)
    {
        if($perPage != null){
            $this->perPage = $perPage;
        }

        return $this;
    }

    public function setSortField($sortType)
    {
        switch($sortType){
            case ProductRepository::BYPRICE:
                $sortField = 'defaultPrice';
                break;
            case ProductRepository::BYDATE:
                $sortField = 'dateLastModified';
                break;
            case ProductRepository::BYPOPULARITY:
                $sortField = 'clickCount';
                break;
            case ProductRepository::ALPHABETICAL:
                $sortField = 'name.rawName';
                break;
            default:
                $sortField = '_score';
                break;
        }
        
        $this->sortField = $sortField;

        return $this;
    }

    public function getSortField()
    {
        return $this->sortField ? $this->sortField : "name";
    }

    public function setSortDirection($sortDirection)
    {
        switch($sortDirection){
            case ProductRepository::DIRECTION_ASC:
                $sortDirection = 'asc';
                break;
            default:
                $sortDirection = 'desc';
                break;
        }
        
        $this->sortDirection = $sortDirection;

        return $this;
    }

    public function getSortDirection()
    {
        return $this->sortDirection ? $this->sortDirection : "desc";
    }

    public function getBrands()
    {
        return $this->brands;
    }

    public function setBrands($brand = null)
    {
        if($brand != null){
            $brands = is_array($brand) ? $brand : array($brand);
            $this->brands = $brands;
        }

        return $this;
    }

    public function getSubcategoryIds()
    {
        return $this->subcategoryIds;
    }

    public function setSubcategoryIds($subcategoryIds = null)
    {
        if($subcategoryIds != null){
            

            $subcategoryIds = is_array($subcategoryIds) ? $subcategoryIds : array($subcategoryIds);
            $this->subcategoryIds = $subcategoryIds;
        }

        return $this;
    }

    public function setAttributeValues($attributeValues)
    {
        $this->attributeValues = $attributeValues;

        return $this;
    }

    public function getAttributeValues()
    {
        $attributeValues = array();
        if (is_array($this->attributeValues)) {
            foreach ($this->attributeValues as $key => $attributeValue) {
                if (is_string($key)) {
                    $attributeValues[] = $key.'|'.$attributeValue;
                }
                else {
                    $attributeValues[] = $attributeValue;
                }
            }
        }

        return $attributeValues;
    }

    public function setBeginDate($beginDate)
    {
        $this->beginDate = $beginDate;
    }

    public function getBeginDate()
    {
        return $this->beginDate;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function setStatuses($statuses)
    {
        if($statuses !== null){
            if (!is_array($statuses) && $statuses) {
                $statuses = array($statuses);
            }

            $this->statuses = $statuses;
        }
        
        return $this;
    }

    public function getStatuses()
    {
        $statuses = $this->statuses;
        if(count($statuses) === 0){
            $statuses = array(Product::ACTIVE);
        }

        return $statuses;
    }

    /**
     * Set isInhouseProduct
     *
     * @param boolean $isInhouseProduct
     */
    public function setIsInhouseProduct($isInhouseProduct)
    {
        $this->isInhouseProduct = $isInhouseProduct;
    }

    /**
     * Return isInhouseProduct
     *
     * @return boolean
     */
    public function getIsInhouseProduct()
    {
        return $this->isInhouseProduct;
    }

    /**
     * Set isPromoProduct
     *
     * @param boolean $isPromoProduct
     */
    public function setIsPromoProduct($isPromoProduct)
    {
        $this->isPromoProduct = $isPromoProduct;

        return $this;
    }

    /**
     * Return isPromoProduct
     *
     * @return boolean
     */
    public function getIsPromoProduct()
    {
        return $this->isPromoProduct;
    }

    /**
     * Set productIds
     *
     * @param int|int[] $productIds
     */
    public function setProductIds($productIds)
    {
        if($productIds != null){           
            $productIds = is_array($productIds) ? $productIds : array($productIds);
            $this->productIds = $productIds;
        }

        return $this;
    }

    /**
     * Return isPromoProduct
     *
     * @return boolean
     */
    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * Set the country code
     *
     * @param string[] $codes
     * @return ProductSearch
     */
    public function setCountryCodes($codes)
    {
        $this->countryCode = $codes;

        return $this;
    }

    /**
     * Get the country codes
     *
     * @return string[]
     */
    public function getCountryCodes()
    {
        $lowerCased = array();
        foreach($this->countryCode as $code){
            $lowerCased[] = strtolower($code);
        }
        
        return $lowerCased;
    }

    public function addWarehouse($warehouse)
    {
        $this->warehouses[] = (string) $warehouse;

        return $this;
    }

    public function setWarehouses($warehouses)
    {
        $this->warehouses = $warehouses;

        return $this;
    }

    public function getWarehouses()
    {
        return $this->warehouses;
    }

    /**
     * Set the field prefix
     * 
     * @param string $fieldPrefix
     * @return ProductSearch
     */
    public function setFieldPrefix($fieldPrefix = "")
    {
        $this->fieldPrefix = $fieldPrefix;
        
        return $this;
    }

    /**
     * Get the field prefix
     *
     * @return string
     */ 
    public function getFieldPrefix()
    {
        return $this->fieldPrefix;
    }
    
    /*
     * Set the store activity status    
     *
     * @param int $status
     */
    public function setIsActiveStore($status)
    {
        $this->isActiveStore = $status;

        return $this;
    }
    
    /**
     * Get the store activity status    
     *
     * @param int $isActiveStore
     */
    public function getIsActiveStore()
    {
        return $this->isActiveStore;
    }    

}
