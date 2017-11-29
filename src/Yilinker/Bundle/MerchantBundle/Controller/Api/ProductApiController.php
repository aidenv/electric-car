<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class ProductApiController
 */
class ProductApiController extends YilinkerBaseController
{
    public function updateProductStatusAction(Request $request)
    {

        $response = array(
            "isSuccessful" => false,
            "message" => "API is unavailable",
            "data" => array()
        );
        $httpCode = 400;

        $authenticatedUser = $this->getAuthenticatedUser();
        $productIds = $request->get('productId', "[]");
        $status = (int) $request->get('status');
        $productIds = is_array($productIds) ? $productIds: json_decode($productIds, true);
        $productService = $this->get("yilinker_core.service.product.product");
        $translationService = $this->get("yilinker_core.translatable.listener");

        if($productIds === false){
            $response['message'] = "Invalid productId parameter";
        }
        else{
            $country = $translationService->getCountry();

            if ($this->getUser()->isAffiliate()) {
                $response = $productService->affilateUpdateStatus(
                    compact("productIds", "status", "authenticatedUser")
                );
            } else {
                $response = $productService->updateProductStatus($authenticatedUser, $productIds, $status, $country);
            }
            
            if($response['isSuccessful']){
                $httpCode = 200;
            }
        }

        return new JsonResponse($response, $httpCode);
    }

    /**
     * List of Products
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     parameters={
     *         {"name"="keyword", "dataType"="string", "required"=false},
     *         {"name"="page", "dataType"="string", "required"=false},
     *         {"name"="perPage", "dataType"="string", "required"=false},
     *         {"name"="status", "dataType"="string", "required"=false},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */

    public function getProductListAction(Request $request)
    {
        $response = array(
            "isSuccessful" => false,
            "message" => "API is unavailable",
            "data" => array()
        );
        $httpCode = 400;

        $authenticatedUser = $this->getAuthenticatedUser();
        $keyword = $request->get('keyword', "");
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 30);
        $status = $request->get('status', Product::ACTIVE);
        $productService = $this->get("yilinker_core.service.product.product");
        $translationService = $this->get("yilinker_core.translatable.listener");
        $country = $translationService->getCountry();
        $country = $this->get("doctrine.orm.entity_manager")
                        ->getRepository("YilinkerCoreBundle:Country")
                        ->findOneByCode($country);

        if($authenticatedUser->getStore()->isAffiliate()){
            $sellerViewableStatuses = $productService->viewableAffiliateProductStatuses();

            $this->processAffiliate(compact("keyword", "page","perPage", "status", "country"));
        }
        else{
            $sellerViewableStatuses = $productService->viewableSellerProductStatuses();
        }

        if($status !== 'all' && in_array($status, $sellerViewableStatuses) === false){
            $response['message'] = "Viewing products with status ".$status." is not allowed";
        }
        else{
            if($status === 'all'){
                $status = $sellerViewableStatuses;
            }

            $products = $productService->getProductList(
                $authenticatedUser,
                $keyword,
                $status,
                false,
                array('dateCreated' => 'DESC'),
                $page,
                $perPage,
                $country
            );

            $totalProductCount = $productService->getProductListCount(
                $authenticatedUser,
                $keyword,
                $status,
                false,
                array(),
                $country
            );

            $response['message'] = "No products found";
            $response['data']['totalPage'] = (int) ceil($totalProductCount/$perPage);
            $response['data']['totalProducts'] = (int) $totalProductCount;
            $response['data']['currentPage'] = $page;
            if(count($products) > 0){
                $response['data']['products'] = $this->preProcessProduct($products);
                $response['message'] = "Product successfully retrieved";
                $response['isSuccessful'] = true;
                $httpCode = 200;
            }
        }

        return new JsonResponse($response, $httpCode);
    }

    /**
     * Get product search suggestion
     *
     * @param  Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse;
     */
    public function getProductNameSearchSuggestionAction(Request $request)
    {
        $queryString = $request->get('queryString', '');
        $perPage = $request->get('perPage', 30);
        $page = $request->get('page', 1);
        $authenticatedUser = $this->getAuthenticatedUser();

        $products = $this->get('yilinker_core.service.search.product')
                         ->searchProductsWithElastic(
                             $queryString,
                             null, null, null,
                             $authenticatedUser->getUserId(), null, null,
                             null, null, null,
                             $page, $perPage
                         );

        $productSuggestions = array();
        foreach($products['products'] as $product){
            $productSuggestions[] = array(
                'productId' => $product['productId'],
                'name'      => $product['name'],
            );
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => count($productSuggestions) ? "Products suggestions retrieved." : "No suggestions found",
            "data" => $productSuggestions,
        ), 200);
    }

    /**
     * List of Manufacturer categories | yilinker categories
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Select Product",
     *     parameters={
     *         {"name"="access_token", "dataType"="string", "required"=false},
     *     }
     * )
     */
    public function getManufacturerCategoriesAction(Request $request)
    {
        $affiliateProductService = $this->get("yilinker_merchant.service.api.affiliate_product");

        $categories = $affiliateProductService->getFilterCategories();

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = $categories;

        return $this->jsonResponse();
    }


    /**
     * Manufacturer Product | Yiliinker Products
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Select Product",
     *     parameters={
     *         {"name"="access_token", "dataType"="string", "required"=false},
     *         {"name"="categoryIds", "dataType"="json", "required"=false},
     *         {"name"="name", "dataType"="string", "required"=false},
     *         {"name"="sortBy", "dataType"="string", "required"=false, "description"="latest|earning"},
     *         {"name"="limit", "dataType"="string", "required"=false},
     *         {"name"="page", "dataType"="string", "required"=false},
     *         {"name"="status", "dataType"="string", "required"=false, "description"="active|selected"},
     *     }
     * )
     */
    public function getAffiliateProductsAction(Request $request)
    {

        if (!is_null($request->get('sortBy'))) {
            $sortBy = $request->get('sortBy','latest');
        } else {
            $sortBy = $request->get('sortby','latest');
        }

        $data = array(
            'limit'         => $request->get("limit", 30),
            'sortby'        => $sortBy,
            'status'        => $request->get('status','active'),
            'name'          => $request->get('name',null),
            'page'          => $request->get('page',1),
        );


        $categoryIds = $request->get('categoryIds',null);

        if (!is_null($categoryIds)) {
            $categoryIds = json_decode($categoryIds);
        }

        $data['categoryIds'] = $categoryIds;

        $affiliateProductService = $this->get("yilinker_merchant.service.api.affiliate_product");

        $productResponse = $affiliateProductService->getAffiliateProducts($data);

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = $productResponse;

        return $this->jsonResponse();
    }


    /**
     * Save Selected Products and Remove selected products
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Select Product",
     *     parameters={
     *         {"name"="access_token", "dataType"="string", "required"=false},
     *         {"name"="manufacturerProductIds", "dataType"="json", "required"=true},
     *         {"name"="removeManufacturerProductIds", "dataType"="json", "required"=true},
     *     }
     * )
     */
    public function saveAffiliateProductAction(Request $request)
    {
        $data = array();
        $response = array();
        $isSuccessful = true;
        $message = "";

        $em = $this->get("doctrine.orm.entity_manager");
        $em->beginTransaction();

        try{

            $affiliateProductService = $this->get("yilinker_merchant.service.api.affiliate_product");
            $manufacturerProductIds = $request->get('manufacturerProductIds',null);
            $removeManufacturerProductIds = $request->get('removeManufacturerProductIds',null);

            //remove products
            if (!is_null($removeManufacturerProductIds)) {
                $data['removeManufacturerProductIds'] = json_decode($removeManufacturerProductIds);
            }

            $removeProductResponse = $affiliateProductService->unBindAffiliateProducts($data);

            if (!is_null($manufacturerProductIds)) {
                $manufacturerProductIds = json_decode($manufacturerProductIds);
            }

            // add products
            $data['manufacturerProductIds'] = $manufacturerProductIds;

            $productResponse = $affiliateProductService->saveAffiliateProducts($data);

            // responses
            $response['save'] = $productResponse['data'];
            $response['remove'] = $removeProductResponse['data'];

            if ($productResponse['hasError'] > 0 || $removeProductResponse['hasError'] > 0) {
                $isSuccessful = false;
            }

            $em->commit();
        }
        catch(\Exception $e){
            $em->rollback();
            $isSuccessful = false;
            $message = 'Something went wrong.';
        }

        $this->jsonResponse['isSuccessful'] = $isSuccessful;
        $this->jsonResponse['data'] = $response;
        $this->jsonResponse['message'] = $message;

        return new JsonResponse($this->jsonResponse);
    }


    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }


    private function preProcessProduct($products)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $locationService = $this->get('yilinker_core.service.location.location');
        $assetsHelper = $this->get('templating.helper.assets');

        foreach ($products as &$product) {

            $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
            $languages = $tbProduct->getLanguages($tbProduct->find($product['id']), true);
            $countries = $tbProduct->getCountries($tbProduct->find($product['id']), true);


            $selectedCountries = array();
            $selectedLanguages = array();
            foreach ($countries as $country) {

                $code = $country->getCode();
                $image = $assetsHelper->getUrl(
                    '/images/country-flag/'.strtolower($code).'.png'
                );

                array_push($selectedCountries, array(
                    "countryId" => $country->getCountryId(),
                    "name" => $country->getName(),
                    "code" => $code,
                    "image" => $image
                ));

                $languageCountries = $country->getLanguageCountryIn($languages);

                foreach($languageCountries as $languageCountry){
                    array_push($selectedLanguages, array(
                        "languageId" => $languageCountry->getLanguage()->getLanguageId(),
                        "languageName" => $languageCountry->getLanguage()->getName(),
                        "languageCode" => $languageCountry->getLanguage()->getCode(),
                        "countryId" => $languageCountry->getCountry()->getCountryId(),
                        "countryName" => $languageCountry->getCountry()->getName(),
                        "countryCode" => $languageCountry->getCountry()->getCode()
                    ));
                }
            }

            $product['selectedLanguages'] = $selectedLanguages;
            $product['selectedCountries'] = $selectedCountries;
        }

        return $products;
    }

    protected function processAffiliate($data = array())
    {
        $httpCode = 200;
        $productService = $this->get("yilinker_core.service.product.product");
        $em = $this->get("doctrine.orm.entity_manager");

        $statuses = $data['status'] === 'all' ? array('statuses' => array(Product::ACTIVE,Product::INACTIVE))
                            : array('statuses' => $data['status']);

        $inhouseProduct = $em
                ->getRepository('YilinkerCoreBundle:InhouseProduct')
                ->searchBy(
                    array_merge($statuses,
                    array(
                        'affiliate'    => $this->getUser(),
                        'query'        => $data['keyword']
                    ))
                )
                ->setLimit($data['perPage'])
            ;

        $products = $inhouseProduct->getResult();

        $products = $productService->constructProduct($products,$data['country']);

        $products = $this->preProcessProduct($products);
        
        $response['message'] = "No products found";
        $response['data']['totalPage'] = (int) ceil($inhouseProduct->getCount()/$data['perPage']);
        $response['data']['totalProducts'] = (int) $inhouseProduct->getCount();
        $response['data']['currentPage'] = $data['page'];
        $response['data']['products'] = $this->preProcessProduct($products);
        if(count($products) > 0){
            $response['message'] = "Product successfully retrieved";
            $response['isSuccessful'] = true;
            $httpCode = 200;
        }

        $rs = new JsonResponse($response, $httpCode);
        $rs->send();
        exit;
    }
}
