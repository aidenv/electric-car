<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Finder;

use Elastica\Query;
use Pagerfanta\Pagerfanta;
use Yilinker\Bundle\CoreBundle\Services\Search\Paginator\HybridPaginatorAdapter;
use FOS\ElasticaBundle\Paginator\FantaPaginatorAdapter;
use FOS\ElasticaBundle\Finder\TransformedFinder;

class StoreTransformedFinder extends TransformedFinder
{
     /**
      * Gets a paginator wrapping the result of a search
      *
      * @param string $query
      * @param array $options
      * @return Pagerfanta
      */
     public function findPaginatedHybrid($query, $options = array())
     {
         $queryObject = Query::create($query);
         $paginatorAdapter = $this->createHybridPaginatorAdapter($queryObject, $options);
 
         return new Pagerfanta(new FantaPaginatorAdapter($paginatorAdapter));
     }

    public function createHybridPaginatorAdapter($query)
    {
        $query = Query::create($query);
        
        return new HybridPaginatorAdapter($this->searchable, $query, $this->transformer);
    }

}
