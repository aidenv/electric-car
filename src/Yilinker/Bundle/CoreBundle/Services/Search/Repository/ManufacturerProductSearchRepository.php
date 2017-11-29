<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Repository;

use Yilinker\Bundle\CoreBundle\Model\ManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Model\ManufacturerProductSearch;
use FOS\ElasticaBundle\Repository;
use Elastica\Query;

class ManufacturerProductSearchRepository extends Repository
{
    public function search(ManufacturerProductSearch $manufacturerProductSearch, $getResults = true)
    {
        if (strlen($manufacturerProductSearch->getQueryString()) > 0) {
            $query = new \Elastica\Query\Match();
            $query->setFieldQuery('manufacturerProduct.name', $manufacturerProductSearch->getQueryString());
            $query->setFieldFuzziness('manufacturerProduct.name', 0.7);
            $query->setFieldMinimumShouldMatch('manufacturerProduct.name', '80%');
        } 
        else{
            $query = new \Elastica\Query\MatchAll();
        }

        $baseQuery = $query;
        $booleanFilter = new \Elastica\Filter\Bool();

        if(count($manufacturerProductSearch->getStatuses()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('status', $manufacturerProductSearch->getStatuses())
            );
        }

        if(count($manufacturerProductSearch->getBrandIds()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('brandId', $manufacturerProductSearch->getBrandIds() )
            );
        }

        if(count($manufacturerProductSearch->getCategoryIds()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('categoryId', $manufacturerProductSearch->getCategoryIds() )
            );
        }

        if (count($manufacturerProductSearch->getManufacturerIds()) > 0) {
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('manufacturerId', $manufacturerProductSearch->getManufacturerIds() )
            );
        }

        if(null !== $manufacturerProductSearch->getPriceFrom()){
            $fromFilter = new \Elastica\Filter\NumericRange();
            $fromFilter->addField('price', array('from' => $manufacturerProductSearch->getPriceFrom() ));
            $booleanFilter->addMust($fromFilter);
        }
        if(null !== $manufacturerProductSearch->getPriceTo()){
            $toFilter = new \Elastica\Filter\NumericRange();
            $toFilter->addField('price', array('to' => $manufacturerProductSearch->getPriceTo() ));
            $booleanFilter->addMust($toFilter);
        }
        
        $filtered = new \Elastica\Query\Filtered($baseQuery, $booleanFilter);
        $query = \Elastica\Query::create($filtered);

        $query->setSort(array(
            $manufacturerProductSearch->getSortField() => array(
                'order' => $manufacturerProductSearch->getSortDirection()
            )
        ));

        $brandAggregation = new \Elastica\Aggregation\Terms('brandId');
        $brandAggregation->setField('brandId');

        $categoryAggregation = new \Elastica\Aggregation\Terms('categoryId');
        $categoryAggregation->setField('categoryId');

        $query->addAggregation($brandAggregation);
        $query->addAggregation($categoryAggregation);
        
        if($getResults === false){
            $query->setSize(0);
            $paginatorAdapter = $this->createPaginatorAdapter($query);

            return array(
                'manufacturerProducts' => array(),
                'totalCount'               => 0,
                'aggregations'             => $paginatorAdapter->getAggregations(),
            );
        }
        else{            
            $paginatedProducts = $this->finder->findPaginated($query);
            $paginatedProducts->setMaxPerPage($manufacturerProductSearch->getPerPage());
            $paginatedProducts->setCurrentPage($manufacturerProductSearch->getPage());

            return array(
                'manufacturerProducts'     => $paginatedProducts->getCurrentPageResults(),
                'totalCount'               => $paginatedProducts->getNbResults(),
                'aggregations'             => $paginatedProducts->getAdapter()->getAggregations(),
            );
        }
    }

}

