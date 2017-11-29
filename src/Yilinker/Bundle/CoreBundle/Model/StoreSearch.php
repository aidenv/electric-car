<?php

namespace Yilinker\Bundle\CoreBundle\Model;

use Yilinker\Bundle\CoreBundle\Repository\StoreRepository;

class StoreSearch
{
    /**
     * Query String
     *
     * @var string
     */
    private $queryString;

    /**
     * Per page
     *
     * @var int
     */
    private $perPage;

    /**
     * Current Page
     *
     * @var int
     */
    private $page;

    /**
     * Sort field
     *
     * @var string
     */
    private $sortField = null;

    /**
     * Accreditation Level
     *
     * @var int
     */
    private $accreditationLevel = null;

    /**
     * Sort direction
     *
     * @var string
     */
    private $sortDirection = null;

    /**
     * Has Product
     *
     * @var boolean
     */
    private $hasProduct = null;

    public function getQueryString()
    {
        return $this->queryString;
    }
    
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
        
        return $this;
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
            case StoreRepository::ALPHABETICAL:
                $sortField = 'storeName';
                break;
            default:
                $sortField = 'dateAdded';
                break;
        }
        
        $this->sortField = $sortField;

        return $this;
    }

    public function getSortField()
    {
        return $this->sortField ? $this->sortField : "dateAdded";
    }

    public function setSortDirection($sortDirection)
    {
        switch($sortDirection){
            case StoreRepository::DIRECTION_ASC:
                $sortDirection = 'asc';
                break;
            case StoreRepository::DIRECTION_DESC:
                $sortDirection = 'desc';
                break;
            default:
                $sortField = '_score';
                break;
        }
        
        $this->sortDirection = $sortDirection;

        return $this;
    }

    public function getSortDirection()
    {
        return $this->sortDirection ? $this->sortDirection : "desc";
    }

    /**
     * Set the accreditation level
     *
     * @param int $accreditationLevel
     */
    public function setAccreditationLevel($accreditationLevel)
    {
        $this->accreditationLevel = $accreditationLevel;
    }
    
    /**
     * Get the accreditation level
     *
     * @return integer $accreditationLevel
     */
    public function getAccreditationLevel()
    {
        return $this->accreditationLevel;
    }

    /**
     * Set the hasProduct filter
     *
     * @param boolean $hasProduct
     */
    public function setHasProduct($hasProduct)
    {
        $this->hasProduct = $hasProduct;
    }
    
    /**
     * Get the hasProduct filter
     *
     * @return boolean
     */
    public function getHasProduct()
    {
        return $this->hasProduct;
    }

}
