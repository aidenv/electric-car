<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search;

use Yilinker\Bundle\CoreBundle\Model\ManufacturerProductSearch;
use FOS\ElasticaBundle\Repository;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;

class ManufacturerProductSearchService
{

    const RESULT_PER_PAGE = 30;

    private $elasticaManufacturerProductUnitSearchRepository;

    public function __construct($elasticaRepositoryManager)
    {
        $this->elasticaManufacturerProductSearchRepository = $elasticaRepositoryManager->getRepository('YilinkerCoreBundle:ManufacturerProduct');
    }
    
    public function searchWithElastic(
        $queryString = null, 
        $page = 1,
        $perPage = self::RESULT_PER_PAGE,
        $sortType = null,
        $brand = null,
        $category = null,
        $priceFrom = null,
        $priceTo = null,
        $statuses = null,
        $getResults = true,
        $manufacturer = null
    ){
        $searchModel = new ManufacturerProductSearch();
        $searchModel->setQueryString($queryString);
        $searchModel->setPage($page);
        $searchModel->setPerPage($perPage);
        $searchModel->setSortField($sortType);
        $searchModel->setBrandIds($brand);
        $searchModel->setCategoryIds($category);
        $searchModel->setPriceFrom($priceFrom);
        $searchModel->setPriceTo($priceTo);
        $searchModel->setStatuses($statuses);
        $searchModel->setManufacturerIds($manufacturer);

        $searchResults = $this->elasticaManufacturerProductSearchRepository
                              ->search($searchModel, $getResults);

        $perPage = !$perPage? self::RESULT_PER_PAGE : $perPage;

        $aggregations = array(
            "categoryIds" => array(),
            "brandIds" => array(),
        );

        if(array_key_exists("categoryId", $searchResults["aggregations"])){
            foreach ($searchResults["aggregations"]["categoryId"]["buckets"] as $bucket) {
                array_push($aggregations["categoryIds"], $bucket["key"]);
            }
        }

        if(array_key_exists("brandId", $searchResults["aggregations"])){
            foreach ($searchResults["aggregations"]["brandId"]["buckets"] as $bucket) {
                array_push($aggregations["brandIds"], $bucket["key"]);
            }
        }

        return array(
            'manufacturerProducts' => $searchResults['manufacturerProducts'],
            'totalResultCount' => $searchResults['totalCount'],
            'totalPage'        => (int) ceil($searchResults['totalCount']/$perPage),
            'aggregations'     => $aggregations
        );
    }

    public function getAggregations()
    {
        $manufacturerProducts = $this->searchWithElastic(
            null, 
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            array(
                ManufacturerProduct::STATUS_ACTIVE, 
                ManufacturerProduct::STATUS_INACTIVE
            ),
            true
        );

        return $manufacturerProducts;
    }
}
