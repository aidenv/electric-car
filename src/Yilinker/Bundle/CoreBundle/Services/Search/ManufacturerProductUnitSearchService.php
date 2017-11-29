<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search;

use Yilinker\Bundle\CoreBundle\Model\ManufacturerProductUnitSearch;
use FOS\ElasticaBundle\Repository;

class ManufacturerProductUnitSearchService
{

    const RESULT_PER_PAGE = 30;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Services\Search\Repository\ManufacturerProductUnitSearchRepository
     */
    private $elasticaManufacturerProductUnitSearchRepository;

    /**
     * Constructor
     *
     * @param FOS\ElasticaBundle\Manager\RepositoryManager $elasticaRepositoryManager
     */
    public function __construct($elasticaRepositoryManager)
    {
        $this->elasticaManufacturerProductUnitSearchRepository = $elasticaRepositoryManager->getRepository('YilinkerCoreBundle:ManufacturerProductUnit');
    }
    
    /**
     * Search ManufacturerProducts with elastic search
     *
     * @param DateTime $beginDate
     * @param DateTime $endDate
     * @param int[] $statuses
     * @param int $page
     * @param int $perPage
     * @param bool getResults
     * @param $sortType = null,
     * @param $sortDirection = null,
     * @return mixed
     */
    public function searchWithElastic(
        $beginDate = null,
        $endDate = null,
        $statuses = null,
        $page = 1,
        $perPage = self::RESULT_PER_PAGE,
        $sortType = null,
        $sortDirection = null,
        $getResults = true
    ){
        $searchModel = new ManufacturerProductUnitSearch();
        $searchModel->setPage($page);
        $searchModel->setPerPage($perPage);
        $searchModel->setSortField($sortType);
        $searchModel->setSortDirection($sortDirection);
        $searchModel->setBeginDate($beginDate);
        $searchModel->setEndDate($endDate);
        $searchModel->setStatuses($statuses);

        $searchResults = $this->elasticaManufacturerProductUnitSearchRepository
                              ->search($searchModel, $getResults);

        $perPage = !$perPage? self::RESULT_PER_PAGE : $perPage;

        return array(
            'manufacturerProductUnits' => $searchResults['manufacturerProductUnits'],
            'totalResultCount' => $searchResults['totalCount'],
            'totalPage'        => (int) ceil($searchResults['totalCount']/$perPage),
        );
    }

    
}
