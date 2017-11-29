<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Services\Search\ProductSearchService;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;

class ProductController extends Controller
{
    const SEARCH_RESULTS_PER_PAGE = 30;

    public function detailsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $storeSlug = $request->get('storeSlug');
        $productSlug = $request->get('slug');
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');

        $product = $productRepository->qb()
            ->getProductFilterByStatus(array(Product::ACTIVE))
            ->getBySlug($productSlug)
            ->getByAffiliateSlug($storeSlug)
            ->getSingleResult()
        ;

        if ($product && $map = $product->getManufacturerProductMap()) {
            $inhouseProduct = $map->getManufacturerProduct()->getProduct();
            $user = $product->getUser();
            $routeName = 'product_details';
            $params = array('slug' => $inhouseProduct->getSlug());

            if ($inhouseProduct->isSelectedBy($user)) {
                $routeName = 'store_product_details';
                $params['storeSlug'] = $user->getStore()->getStoreSlug();
            }

            return $this->redirectToRoute($routeName, $params, 301);
        }

        if($product === null || $product->getUser()->getIsActive() === false){
            throw $this->createNotFoundException('Product not found');
        }

        $store = null;
        if ($storeSlug) {
            $tbStore = $em->getRepository('YilinkerCoreBundle:Store');
            $store = $tbStore->getActiveStore($storeSlug);
            if (!$store) {
                throw $this->createNotFoundException('Store not found');
            }
        }

        $elasticManager = $this->get('fos_elastica.manager');
        $tbeProduct = $elasticManager->getRepository('YilinkerCoreBundle:Product');
        $elasticProduct = $tbeProduct->findByID($product->getProductId());

        $ip = $this->container->get("request")->getClientIp();
        $productVisits = $product->getProductVisitTodayByIp($ip);
        if($productVisits->isEmpty()){
            $productVisitService = $this->get('yilinker_core.service.product.product_visit');
            $productVisitService->addProductVisit($product, $ip);
        }

        return $this->render('YilinkerFrontendBundle:Product:product.html.twig', compact('product', 'elasticProduct', 'store'));
    }

    /**
     * Render the product seller section (Use ESI for this)
     *
     * @param int $productId
     * @return Response
     */
    public function renderProductSellerAction($productId, $storeId = null)
    {
        $em = $this->getDoctrine()->getManager();
        $user = null;

        if ($storeId && $store = $em->getRepository('YilinkerCoreBundle:Store')->find($storeId)) {
            $user = $store->getUser();
        }
        elseif ($product = $em->getRepository('YilinkerCoreBundle:Product')->findProductByIdCached($productId)) {
            $user = $product->getUser();
        }

        if (!$user) {
            return new Response;
        }

        $productCategoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $specialty = $productCategoryRepository->getUserSpecialty($user);

        $response = $this->render('YilinkerFrontendBundle:Product:product_seller.html.twig', array(
            'user'             => $user,
            'specialty'        => $specialty,
            'merchantHostName' => $this->getParameter('merchant_hostname')
        ));
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    public function changeQuantityAction(Request $request)
    {
        $productUnitId = $request->request->get("unitId", 0);
        $quantity = $request->request->get("quantity", 0);

        $productUnit = $this->getDoctrine()
                            ->getRepository('YilinkerCoreBundle:ProductUnit')
                            ->find($productUnitId);

        if($productUnit){
            $productService = $this->get('yilinker_front_end.service.product.product');
            $productService->setProductUnitDiscount($productUnit, $quantity);

            $price = $productUnit->getAppliedDiscountPrice();
            if(is_null($price)){
                $price = $productUnit->getDiscountedPrice();
            }

            return new JsonResponse(array(
                "discountedPrice" => number_format($price, 2)
            ), 200);
        }

        return new JsonResponse(array(
            "discountedPrice" => "0.00"
        ), 400);

    }

    public function relatedAction(Request $request)
    {
        $title = 'PRODUCTS RELATED TO THIS ITEM';

        $productId = $request->get('productId');
        $productRepo = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
        $productCollection = $productRepo->getRelated($productId);

        $pagesService = $this->get('yilinker_core.service.pages.pages');

        $products = $pagesService->constructProducts($productCollection);
        if (!$products) {
            return new Response;
        }

        $data = compact('title', 'products');

        return $this->render('YilinkerFrontendBundle:Product:product_recommendations.html.twig', $data);
    }

    public function sellerRelatedAction(Request $request)
    {
        $title = 'OTHER PRODUCTS SOLD BY THIS SELLER';
        $productId = $request->get('productId');
        $key = 'related-to-'.$productId;

        $products = $this->getCacheValue($key, true);
        if(!$products){
            $productRepo = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
            $productCollection = $productRepo->getSellerRelated($productId);
            $pagesService = $this->get('yilinker_core.service.pages.pages');
            $products = $pagesService->constructProducts($productCollection);
            $this->setCacheValue($key, $products);
        }

        if (!$products) {
            return new Response;
        }
        $data = compact('title', 'products');

        $response = $this->render('YilinkerFrontendBundle:Product:product_recommendations.html.twig', $data);
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    public function mayLikeAction(Request $request)
    {
        $title = 'ITEMS YOU MAY LIKE';

        $limit = $request->get('limit', 5);
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "web");
        $xmlParserService = $this->get('yilinker_core.service.pages.xml_parser');
        $productUnitIds = $xmlParserService->getNodeValues($homeXml, 'itemsYouMayLike/productUnitId');

        $productUnitRepository = $this->getDoctrine()->getRepository('YilinkerCoreBundle:ProductUnit');
        $productUnits = $productUnitRepository->loadProductUnitsIn($productUnitIds, $limit);

        $pagesService = $this->get('yilinker_core.service.pages.pages');

        $products = $pagesService->constructProductUnits($productUnits);
        if (!$products) {
            return new Response;
        }
        $data = compact('title', 'products');

        return $this->render('YilinkerFrontendBundle:Product:product_recommendations.html.twig', $data);
    }

    public function boughtWithAction(Request $request)
    {
        $title = 'ITEMS BOUGHT WITH THIS ITEM';

        $productId = $request->get('productId');
        $limit = $request->get('limit', 5);
        $productRepo = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
        $productCollection = $productRepo->getBoughtWith($productId, $limit);

        $pagesService = $this->get('yilinker_core.service.pages.pages');

        $products = $pagesService->constructProducts($productCollection);
        if (!$products) {
            return new Response;
        }

        $data = compact('title', 'products');

        return $this->render('YilinkerFrontendBundle:Product:product_recommendations.html.twig', $data);
    }

    /**
     * Render Search Product Results
     *
     * @param Symfony\Component\HttpFoundation\Request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function productSearchAction(Request $request)
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
        $subcategories = $subcategories ? explode(',', $subcategories) : null;
        $brands = $request->get('brands', null);
        $brands = $brands ? explode(',', $brands) : null;

        if($queryString === NULL || trim($queryString) === ""){
            return $this->redirectToRoute('all_categories');
        }

        try{
            $productSearchResult = $this->get('yilinker_core.service.search.product')
                                 ->searchProductsWithElastic(
                                     $queryString, $priceFrom, $priceTo,
                                     $categoryId, $sellerId, $brands,
                                     $subcategories, $sortType, $sortDirection,
                                     array(), $page, self::SEARCH_RESULTS_PER_PAGE,
                                     true, true
                                 );
        }
        catch(OutOfRangeCurrentPageException $e){
            throw $this->createNotFoundException('Page does not exist');
        }

        $productAggregations = $this->get('yilinker_core.service.search.product')
                                    ->searchProductsWithElastic(
                                        $queryString,
                                        null, null, null, null,
                                        null, null, null, null,
                                        array(), null, null,
                                        false, false
                                    );

        $storeSearchResult = $this->get('yilinker_core.service.search.store')
                                  ->searchStoreWithElastic(
                                      $queryString, null,
                                      null, null, null, null, true
                                  );

        $parameters = $request->query->all();
        if(isset($parameters['page'])){
            unset($parameters['page']);
        }

        return $this->render('YilinkerFrontendBundle:Search:search_page_by_product.html.twig', array(
            'subcategoriesEnabled' => true,
            'products' => $productSearchResult['products'],
            'aggregations' => $productAggregations['aggregations'],
            'totalProductResultCount' => $productSearchResult['totalResultCount'],
            'totalStoreResultCount' => $storeSearchResult['totalResultCount'],
            'totalPages' => ceil($productSearchResult['totalResultCount']/self::SEARCH_RESULTS_PER_PAGE),
            'page' => $page,
            'query' => $queryString,
            'parameters' => $parameters
        ));
    }

    /**
     * Render product card
     *
     * @param integer $productId
     * @return Response
     */
    public function renderProductCardAction($productId, $discountedPrice, $quantity, $storeSlug = null)
    {
        $productRepo = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
        $product = $productRepo->findProductByIdCached($productId);

        $response =  $this->render('YilinkerFrontendBundle:Product:product_card.html.twig', array('product' => $product, 'storeSlug' => $storeSlug));
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    public function renderFeaturedCategoryProductAction($productId, $discountedPrice, $quantity)
    {
        $productRepo = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
        $product = $productRepo->findProductByIdCached($productId);

        $productService = $this->get("yilinker_core.service.product.product");
        $product = $productService->getProductDetail($product);

        $response =  $this->render('YilinkerFrontendBundle:Product:v2/category_product_details.html.twig', array('product' => $product));
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    public function renderFeaturedProductAction($productId, $discountedPrice, $quantity)
    {
        $productRepo = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
        $product = $productRepo->findProductByIdCached($productId);

        $productService = $this->get("yilinker_core.service.product.product");
        $product = $productService->getProductDetail($product);

        $response =  $this->render('YilinkerFrontendBundle:Product:v2/product_details.html.twig', array('product' => $product));
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

    /**
     * Render custom product list
     *
     * @param string $list
     * @return Response
     */
    public function renderCustomProductListAction($list)
    {
        $request = $this->getRequest();
        $resourceGetter = $this->container->get('yilinker_core.service.xml_resource_service');
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $xmlParser = $this->container->get('yilinker_core.service.pages.xml_parser');
        $xmlObject = $resourceGetter->fetchXML("products", "v2", "web");
        $productList = $xmlParser->getNodeWithId($xmlObject, "list", $list);
        $page = (int) $request->get('page', 1);

        if($productList === false){
            throw $this->createNotFoundException('Product list not found');
        }

        try{
            $productList = json_decode(json_encode($productList), 1);
            $categoryIds = isset($productList['categoryId']) ? $productList['categoryId'] : null;
            
            if ($categoryIds) {

                $rs = $this->searchResult(array(
                    'page' => $page,
                    'productList' => null,
                    'categoryIds' => $categoryIds,
                ));

                $productSearchResult = $rs['productSearchResult'];
                $productAggregations = $rs['productAggregations'];

            } else {

                $rs = $this->searchResult(array(
                    'page' => $page,
                    'productList' => $productList['productId'],
                ));

                $productSearchResult = $rs['productSearchResult'];
                $productAggregations = $rs['productAggregations'];
            }


            $innerBanner = isset($productList['banner']) ? $pagesService->getProductDetailInnerBanner($productList['banner']) : null;
        }
        catch(OutOfRangeCurrentPageException $e){
            throw $this->createNotFoundException('Page does not exist');
        }


        return $this->render('YilinkerFrontendBundle:Search:search_page_by_product.html.twig', array(
            'products'                => $productSearchResult['products'],
            'aggregations'            => $productAggregations['aggregations'],
            'totalProductResultCount' => $productSearchResult['totalResultCount'],
            'totalStoreResultCount'   => 0,
            'totalPages'              => ceil($productSearchResult['totalResultCount']/self::SEARCH_RESULTS_PER_PAGE),
            'page'                    => $page,
            'query'                   => "Custom Product List",
            'banner'                  => $innerBanner,
            'parameters'              => array(
                'list' => $list,
            ),
        ));
    }


    private function searchResult($params=array())
    {
        extract($params);

        $categoryIds = !isset($categoryIds) ? null : $categoryIds;

        $productSearchResult = $this->get('yilinker_core.service.search.product')
                                    ->searchProductsWithElastic(
                                        null,null,null,$categoryIds,null,null,null,
                                        null,null,array(),$page,self::SEARCH_RESULTS_PER_PAGE,
                                        true,true,array(),null,null,null,null,
                                        null,null,$productList
                                    );

        $productAggregations = $this->get('yilinker_core.service.search.product')
                            ->searchProductsWithElastic(
                                null,null,null,$categoryIds,null,null,null,
                                null,null,array(),null,null,
                                false,false,array(),null,null,null,null,
                                null,null,$productList
                            );

        return compact('productSearchResult','productAggregations');
    }

}
