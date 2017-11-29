<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Repository\ProductRepository;
use Yilinker\Bundle\CoreBundle\Services\Search\ProductSearchService;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController as Controller;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use DomDocument;
use StdClass;

class ProductApiController extends Controller
{
    const IS_PROMO_PRODUCTS = "PROMOPRODUCTS";

     /**
     * Get Product Details
     *
     * @param Request|Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="Product Id"},
     *     }
     * )
     */
    public function detailAction(Request $request)
    {
        $productId = $request->get("productId", 0);

        $tbProduct = $this->getRepository('YilinkerCoreBundle:Product');
        $product = $tbProduct->getOnebyIdOrSlug($productId, Product::ACTIVE)->getOneOrNullResult();

        $product = $product ? $product->getDetails(false, true) : array();

        if(!empty($product["productUnits"])){
            $product = $this->preProcessProductDetails($product);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "data" => $product,
                "message" => "Product details."
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "data" => new StdClass,
                "message" => "Product doest not exists."
            ), 400);
        }
    }

    public function getPromoProductsAction(Request $request)
    {
        $page = $request->query->get("page", 1);
        $limit = $request->query->get("limit", 10);

        $productService = $this->get("yilinker_front_end.service.product.product");
        $productCollection = $productService->getPromoProducts($limit, $page);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Promo products list",
            "data" => array(
                "totalResultCount" => $productCollection["totalResults"],
                "products" => $productCollection["products"]
            )
        ));
    }

    /**
     * Get Product List
     *
     * @param Request|Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     parameters={
     *         {"name"="query", "dataType"="string", "required"=false, "description"="query String"},
     *         {"name"="priceFrom", "dataType"="string", "required"=false},
     *         {"name"="categoryIds", "dataType"="string", "required"=false},
     *         {"name"="categoryId", "dataType"="string", "required"=false},
     *         {"name"="sellerIds", "dataType"="string", "required"=false},
     *         {"name"="brandIds", "dataType"="string", "required"=false},
     *         {"name"="subcategoryIds", "dataType"="string", "required"=false},
     *         {"name"="sortType", "dataType"="string", "required"=false},
     *         {"name"="sortDirection", "dataType"="string", "required"=false},
     *         {"name"="filters", "dataType"="string", "required"=false},
     *         {"name"="page", "dataType"="string", "required"=false},
     *         {"name"="perPage", "dataType"="string", "required"=false},
     *         {"name"="attributes", "dataType"="string", "required"=false},
     *         {"name"="isAffiliate", "dataType"="string", "required"=false},
     *         {"name"="customCategoryId", "dataType"="string", "required"=false},
     *         {"name"="isPromoProduct", "dataType"="string", "required"=false},
     *     }
     * )
     */
    public function listAction(Request $request)
    {
        $queryString = $request->get('query', null);
        $priceFrom = $request->get('priceFrom', -PHP_INT_MAX);
        $priceTo = $request->get('priceTo', PHP_INT_MAX);
        $categoryIds = $request->get('categoryIds', null);
        $categoryIds = $request->get('categoryId', $categoryIds);
        $sellerIds = $request->get('sellerIds', null);
        $brandIds = $request->get('brandIds', null);
        $subcategoryIds = $request->get('subcategoryIds', null);
        $sortType = $request->get('sortType', null);
        $sortDirection = $request->get('sortDirection', 'DESC');
        $filters = $request->get('filters', array());
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', null);
        $attributes = $request->get('attributes', null);
        $isAffliate = $request->get('isAffiliate', null);
        $customCategoryId = $request->get('customCategoryId', null);
        $isPromoProduct = $request->get('isPromoProduct', null);

        $products = $this->get('yilinker_core.service.search.product')
                         ->searchProductsWithElastic(
                             $queryString, $priceFrom, $priceTo,
                             $categoryIds, $sellerIds, $brandIds,
                             $subcategoryIds, $sortType, $sortDirection,
                             $filters, $page, $perPage, true,
                             true, $attributes, null, null, null, 
                             $isAffliate, $customCategoryId, $isPromoProduct
                         );

        $assetHelper = $this->get('templating.helper.assets');
        foreach ($products['products'] as &$product) {
            $image = $product->getPrimaryImages()->first();
            $imageUrl = $image ? $image->getImageLocation() : '';          
            $defaultUnit = $product->getDefaultUnit();

            $product = array(
                'id'            => $product->getProductId(),
                'productName'   => $product->getName(),
                'originalPrice' => $defaultUnit ? number_format((int)$defaultUnit->getAppliedBaseDiscountPrice(), 2, '.', ',') : '0.00',
                'newPrice'      => $defaultUnit ? number_format((int)$defaultUnit->getAppliedDiscountPrice(), 2,'.',',') : '0.00',
                'imageUrl'      => $assetHelper->getUrl($product->getPrimaryImageLocation(), 'product'),
                'imageUrlThumbnail' => $assetHelper->getUrl($product->getPrimaryImageLocationBySize('thumbnail'), 'product'),
                'discount'      => $product->getDefaultUnit() ? $product->getDefaultUnit()->getDiscount(): 0,
                'slug'          => $product->getSlug(),
                'isAffiliate'   => $product->getIsResold(),
                'isOutOfStock'  => $defaultUnit->getQuantity() == 0 ? true : false,
                'isInternationalWarehouse' => count($product->elastica['internationalWarehouses']) > 0 ? true : false,
                'hasCOD'        => $product->hasCOD()
            );
        }
        if (array_key_exists('aggregations', $products)) {
            if (array_key_exists('attributes', $products['aggregations'])) {
                $attributes = array();
                foreach ($products['aggregations']['attributes'] as $attributeName => $attributeValues) {
                    $attributes[] = array(
                        'filterName'    => $attributeName,
                        'filterItems'   => $attributeValues
                    );
                }
                $products['aggregations']['attributes'] = $attributes;
            }
        }

        $data = array(
            'isSuccessful' => $products ? true : false,
            'data'         => $products ? $products : array(),
            'message'      => $products ? '' : 'No result found.'
        );

        $response =  new JsonResponse($data);
        $response->setPublic();
        $response->setMaxAge(1800);
        $response->setSharedMaxAge(1800);

        return $response;
    }

    /**
     * Get International Product
     *
     * @param Request|Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     parameters={
     *         {"name"="country", "dataType"="string", "required"=false, "description"="country code"},
     *         {"name"="priceFrom", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="priceTo", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="categoryId", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="sellerId", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="sortBy", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="sortDirection", "dataType"="string", "required"=false, "description"="DESC"},
     *         {"name"="page", "dataType"="string", "required"=false, "description"="1"},
     *         {"name"="query", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="subcategories", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="customCategoryId", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="isPromoProduct", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="perPage", "dataType"="string", "required"=false, "description"=""},
     *     }
     * )
     */
    public function internationalProductAction(Request $request)
    {

        $productSearchService = $this->get('yilinker_core.service.search.product');
        $em = $this->getDoctrine();

        $country = $this->getCountryByApi();
        $filterCountry = strtolower($request->get('country', ''));
        $selectedCountry = $em->getRepository('YilinkerCoreBundle:Country')->findOneByCode($filterCountry);
        $query = trim($request->get('query', ''));

        $productSearchResult = $productSearchService
            ->build($request)
            ->filterOverseasProduct(strtolower($country->getCode()), $filterCountry)
            ->search();

        $assetHelper = $this->get('templating.helper.assets');
            
        foreach ($productSearchResult['products'] as &$product) {
            
            $image = $product->getPrimaryImages()->first();
            $imageUrl = $image ? $image->getImageLocation() : '';          
            $defaultUnit = $product->getDefaultUnit();

            $product = array(
                'id'            => $product->getProductId(),
                'productName'   => $product->getName(),
                'originalPrice' => $defaultUnit ? number_format((int)$defaultUnit->getAppliedBaseDiscountPrice(), 2, '.', ',') : '0.00',
                'newPrice'      => $defaultUnit ? number_format((int)$defaultUnit->getAppliedDiscountPrice(), 2,'.',',') : '0.00',
                'imageUrl'      => $assetHelper->getUrl($product->getPrimaryImageLocation(), 'product'),
                'imageUrlThumbnail' => $assetHelper->getUrl($product->getPrimaryImageLocationBySize('thumbnail'), 'product'),
                'discount'      => $product->getDefaultUnit() ? $product->getDefaultUnit()->getDiscount(): 0,
                'slug'          => $product->getSlug(),
                'isAffiliate'   => $product->getIsResold(),
                'isOutOfStock'  => $defaultUnit->getQuantity() == 0 ? true : false,
                'isInternationalWarehouse' => count($product->elastica['internationalWarehouses']) > 0 ? true : false,
                'hasCOD'        => $product->hasCOD()
            );
        }

        $products = array(
            'products' => $productSearchResult['products'],
            'excludeSeller' => true,
            'includeCountry' => true,
            'aggregations' => $productSearchResult['aggregations'],
            'totalResultCount' => $productSearchResult['totalResultCount'],
            'totalPage' => $productSearchResult['totalPage'],
            'page' => $request->get('page', 1),
            'query' => $query,
        );

         $data = array(
            'isSuccessful' => true,
            'data'         => $products,
            'message'      => count($productSearchResult['products']) > 0 ? "Available products overseas" : "No products available overseas"
        );

        return new JsonResponse($data);
    }

    private function preProcessProductDetails($product)
    {
        if (!$product) {
            return $product;
        }

        $assetHelper = $this->get('templating.helper.assets');
        $product['image'] = $assetHelper->getUrl($product['image'], 'product');

        if(array_key_exists('thumbnail', $product)){
            $product['thumbnail'] = $assetHelper->getUrl($product['thumbnail'], 'product');
        }

        if(array_key_exists('small', $product)){
            $product['small'] = $assetHelper->getUrl($product['small'], 'product');
        }

        if(array_key_exists('medium', $product)){
            $product['medium'] = $assetHelper->getUrl($product['medium'], 'product');
        }

        if(array_key_exists('large', $product)){
            $product['large'] = $assetHelper->getUrl($product['large'], 'product');
        }

        foreach ($product['images'] as $key => $image) {
            $product['images'][$key]['imageLocation'] = $assetHelper->getUrl($image['imageLocation'], 'product');

            if(array_key_exists('thumbnail', $product['images'][$key]['sizes'])){
                $product['images'][$key]['sizes']['thumbnail'] = $assetHelper->getUrl($product['images'][$key]['sizes']['thumbnail'], 'product');
            }

            if(array_key_exists('small', $product['images'][$key]['sizes'])){
                $product['images'][$key]['sizes']['small'] = $assetHelper->getUrl($product['images'][$key]['sizes']['small'], 'product');
            }

            if(array_key_exists('medium', $product['images'][$key]['sizes'])){
                $product['images'][$key]['sizes']['medium'] = $assetHelper->getUrl($product['images'][$key]['sizes']['medium'], 'product');
            }

            if(array_key_exists('large', $product['images'][$key]['sizes'])){
                $product['images'][$key]['sizes']['large'] = $assetHelper->getUrl($product['images'][$key]['sizes']['large'], 'product');
            }
        }
        
        foreach($product['productUnits'] as $key => $unit) {
            $product['productUnits'][$key]['price'] = number_format((int)$unit['price'], 2, '.', ',');
            $product['productUnits'][$key]['discountedPrice'] = number_format((int)$unit['discountedPrice'], 2, '.', ',');
            if($unit['appliedBaseDiscountPrice']){
                $product['productUnits'][$key]['appliedBaseDiscountPrice'] = number_format((int)$unit['appliedBaseDiscountPrice'], 2, '.', ',');
            }
            if($unit['appliedDiscountPrice']){
                $product['productUnits'][$key]['appliedDiscountPrice'] = number_format((int)$unit['appliedDiscountPrice'], 2, '.', ',');
            }
        }

        /**
         * Parse DOM description to add CSS to fix width
         */       
        $dom = new DomDocument;
        @$dom->loadHTML($product['fullDescription']);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image){
            $image->setAttribute('style', 'max-width: 100% !important; height: auto !important;');
        }
        $product['fullDescription'] = $dom->saveHTML();
        $product['isInternationalWarehouse'] = $this->getInternationalWarehouse($product);
        
        return $product;
    }

    private function getInternationalWarehouse($product)
    {
        $elasticManager = $this->get('fos_elastica.manager');
        $tbeProduct = $elasticManager->getRepository('YilinkerCoreBundle:Product');
        $elasticProduct = $tbeProduct->findByID($product['id']);

        return count($elasticProduct->elastica['internationalWarehouses']) > 0 ? true: false;
    }

    /**
     * Get Entity Repository
     *
     * @param string $entityName
     * @return Doctrine\ORM\EntityRepository
     */
    private function getRepository($entityName)
    {
        return $this->getDoctrine()->getRepository($entityName);
    }

}
