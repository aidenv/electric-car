<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Repository;

use Yilinker\Bundle\CoreBundle\Model\ManufacturerProductUnit;
use Yilinker\Bundle\CoreBundle\Model\ManufacturerProductUnitSearch;
use FOS\ElasticaBundle\Repository;
use Elastica\Query;

class ManufacturerProductUnitSearchRepository extends Repository
{
    /**
     * Search function for a ManufacturerProductUnit using elasticsearch
     *
     * @param Yilinker\Bundle\CoreBundle\Model\ManufacturerProductUnitSearch $manufacturerProductUnit
     * @param bool $getResults
     * @return mixed
     */
    public function search(ManufacturerProductUnitSearch $manufacturerProductUnitSearch, $getResults = true)
    {
        $query = new \Elastica\Query\MatchAll();

        $baseQuery = $query;
        $booleanFilter = new \Elastica\Filter\Bool();
        
        /**
         *  Date created
         */
        if(null !== $manufacturerProductUnitSearch->getBeginDate()){
            $fromFilter = new \Elastica\Filter\Range();
            $fromFilter->addField('dateLastModified', array('gte' => $manufacturerProductUnitSearch->getBeginDate() ));
            $booleanFilter->addMust($fromFilter);
        }
        if(null !== $manufacturerProductUnitSearch->getEndDate()){
            $toFilter = new \Elastica\Filter\Range();
            $toFilter->addField('dateLastModified', array('lte' => $manufacturerProductUnitSearch->getEndDate() ));
            $booleanFilter->addMust($toFilter);
        }
                
        $filtered = new \Elastica\Query\Filtered($baseQuery, $booleanFilter);
        $query = \Elastica\Query::create($filtered);
        
        /**
         * Set sort order and direction
         */
        $query->setSort(array(
            $manufacturerProductUnitSearch->getSortField() => array(
                'order' => $manufacturerProductUnitSearch->getSortDirection()
            )
        ));
        
        if($getResults === false){
            $query->setSize(0);
            $paginatorAdapter = $this->createPaginatorAdapter($query);

            return array(
                'manufacturerProductUnits' => array(),
                'totalCount'               => 0,
                'aggregations'             => $paginatorAdapter->getAggregations(),
            );
        }
        else{            
            $paginatedProducts = $this->finder->findPaginated($query);
            $paginatedProducts->setMaxPerPage($manufacturerProductUnitSearch->getPerPage());
            $paginatedProducts->setCurrentPage($manufacturerProductUnitSearch->getPage());

            return array(
                'manufacturerProductUnits' => $paginatedProducts->getCurrentPageResults(),
                'totalCount'               => $paginatedProducts->getNbResults(),
                'aggregations'             => $paginatedProducts->getAdapter()->getAggregations(),
            );
        }
    }

}

