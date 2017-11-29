<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Yilinker\Bundle\CoreBundle\Controller\Custom\CustomController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Repository\ProductRepository;
use Doctrine\Common\Util\Debug;

/**
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_PRODUCT_SPECIALIST') or has_role('ROLE_EXPRESS_OPERATIONS')  or has_role('ROLE_CSR_MANAGER')")
 */
class ProductController extends Controller
{
    public function listingsAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();

        $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');
        $productSearchService = $this->get('yilinker_core.service.search.product');
        $translatable = $this->get('yilinker_core.translatable.listener');

        $productId = $request->get('productId');
        $q = $request->get('q');
        $page = $request->get('page', 1);
        $countryCode = $translatable->getCountry() ? $translatable->getCountry() : Country::COUNTRY_CODE_PHILIPPINES;
        $perPage = 10;
        
        $sorting = $request->get('sorting', ProductRepository::BYDATE.'~'.ProductRepository::DIRECTION_DESC);
        $sorting = explode('~', $sorting);
        $sortType = array_shift($sorting);
        $sortDirection = array_shift($sorting);
 
        $productStatus = trim($request->get('status', null));
        $productStatuses = 
            $productStatus ? 
            array($productStatus):
            array(
                Product::ACTIVE, 
                Product::FOR_REVIEW,
                Product::DELETE,
                Product::REJECT,
                Product::INACTIVE,
            )
        ;

        $store = $em->getRepository('YilinkerCoreBundle:Store')
                    ->findOneByStoreName($request->get('store', ''));
        $seller = $store && $request->get('store', '') !== '' ? $store->getUser()->getUserId() : null;
        
        $productSearchService->setCountryLocale(strtolower($countryCode));
        $productSearch = $productSearchService->searchProductsWithElastic(
            $q, $request->get('priceFrom', null), $request->get('priceTo', null),
            $request->get('category', null), $seller, trim($request->get('brand')),
            null, $sortType,  $sortDirection,
            null, $page, $perPage,
            true, true, null, 
            null, null, $productStatuses,
            null, null, null,
            $productId, array($countryCode), null
        );
      //  echo '<pre>';  Debug::dump($productSearch);exit;
        $aggregations = $productSearchService->getAggregations();

        $countries = $countryRepository->filterBy()->getResult();
        $country = $countryRepository->findOneByCode($countryCode);

        $data = compact(
            'aggregations',
            'productSearch',
            'page',
            'perPage',
            'countries',
            'country'
        );

        return $this->render('YilinkerBackendBundle:Product:listings.html.twig', $data);
    }


    /**
     * Get product detail for rendering to modal
     *
     * @param int $productId
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductDetailAction($productId, Request $request)
    {
        $response = array(
            "isSuccessful" => false,
            "message"      => "Product not found",
            "data"         => array(),
        );

        $em = $this->getDoctrine()->getEntityManager();
        $translatable = $this->get('yilinker_core.translatable.listener');
        $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');
        $languageRepository = $em->getRepository('YilinkerCoreBundle:Language');
        $productAttributeValueRepository = $em->getRepository('YilinkerCoreBundle:ProductAttributeValue');
        $product = $em->getRepository('YilinkerCoreBundle:Product')
                      ->findOneby(array('productId' => $productId));

        $requestCountryCode = $translatable->getCountry() ? $translatable->getCountry() : Country::COUNTRY_CODE_PHILIPPINES;

        $requestCountry = $countryRepository->findOneByCode($requestCountryCode);
        $productCountryStatus = $product->getProductCountryStatus($requestCountry);

        $code = 404;
        if($product){
            $assetHelper = $this->get('templating.helper.assets');
            $user = $product->getUser();
            $shippingCategory = $product->getShippingCategory();
            $countryTranslations = array();
            $warehouses = $product->getProductWarehouses(true);
            foreach($warehouses as $warehouse){
                $countryCode = $warehouse->getCountryCode();
                if(isset($countryTranslations[$countryCode]) === false){
                    $country = $countryRepository->findOneBy(array(
                        'code' => $countryCode
                    ));
                    if($country && $country->getLanguage()){
                        $language = $country->getLanguage();
                        $languageCode = $language->getCode();
                        $product->setLocale($languageCode);
                        $em->refresh($product);

                        $images = $product->getImages(true);
                        foreach($images as $key => $image){
                            $fullpathImages = array();
                            foreach($image['sizes'] as $sizekey => $imageSize){
                                $fullpathImages[$sizekey] = $assetHelper->getUrl($imageSize, 'product');
                            }
                            $images[$key]['sizes'] = $fullpathImages;
                        }
                        
                        $countryTranslations[$countryCode] = array(
                            'language'         => $language->getName(),
                            'name'             => $product->getName(),
                            'dateAdded'        => $warehouse->getDateAdded()->format('Y-m-d H:i:s'),
                            'shortDescription' => $product->getShortDescription(),
                            'description'      => $product->getDescription(),
                            'images'           => $images,
                            'attributes'       => $productAttributeValueRepository->getGroupedProductAttributesByProduct(
                                $product, $languageCode, true
                            ),
                            'shippingCategory' => null,
                        );

                        if($shippingCategory){
                            $shippingCategory->setLocale($languageCode);
                            $em->refresh($shippingCategory);
                            $countryTranslations[$countryCode]['shippingCategory'] = $shippingCategory->toArray();
                        }
                    }
                }
            }
            $language = $languageRepository->findOneByCode($product->getDefaultLocale());
            $languageCountry = $language ? $language->getLanguageCountries()->first(): null;
            if ($languageCountry) {
                $country = $languageCountry->getCountry();
                $countryCode = $country->getCode(true);
                if(isset($countryTranslations[$countryCode]) === false){
                    $languageCode = $language->getCode();
                    $product->setLocale($languageCode);
                    $em->refresh($product);

                    $images = $product->getImages(true);
                    foreach($images as $key => $image){
                        $fullpathImages = array();
                        foreach($image['sizes'] as $sizekey => $imageSize){
                            $fullpathImages[$sizekey] = $assetHelper->getUrl($imageSize, 'product');
                        }
                        $images[$key]['sizes'] = $fullpathImages;
                    }
                    
                    $countryTranslations[$countryCode] = array(
                        'language'         => $language->getName(),
                        'name'             => $product->getName(),
                        'dateAdded'        => $product->getDateCreated()->format('Y-m-d H:i:s'),
                        'shortDescription' => $product->getShortDescription(),
                        'description'      => $product->getDescription(),
                        'images'           => $images,
                        'attributes'       => $productAttributeValueRepository->getGroupedProductAttributesByProduct(
                            $product, $languageCode, true
                        ),
                        'shippingCategory' => null,
                    );

                    if($shippingCategory){
                        $shippingCategory->setLocale($languageCode);
                        $em->refresh($shippingCategory);
                        $countryTranslations[$countryCode]['shippingCategory'] = $shippingCategory->toArray();
                    }
                }
            }

            $productService = $this->get('yilinker_core.service.product.product');
            $units = $productService->reloadUnitDetailsByCountry($product);
            $storeDetails = $user->getStore()
                                 ->getStoreDetails();

            $defaultShippingCategory = $product->getShippingCategory();
            if ($defaultShippingCategory) {
                $defaultShippingCategory->setLocale($product->getDefaultLocale());
                $em->refresh($defaultShippingCategory);
            }

            $remarks = $em->getRepository('YilinkerCoreBundle:ProductRemarks')
                          ->findBy(array(
                              'product' => $product,
                              'countryCode' => $requestCountryCode
                          ));
            $arrayRemarks = array();
            foreach ($remarks as $remark) {
                $arrayRemarks[] = $remark->toArray();
            }

            $brand = $product->getBrand();
            $response['data'] = array(
                'productUnits'       => $units,
                'seller'             => $storeDetails,
                'productCategory'    => $product->getProductCategory() ? $product->getProductCategory()->toArray(): array(),
                'shippingCategory'   => $defaultShippingCategory ? $defaultShippingCategory->toArray() : array(),
                'brand'              => $brand->toArray(),
                'video'              => $product->getYoutubeVideoUrl(),
                'translations'       => $countryTranslations,
                'isForReview'        => $productCountryStatus == Product::FOR_REVIEW,
                'productId'          => $product->getProductId(),
                'requestCountryCode' => $requestCountryCode,
                'remarks'            => $arrayRemarks
            );
            $response['message'] = "Data successfully retrieved";
            $response['isSuccessful'] = true;
            $code = 200;
        }

        return new JsonResponse($response, $code);
    }
    
    /**
     * Change product status
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return JsonResponse
     */
    public function updateProductStatusAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Product status update is not permitted',
            'data'         => array(),
        );

        $em = $this->getDoctrine()->getEntityManager();
        $translatable = $this->get('yilinker_core.translatable.listener');
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $productCountryRepository = $em->getRepository('YilinkerCoreBundle:ProductCountry');
        $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');

        $action = trim(strtolower($request->get('action')));
        $countryCode = $translatable->getCountry() ? $translatable->getCountry() : Country::COUNTRY_CODE_PHILIPPINES;

        $updatedStatus = null;
        $currentStatus = null;
        if($action === 'approve'){
            $updatedStatus = Product::ACTIVE;
            $currentStatus = array(Product::FOR_REVIEW, Product::REJECT);
        }
        else if($action == 'reject'){
            $updatedStatus = Product::REJECT;
            $currentStatus = array(Product::FOR_REVIEW);
        }
        else if($action === 'disable'){
            $updatedStatus = Product::DELETE;
            $currentStatus = array(Product::ACTIVE, PRODUCT::DELETE);
        }
        else if($action === 'enable'){
            $updatedStatus = Product::ACTIVE;
            $currentStatus = array(Product::FULL_DELETE);
        }

        $country = $countryRepository->findOneByCode(strtolower($countryCode));

        if ($country) {
            $productCountry = $productCountryRepository->findOneBy(array(
                'status' => $currentStatus,
                'product' => $request->get('productId'),
                'country' => $country
            ));

            if($productCountry){
                $product = $productCountry->getProduct();
                $response['isSuccessful'] = true;
                $response['message'] = "Product status successfully updated";
                $response['data'] = array(
                    'productId'         => $product->getProductId(),
                    'originalStatus'    => $product->getStatus(true),
                    'newStatus'         => $updatedStatus,
                );

                $productCountry->setStatus($updatedStatus);
                $product->setStatus($updatedStatus);

                $em->flush();
            }
        }

        return new JsonResponse($response);
    }

    /**
     * Send Remarks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendRemarksAction (Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');
        $productCountryRepository = $em->getRepository('YilinkerCoreBundle:ProductCountry');
        $translatable = $this->get('yilinker_core.translatable.listener');

        $productId = $request->get('productId', null);
        $remarks = $request->get('remarks', null);
        $authenticatedUser = $this->getUser();
        $countryCode = $translatable->getCountry()
                       ? $translatable->getCountry()
                       : Country::COUNTRY_CODE_PHILIPPINES;
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Product status update is not permitted',
            'data'         => array(),
        );
        $country = $countryRepository->findOneByCode(strtolower($countryCode));

        if ($country) {
            $updatedStatus = Product::REJECT;
            $currentStatus = array(Product::FOR_REVIEW, Product::REJECT);

            $productCountry = $productCountryRepository->findOneBy(array(
                'status' => $currentStatus,
                'product' => $productId,
                'country' => $country
            ));

            if ($productCountry) {
                $product = $productCountry->getProduct();
                $productRemarks = $this->get('yilinker_core.service.product_remarks')
                                       ->addProductRemarks(
                                           $product,
                                           $authenticatedUser,
                                           $remarks,
                                           $updatedStatus,
                                           strtolower($countryCode)
                                       );

                $response = array (
                    'isSuccessful' => true,
                    'message'      => '',
                    'data'         => $productRemarks->toArray()
                );

                $productCountry->setStatus($updatedStatus);
                $product->setStatus($updatedStatus);

                $em->flush();
            }
        }

        return new JsonResponse($response);
    }

    public function updateShippingCategoryAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $inputCategory = (int) $request->get('shippingCategory');
        $inputProduct = (int) $request->get('product');

        $shippingCategory = $em->find('YilinkerCoreBundle:ShippingCategory', $inputCategory);
        $product = $em->find('YilinkerCoreBundle:Product', $inputProduct);

        if ($shippingCategory && $product) {

            $product->setShippingCategory($shippingCategory);
            $em->flush();

            return new JsonResponse(array(
                'isSuccessful' => true,
                'message' => '',
                'data' => array()
            ));
        }

        return new JsonResponse(array(
            'isSuccessful' => false,
            'message' => 'Please select valid shipping category.',
            'data' => array()
        ));
    }
}
