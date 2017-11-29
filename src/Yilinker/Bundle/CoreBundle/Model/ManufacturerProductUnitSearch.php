<?php

namespace Yilinker\Bundle\CoreBundle\Model;

use Yilinker\Bundle\CoreBundle\Repository\ManufacturerProductUnitRepository;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;

class ManufacturerProductUnitSearch
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

    public function __construct()
    {
        $this->sortField = null;
        $this->sortDirection = null;
        $this->statuses = array();
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
            case ManufacturerProductUnitRepository::SORT_BY_DATE_ADDED:
                $sortField = 'dateCreated';
                break;
            default:
                $sortField = 'dateLastModified';
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
            case ManufacturerProductUnitRepository::SORT_DIRECTION_ASC:
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
            $statuses = array(ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ACTIVE);
        }

        return $statuses;
    }
    
}
