<?php

namespace Yilinker\Bundle\CoreBundle\Model;

use Yilinker\Bundle\CoreBundle\Repository\ManufacturerProductRepository;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;

class ManufacturerProductSearch
{
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
     * @var DateTime
     */
    private $beginDate;

    /**
     * @var DateTime
     */
    private $endDate;

    /**
     * Statuses
     *
     * @var int[]
     */
    private $statuses;

    private $brandIds;

    private $categoryIds;

    private $manufacturerIds;

    private $queryString;

    private $priceFrom;

    private $priceTo;

    public function __construct()
    {
        $this->priceFrom = "0.0000";
        $this->sortField = null;
        $this->sortDirection = null;
        $this->statuses = array();
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

    public function setPriceFrom($priceFrom)
    {
        if($priceFrom != "" && !is_null($priceFrom)){
            $this->priceFrom = floatval($priceFrom);
        }

        return $this;
    }

    public function getPriceFrom()
    {
        return $this->priceFrom;
    }

    public function setPriceTo($priceTo)
    {
        if($priceTo != "" && !is_null($priceTo)){
            $this->priceTo = floatval($priceTo);
        }

        return $this;
    }

    public function getPriceTo()
    {
        return $this->priceTo;
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

    public function getBrandIds()
    {
        return $this->brandIds;
    }

    public function setBrandIds($brandIds = array())
    {
        $this->brandIds = $brandIds;
        return $this;
    }

    public function getCategoryIds()
    {
        return $this->categoryIds;
    }

    public function setCategoryIds($categoryIds = array())
    {
        $this->categoryIds = $categoryIds;
        return $this;
    }

    public function getManufacturerIds()
    {
        return $this->manufacturerIds;
    }

    public function setManufacturerIds($manufacturerIds = array())
    {
        if ($manufacturerIds) {
            $this->manufacturerIds = is_array($manufacturerIds) ? $manufacturerIds: array($manufacturerIds);
        }

        return $this;
    }

    public function setSortField($sortType)
    {
        switch($sortType){
            case ManufacturerProductRepository::SORT_BY_NEW_TO_OLD:
                $sortField = 'dateAdded';
                $this->sortDirection = ManufacturerProductRepository::SORT_DIRECTION_DESC;
                break;
            case ManufacturerProductRepository::SORT_BY_OLD_TO_NEW:
                $sortField = 'dateAdded';
                $this->sortDirection = ManufacturerProductRepository::SORT_DIRECTION_ASC;
                break;
            case ManufacturerProductRepository::SORT_BY_RELEVANCE:
                $sortField = '_score';
                $this->sortDirection = ManufacturerProductRepository::SORT_DIRECTION_DESC;
                break;
            default:
                $sortField = 'name.rawName';
                $this->sortDirection = ManufacturerProductRepository::SORT_DIRECTION_ASC;
                break;
        }
        
        $this->sortField = $sortField;

        return $this;
    }

    public function getSortField()
    {
        return $this->sortField ? $this->sortField : "dateCreated";
    }

    public function setSortDirection($sortDirection)
    {
        switch($sortDirection){
            case ManufacturerProductRepository::SORT_DIRECTION_ASC:
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
            $statuses = array(ManufacturerProduct::STATUS_ACTIVE);
        }

        return $statuses;
    }
    
}
