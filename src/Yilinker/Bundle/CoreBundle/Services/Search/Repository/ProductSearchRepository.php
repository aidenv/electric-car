<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search\Repository;

use Yilinker\Bundle\CoreBundle\Model\ProductSearch;
use FOS\ElasticaBundle\Repository;
use Yilinker\Bundle\CoreBundle\Entity\Product;

class ProductSearchRepository extends Repository
{
    /**
     * Search function to demonstrate the elastic search functionality
     *
     * @param Yilinker\Bundle\CoreBundle\Model\ProductSearch $productSearch
     * @param bool $getResults
     * @return mixed
     */
    public function search(ProductSearch $productSearch, $getResults = true)
    {
       $fieldPrefix = $productSearch->getFieldPrefix();

        $booleanFilter = new \Elastica\Filter\Bool();

        if (strlen($productSearch->getQueryString()) > 0) {
            $nameField = 'product.'.$fieldPrefix.'name';
            
            $query = new \Elastica\Query\MultiMatch();
            $query->setQuery($productSearch->getQueryString());
            $query->setFields(array('flattenedCategory.categoryName', $nameField));
            $query->setType('most_fields');
            $query->setFuzziness(0.7);
            $query->setMinimumShouldMatch('80%');

        } 
        else{
            $query = new \Elastica\Query\MatchAll();
        }

        $baseQuery = $query;


        $statuses = $productSearch->getStatuses();
        if(count($statuses) > 0){
            // if (count($statuses) == 1 && $statuses[0] == Product::ACTIVE) {
            //     $booleanFilter->addMust(
            //         new \Elastica\Filter\Terms('status', $statuses)
            //     );
            // }
            // else {
                $booleanFilter->addMust(
                    new \Elastica\Filter\Terms($fieldPrefix.'status', $statuses)
                );
            // }
        }

        /**
         * Is affiliate filter
         */                 
        if(null !== $productSearch->getIsInhouseProduct()){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('isInhouseProduct', array($productSearch->getIsInhouseProduct()))
            );
        }

        /**
         * Store isActive filer         
         */
        if(null !== $productSearch->getIsActiveStore()){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('sellerIsActive', array($productSearch->getIsActiveStore()))
            );
        }
        
        /**
         *  Date created
         */
        if(null !== $productSearch->getBeginDate()){
            $fromFilter = new \Elastica\Filter\Range();
            $fromFilter->addField('dateCreated', array('gte' => $productSearch->getBeginDate() ));
            $booleanFilter->addMust($fromFilter);
        }
        if(null !== $productSearch->getEndDate()){
            $toFilter = new \Elastica\Filter\Range();
            $toFilter->addField('dateCreated', array('lte' => $productSearch->getEndDate() ));
            $booleanFilter->addMust($toFilter);
        }

        if($productSearch->getIsPromoProduct()){
            $dateNow = new \DateTime();
            $promoDateStartFilter = new \Elastica\Filter\Range();
            $promoDateEndFilter = new \Elastica\Filter\Range();

            $dateStartTerm = array('lte'=> $dateNow->format(\DateTime::ISO8601));
            $dateEndTerm = array('gte' => $dateNow->format(\DateTime::ISO8601));

            $promoDateStartFilter->addField('promoDateStart', $dateStartTerm);
            $promoDateEndFilter->addField('promoDateEnd', $dateEndTerm);
            
            $booleanFilter->addMust($promoDateStartFilter);
            $booleanFilter->addMust($promoDateEndFilter);
            $booleanFilter->addMust(new \Elastica\Filter\Terms('promoIsEnabled', array(true)));
        }
        
        if(!is_null($productSearch->getSlug())){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('slug', $productSearch->getSlug() )
            );
        }

        /**
         *  Price filters
         */
        if(null !== $productSearch->getPriceFrom()){
            $fromFilter = new \Elastica\Filter\NumericRange();
            $fromFilter->addField($fieldPrefix.'defaultPrice', array('from' => $productSearch->getPriceFrom() ));
            $booleanFilter->addMust($fromFilter);
        }
        if(null !== $productSearch->getPriceTo()){
            $toFilter = new \Elastica\Filter\NumericRange();
            $toFilter->addField($fieldPrefix.'defaultPrice', array('to' => $productSearch->getPriceTo() ));
            $booleanFilter->addMust($toFilter);
        }

        $sellerIds = $productSearch->getSellerIds();
        $sellerIds = $sellerIds ? $sellerIds: array();

        if (
            // !in_array(203953, $sellerIds) &&
            !in_array(203419, $sellerIds) && 
            isset($GLOBALS['application_env']) && 
            $GLOBALS['application_env'] == 'frontend'
        ) {
            $booleanFilter->addMustNot(
                new \Elastica\Filter\Terms('sellerId', array(203419))
            );
        }
        
        if (count($sellerIds) > 0) {
            if ($productSearch->getIsInhouseProduct()) {
                $inhouseQuery = new \Elastica\Query\BoolQuery();
                $inhouseQuery->addMust(
                    new \Elastica\Query\Terms('flattenedAffiliate.sellerId', $sellerIds)
                );
                if ($statuses) {
                    $inhouseQuery->addMust(
                        new \Elastica\Query\Terms('status', $statuses)
                    );
                }

                $booleanFilter->addMust(
                    new \Elastica\Filter\HasChild($inhouseQuery, 'inhouse_product_user')
                );
            }
            else {
                $booleanFilter->addMust(new \Elastica\Filter\Terms('sellerId', $sellerIds));
            }
        }

        /**
         * don't show products from previous implementation of affiliate products
         */
        $booleanFilter->addMust(new \Elastica\Filter\Terms('isManufacturerProduct', array(false)));

        /**
         * Apply category filter
         */
        if(count($productSearch->getCategoryIds()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('categoryId', $productSearch->getCategoryIds() )
            );
        }

        /**
         * Apply category filter
         */
        if(count($productSearch->getCustomCategoryIds()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('customCategoryId', $productSearch->getCustomCategoryIds())
            );
        }
        
        /**
         * Apply brand filter
         */
        if(count($productSearch->getBrands()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('brandName', $productSearch->getBrands() )
            );
        }

        /**
         * Apply subcategoryId filter
         */ 
        if(count($productSearch->getSubcategoryIds()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('categoryId', $productSearch->getSubcategoryIds())
            );
        }

        $attributeValues = $productSearch->getAttributeValues();
        $attributeArray = array();

        foreach ($attributeValues as $attribute) {
            $attributeData = explode('|', $attribute);

            if (isset($attributeArray[$attributeData[0]])) {
                $attributeArray[$attributeData[0]][] = $attribute;
            }
            else {
                $attributeArray[$attributeData[0]] = array($attribute);
            }
        }

        foreach ($attributeArray as $data) {
            $attributesBooleanFilter = new \Elastica\Filter\Bool();
            foreach ($data as $attrValue) {
                $attributesBooleanFilter->addShould(
                    new \Elastica\Filter\Term(array(
                        'attributeValues' => $attrValue
                    ))
                );
            }

            $booleanFilter->addMust($attributesBooleanFilter);
        }

        /**
         * Apply country filter
         */
        $countryCodes = $productSearch->getCountryCodes();
        if(count($countryCodes) > 0){
            $countryBooleanFilter = new \Elastica\Filter\Bool();
            foreach($countryCodes as $code){
                $countryBooleanFilter->addShould(array(
                    new \Elastica\Filter\Term(array(
                        'countries' => $code
                    ))
                ));
            }
            $booleanFilter->addMust($countryBooleanFilter);
        }

        // filter by product warehouse
        $this->filterByWarehouse($booleanFilter, $productSearch);

        /**
         * Apply exact product id filter
         */
        if(count($productSearch->getProductIds()) > 0){
            $booleanFilter->addMust(
                new \Elastica\Filter\Terms('productId', $productSearch->getProductIds())
            );
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
            $productSearch->getSortField() => array(
                'order' => $productSearch->getSortDirection()
            )
        ));

        $maxPriceAggregation = new \Elastica\Aggregation\Max('max');
        $maxPriceAggregation->setField($fieldPrefix.'defaultPrice');

        $minPriceAggregation = new \Elastica\Aggregation\Min('min');
        $minPriceAggregation->setField($fieldPrefix.'defaultPrice');

        $brandAggregation = new \Elastica\Aggregation\Terms('brandName');
        $brandAggregation->setField('brandName');

        $brandIdAggregation = new \Elastica\Aggregation\Terms('brandId');
        $brandIdAggregation->setField('brandId');

        $categoryAggregation = new \Elastica\Aggregation\Terms('category');
        $categoryAggregation->setField('categoryKeyword');

        $customCategoryAggregation = new \Elastica\Aggregation\Terms('customCategories');
        $customCategoryAggregation->setField('customCategories');

        $attributeValuesAggregation = new \Elastica\Aggregation\Terms('attributeValues');
        $attributeValuesAggregation->setField('attributeValues');
        $attributeValuesAggregation->setSize(0);

        $query->addAggregation($minPriceAggregation);
        $query->addAggregation($maxPriceAggregation);
        $query->addAggregation($brandAggregation);
        $query->addAggregation($brandIdAggregation);
        $query->addAggregation($categoryAggregation);
        $query->addAggregation($customCategoryAggregation);
        $query->addAggregation($attributeValuesAggregation);
        
        // dump(json_encode($query->getQuery())); exit;
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
            /**
             * Use the following code to get the JSON request string: 
             * echo json_encode(array('query' => $query->getParams()));
             */
            $paginatedProducts = $this->finder->findPaginated($query);
            $paginatedProducts->setMaxPerPage($productSearch->getPerPage());
            $paginatedProducts->setCurrentPage($productSearch->getPage());

            return array(
                'products' => $paginatedProducts->getCurrentPageResults(),
                'totalProductCount' => $paginatedProducts->getNbResults(),
                'aggregations' => $paginatedProducts->getAdapter()->getAggregations(),
            );
        }
    }

    /**
     * Apply product warehouse filter
     * @param  \Elastica\Filter\Bool
     * @param  ProductSearch
     */
    private function filterByWarehouse(&$booleanFilter, $productSearch)
    {
        $warehouses = $productSearch->getWarehouses();
        $countryCodes = $productSearch->getCountryCodes();

        if (count($warehouses) > 0) {
            $warehouseBooleanFilter = new \Elastica\Filter\Bool();

            $productWarehouse = array();
            foreach ($countryCodes as $code) {
                foreach($warehouses as $warehouse){
                    $productWarehouse[] = $code.'-'.$warehouse;
                }
            }

            if (count($productWarehouse) > 0) {
                foreach($productWarehouse as $warehouse){
                    $warehouseBooleanFilter->addShould(array(
                        new \Elastica\Filter\Term(array(
                            'warehouses' => $warehouse
                        ))
                    ));
                }

                $booleanFilter->addMust($warehouseBooleanFilter);
            }
        }
    }

    public function findByID($id)
    {
        $query = new \Elastica\Query\Match();
        $query->setFieldQuery('_id', $id);
        $query = \Elastica\Query::create($query);

        $result = $this->find($query);

        return array_shift($result);
    }
}
