<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search;

use Yilinker\Bundle\CoreBundle\Model\ProductSearch;

use Yilinker\Bundle\CoreBundle\Entity\Country;

use FOS\ElasticaBundle\Repository;

use Symfony\Component\HttpFoundation\Request;

class ProductSearchService
{

    const RESULT_PER_PAGE = 30;

    const AFFILIATE_RESULT_COUNT = 10;

    /**
     * @var \Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Services\Search\Repository\ProductSearchRepository
     */
    private $elasticaProductSearchRepository;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\Entity\EntityService
     */
    private $entityService;
    
    /**
     * @var string
     */
    private $countryLocale;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\Search\TranslatableElasticSearch
     */
    private $translatableService;

    private $translatable;

    /**
     * Constructor
     *
     * @param Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager $entityManager
     * @param FOS\ElasticaBundle\Manager\RepositoryManager $elasticaRepositoryManager
     * @param Yilinker\Bundle\CoreBundle\Services\Entity\EntityService $entityService
     * @param Yilinker\Bundle\CoreBundle\Services\Search\TranslatableElasticSearch $translatableService
     */
    public function __construct($entityManager, $elasticaRepositoryManager, $entityService, $translatableService, $translatable) 
    {
        $this->entityManager = $entityManager;
        $this->elasticaProductSearchRepository = $elasticaRepositoryManager->getRepository('YilinkerCoreBundle:Product');
        $this->entityService = $entityService;
        $this->translatableService = $translatableService;
        $this->translatable = $translatable;
        $this->countryLocale = $translatable->getCountry();
    }

    /**
     * Set the countrysearch locale
     *
     * @param string $coutnryLocale
     */
    public function setCountryLocale($countryLocale)
    {
        $this->countryLocale = $countryLocale;
    }
    
    /**
     * Search Product by Criteria
     *
     * @param string $queryString
     * @param string $priceFrom
     * @param string $priceTo
     * @param int|int[] $categoryId
     * @param int $sellerId
     * @param string $sortType
     * @param string $sortDirection
     * @param mixed $filters
     * @param int $brandId
     * @param int $page
     * @param bool $hydrateAsEntity
     * @return mixed
     */
    public function searchProductsByCriteriaSql(
        $queryString = null, 
        $priceFrom = null,
        $priceTo = null,
        $categoryId = null,
        $sellerId = null,
        $sortType = null,
        $sortDirection = null,
        $filters = array(),
        $brandId = null,
        $page = 1,
        $perPage = self::RESULT_PER_PAGE,
        $hydrateAsEntity = false
    ){
        $productRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Product");
        $products = $productRepository->getList(compact(
            'queryString',
            'priceFrom',
            'priceTo',
            'categoryId',
            'sellerId',
            'sortType',
            'sortDirection',
            'filters',
            'brandId',
            'page',
            'perPage',
            'hydrateAsEntity'
        )); 
        
        return $products;
    }

    /**
     * Search Product by Criteria
     *
     * @param string $queryString
     * @param string $priceFrom
     * @param string $priceTo
     * @param int|int[] $categoryId
     * @param int|int[] $sellerId
     * @param string|string[] $brands
     * @param int|int[] $subcategoryIds
     * @param string $sortType
     * @param string $sortDirection
     * @param mixed $filters
     * @param int $page
     * @param bool $hydrateAsEntity
     * @param bool getResults
     * @param mixed $attributes
     * @param DateTime $beginDate
     * @param DateTime $endDate
     * @param int|int[] $statuses
     * @param boolean $isInhouseProduct
     * @param int|int[] $customCategoryIds
     * @param boolean $isPromoProduct
     * @param int|int[] $exactProductIds
     * @param string[] $countryCodes
     * @param boolean $isActiveSeller
     * @return mixed
     */
    public function searchProductsWithElastic(
        $queryString = null, 
        $priceFrom = null,
        $priceTo = null,
        $categoryId = null,
        $sellerId = null,
        $brands = null,
        $subcategoryIds = null,
        $sortType = null,
        $sortDirection = null,
        $filters = array(),
        $page = 1,
        $perPage = self::RESULT_PER_PAGE,
        $hydrateAsEntity = false,
        $getResults = true,
        $attributes = array(),
        $beginDate = null,
        $endDate = null,
        $statuses = null,
        $isInhouseProduct = null,
        $customCategoryIds = null,
        $isPromoProduct = null,
        $exactProductIds = null,
        $countryCodes = array(),
        $isActiveSeller = true,
        $slug = null,
        $manufacturer = null
    ){
        $categoryId = $this->withChildCategories($categoryId);
        $searchModel = new ProductSearch();
        $searchModel->setQueryString($queryString);
        $searchModel->setSellerIds($sellerId);
        $searchModel->setCategoryIds($categoryId);
        $searchModel->setPriceFrom($priceFrom);
        $searchModel->setPriceTo($priceTo);
        $searchModel->setPage($page);
        $searchModel->setBrands($brands);
        $searchModel->setSubcategoryIds($subcategoryIds);
        $searchModel->setPerPage($perPage);
        $searchModel->setSortField($sortType);
        $searchModel->setSortDirection($sortDirection);
        $searchModel->setAttributeValues($attributes);
        $searchModel->setBeginDate($beginDate);
        $searchModel->setEndDate($endDate);
        $searchModel->setStatuses($statuses);
        $searchModel->setIsInhouseProduct($isInhouseProduct);
        $searchModel->setCustomCategoryIds($customCategoryIds);
        $searchModel->setIsPromoProduct($isPromoProduct);
        $searchModel->setProductIds($exactProductIds);
        $searchModel->setIsActiveStore($isActiveSeller);
        if (!$countryCodes) {
            $countryCodes = array($this->translatable->getCountry());
        }
        $searchModel->setCountryCodes($countryCodes);

        return $this->processData($searchModel, $getResults, $hydrateAsEntity);
    }

    public function withChildCategories($categoryIds)
    {
        if($categoryIds === null){
            return null;
        }

        if(!is_array($categoryIds)){
            $categoryIds = $categoryIds ? array($categoryIds) : null;
        }

        if ($categoryIds) {
            $tbCategoryNestedSet = $this->entityManager->getRepository('YilinkerCoreBundle:CategoryNestedSet');
            $childCategoryIds = array();
            foreach ($categoryIds as $categoryId) {
                $childCategories = $tbCategoryNestedSet->getChildrenCategoryIds($categoryId);
                $childCategoryIds = array_merge($childCategoryIds, $childCategories);
            }
            $categoryIds = array_merge($categoryIds, $childCategoryIds);
            $categoryIds = array_unique($categoryIds);
        }
        
        return $categoryIds;
    }

    public function getAggregations()
    {
        $productSearch = $this->searchProductsWithElastic(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            1,
            1,
            true
        );

        return $productSearch['aggregations'];
    }

    public function processData(ProductSearch $searchModel, $getResults = true, $hydrateAsEntity = false)
    {
        /**
         * Set the field prefix based on the country
         */
        $fieldPrefix = $this->translatableService->getElasticFieldPrefix($this->countryLocale);
        $searchModel->setFieldPrefix($fieldPrefix);

        $searchResults = $this->elasticaProductSearchRepository
                              ->search($searchModel, $getResults);

        $products = $searchResults['products'];
        if (!$hydrateAsEntity) {
            $tempProducts = array();
            foreach ($products as $key => $product) {
                $tempProducts[$key] = $this->entityService->toArray($product);
            }
            $products = $tempProducts;
        }

        $perPage = !$searchModel->getPerPage() ? self::RESULT_PER_PAGE : $searchModel->getPerPage();

        return array(
            'products'         => $products,
            'totalResultCount' => $searchResults['totalProductCount'],
            'totalPage'        => (int) ceil($searchResults['totalProductCount'] / $perPage),
            'aggregations'     => $this->buildAggregation($searchResults),
        );
    }

    private $productSearchModel;

    /**
     * Process aggregates
     */
    public function buildAggregation($searchResults)
    {
        $brands = array();
        foreach($searchResults['aggregations']['brandName']['buckets'] as $brand){
            $brands[] = $brand['key'];
        }

        $brandIds = array();
        foreach($searchResults['aggregations']['brandId']['buckets'] as $brandId){
            $brandIds[] = $brandId['key'];
        }

        $categories = array();
        foreach($searchResults['aggregations']['category']['buckets'] as $categoryKeyword){;
            $category = explode('|', $categoryKeyword['key']);
            $categoryID = array_shift($category);
            if ($categoryID) {
                $categories[] = array(
                    'id' => $categoryID,
                    'name' => array_shift($category),
                );
            }
        }

        $customCategories = array();
        foreach ($searchResults['aggregations']['customCategories']['buckets'] as $category) {
            $keywords = json_decode($category['key'], true);

            foreach ($keywords as $customCategory) {
                $details = array(
                    'id'    => $customCategory["id"],
                    'name'  => $customCategory["name"]
                );

                if(!in_array($details, $customCategories)){
                    $customCategories[] = $details;
                }
            }
        }

        $attributes = array();
        foreach ($searchResults['aggregations']['attributeValues']['buckets'] as $attributeValue) {
            $attributeValue = explode('|', $attributeValue['key']);
            $attributeName = array_shift($attributeValue);
            $value = array_shift($attributeValue);
            if ($attributeName && $value) {
                if (!array_key_exists($attributeName, $attributes)) {
                    $attributes[$attributeName] = array();
                }
                $attributes[$attributeName][] = $value;
            }
        }

        $countries = $this->entityManager->getRepository('YilinkerCoreBundle:Country')
                                         ->findAll();

        $processedAgrregations = array(
            'maxPrice'          => $searchResults['aggregations']['max']['value'],
            'minPrice'          => $searchResults['aggregations']['min']['value'],
            'brands'            => $brands,
            'brandIds'          => $brandIds,
            'categories'        => $categories,
            'customCategories'  => $customCategories,
            'attributes'        => $attributes,
            'countries'         => $countries
        );

        return $processedAgrregations;
    }

    public function search()
    {
        $productSearch = $this->productSearchModel;

        if ($productSearch) {
            $data = $this->processData($this->productSearchModel, true, true);

            return $data;
        }

        return null;
    }

    public function filterOverseasProduct($currentCountryCode = '', $filterCountryCode = '')
    {
        $currentCountryCode = trim($currentCountryCode);

        $this->productSearchModel->setCountryCodes(array($currentCountryCode));

        if ($currentCountryCode) {
            if ($filterCountryCode) {
                $this->productSearchModel->addWarehouse($filterCountryCode);
            }
            else {
                $countries = $this->entityManager->getRepository('YilinkerCoreBundle:Country')
                                  ->findAll();

                foreach ($countries as $country) {
                    $countryCode = strtolower(trim($country->getCode()));
                    if ($countryCode !== strtolower($currentCountryCode)) {
                        $this->productSearchModel->addWarehouse($countryCode);
                    }
                }
            }
        }

        return $this;
    }

    public function build(Request $request)
    {
        $priceFrom = $request->get('priceFrom', null);
        $priceTo = $request->get('priceTo', null);
        $categoryId = $request->get('categoryId', null);
        $sellerId = $request->get('sellerId', null);
        $sortType = $request->get('sortBy', null);
        $sortDirection = $request->get('sortDirection', 'DESC');
        $page = (int) $request->get('page', 1);
        $queryString = $request->get('query', null);

        $subcategories = $request->get('subcategories', null);
        $subcategoryIds = $subcategories ? explode(',', $subcategories) : null;

        $brands = $request->get('brands', null);
        $brands = $brands ? explode(',', $brands) : null;

        $attributes = $request->get('attributes', array());

        $customCategoryIds = $request->get('customCategoryId', null);

        $isPromoProduct = $request->get('isPromoProduct', null);

        $perPage = $request->get('perPage', self::RESULT_PER_PAGE);

        $countryCode = array();
        if ($request->query->has('country') && $country = $request->get('country', null)) {
            $countryCode = array(strtolower($country));
        }

        $categories = $this->withChildCategories($categoryId);
        $productSearch = new ProductSearch;

        $productSearch
            ->setPriceFrom($priceFrom)
            ->setPriceTo($priceTo)
            ->setCategoryIds($categories)
            ->setSellerIds($sellerId)
            ->setSortField($sortType)
            ->setSortDirection($sortDirection)
            ->setPage($page)
            ->setQueryString($queryString)
            ->setSubcategoryIds($subcategoryIds)
            ->setBrands($brands)
            ->setAttributeValues($attributes)
            ->setCustomCategoryIds($customCategoryIds)
            ->setIsPromoProduct($isPromoProduct)
            ->setPerPage($perPage)
            ->setCountryCodes($countryCode);

        $this->productSearchModel = $productSearch;

        return $this;
    }
}
