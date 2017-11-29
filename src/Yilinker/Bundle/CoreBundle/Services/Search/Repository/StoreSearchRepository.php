<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Repository;

use Yilinker\Bundle\CoreBundle\Model\StoreSearch;
use FOS\ElasticaBundle\Repository;
use Elastica\Query;

class StoreSearchRepository extends Repository
{
    /**
     * Search function for a store using elasticsearch
     *
     * @param Yilinker\Bundle\CoreBundle\Model\StoreSearch $storeSearch
     * @param bool $getResults
     * @return mixed
     */
    public function search(StoreSearch $storeSearch, $getResults = true)
    {
        if (strlen($storeSearch->getQueryString()) > 0) {
            $query = new \Elastica\Query\Match();
            $query->setFieldQuery('store.storeName', $storeSearch->getQueryString());
            $query->setFieldFuzziness('store.storeName', 0.7);
            $query->setFieldMinimumShouldMatch('store.storeName', '80%');
        } 
        else{
            $query = new \Elastica\Query\MatchAll();
        }

        $baseQuery = $query;
        $booleanFilter = new \Elastica\Filter\Bool();

        /**
         *  Accreditation Level Filter
         */
        if(null !== $storeSearch->getAccreditationLevel()){
            $accreditationFilter = new \Elastica\Filter\Range();
            $accreditationFilter->addField('accreditationLevel', array('gte' => $storeSearch->getAccreditationLevel() ));
            $booleanFilter->addMust($accreditationFilter);
        }
        else{
            $accreditationFilter = new \Elastica\Filter\Range();
            $accreditationFilter->addField('accreditationLevel', array('gt' => 0 ));
            $booleanFilter->addMust($accreditationFilter);
        }
        
        /**
         * Apply product count filter
         */
        if(null !== $storeSearch->getHasProduct()){
            $condition = $storeSearch->getHasProduct() ? "gt" : "eq";
            $productCountFilter = new \Elastica\Filter\Range();
            $productCountFilter->addField('productCount', array($condition => 0 ));
            $booleanFilter->addMust($productCountFilter);
        }

        $filtered = new \Elastica\Query\Filtered($baseQuery, $booleanFilter);
        $query = \Elastica\Query::create($filtered);

//        $x = ($query->getQuery());
//        dump($x);
//        dump(json_encode($x));
//        exit;

        /**
         * Set sort order and direction
         */
        $query->setSort(array(
            $storeSearch->getSortField() => array(
                'order' => $storeSearch->getSortDirection()
            )
        ));

        if($getResults === false){
            $query->setSize(0);
            $paginatorAdapter = $this->createPaginatorAdapter($query);

            return array(
                'products' => array(),
                'totalProductCount' => 0,
                'aggregations' => $paginatorAdapter->getAggregations(),
            );
        }
        else{            
            $paginatedProducts = $this->finder->findPaginatedHybrid($query);
            $paginatedProducts->setMaxPerPage($storeSearch->getPerPage());
            $paginatedProducts->setCurrentPage($storeSearch->getPage());
            $hybridResults = $paginatedProducts->getCurrentPageResults();
            
            $stores = array();
            foreach($hybridResults as $hybridResult){
                $transformedResult = $hybridResult->getTransformed();
                $rawResult = $hybridResult->getResult();
                $store = $transformedResult;
                $store->setSpecialtyCategory($rawResult->getHit()['_source']['specialtyCategory']);
                $stores[] = $store;
            }

            return array(
                'stores' => $stores,
                'totalProductCount' => $paginatedProducts->getNbResults(),
                'aggregations' => $paginatedProducts->getAdapter()->getAggregations(),
            );
        }
    }

}


