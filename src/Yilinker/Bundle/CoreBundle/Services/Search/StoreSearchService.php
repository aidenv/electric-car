<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search;

use Yilinker\Bundle\CoreBundle\Model\StoreSearch;
use FOS\ElasticaBundle\Repository;

class StoreSearchService
{
    const RESULT_PER_PAGE = 30;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Services\Search\Repository\StoreSearchRepository
     */
    private $elasticStoreSearchRepository;

    /**
     * Constructor
     *
     * @param FOS\ElasticaBundle\Manager\RepositoryManager $elasticaRepositoryManager
     */
    public function __construct($elasticaRepositoryManager)
    {
        $this->elasticStoreSearchRepository = $elasticaRepositoryManager->getRepository('YilinkerCoreBundle:Store');
    }

    /**
     * Search Store with elastic search
     *
     * @param string $queryString
     * @param int $accreditationLevel
     * @param string $sortType
     * @param string $sortDirection
     * @param int $page
     * @param int $perPage
     * @param bool getResults
     * @param bool $hasProduct
     * @return mixed
     */
    public function searchStoreWithElastic(
        $queryString = null, 
        $accreditationLevel = null,
        $sortType = null,
        $sortDirection = null,
        $page = 1,
        $perPage = self::RESULT_PER_PAGE,
        $getResults = true,
        $hasProduct = true
    ){
        $searchModel = new StoreSearch();
        $searchModel->setQueryString($queryString);
        $searchModel->setPage($page);
        $searchModel->setPerPage($perPage);
        $searchModel->setAccreditationLevel($accreditationLevel);
        $searchModel->setSortField($sortType);
        $searchModel->setSortDirection($sortDirection);
        $searchModel->setHasProduct($hasProduct);

        $searchResults = $this->elasticStoreSearchRepository
                              ->search($searchModel, $getResults);

        return array(
            'stores' => $searchResults['stores'],
            'totalResultCount' => $searchResults['totalProductCount'],
        );
    }
}
