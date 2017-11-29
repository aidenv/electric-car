<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api\v3;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Services\Redis\Keys as RedisKeys;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageUploader;

use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Carbon\Carbon;
use \stdClass;

/**
 * Class ProductUploadApiController
 */
class ProductApiController extends YilinkerBaseController
{
    use FormHandler;

    /**
     * List of brands
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Brands",
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getBrandsAction (Request $request)
    {
        $q = $request->query->get('q');

        $key = RedisKeys::BRANDS.'-'.$q;
        $brands = $this->getCacheValue($key, true);

        if(!$brands){
            $productBrands = $this->get("doctrine.orm.entity_manager")
                                  ->getRepository("YilinkerCoreBundle:Brand")
                                  ->getBrandByName($q);

            $brands = array();
            foreach($productBrands as $productBrand){
                array_push($brands, $productBrand->toArray(true));
            }

            $this->setCacheValue($key, $brands);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product brands",
            "data" => $brands
        ), 200);
    }

    /**
     * List of product conditions
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product Conditions",
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getProductConditionsAction (Request $request)
    {
        $key = RedisKeys::PRODUCT_CONDITIONS;
        $productConditions = $this->getCacheValue($key, true);

        if(!$productConditions){
            $conditions = $this->get("doctrine.orm.entity_manager")
                               ->getRepository("YilinkerCoreBundle:ProductCondition")
                               ->findAll();

            $productConditions = array();
            foreach($conditions as $condition){
                array_push($productConditions, $condition->toArray(true));
            }

            $this->setCacheValue($key, $productConditions);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product conditions",
            "data" => $productConditions
        ), 200);
    }

    /**
     * List of product groups
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product Groups",
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getProductGroupsAction(Request $request)
    {
        $q = $request->get("q", null);
        $userProductGroups = $this->get("doctrine.orm.entity_manager")
                                  ->getRepository("YilinkerCoreBundle:UserProductGroup")
                                  ->getFindByName($q, $this->getUser());

        $productGroups = array();
        foreach($userProductGroups as $userProductGroup){
            array_push($productGroups, $userProductGroup->toArray());
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product groups",
            "data" => $productGroups
        ), 200);
    }

    /**
     * List of shipping categories
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Shipping Categories",
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getShippingCategoriesAction(Request $request)
    {
        $key = RedisKeys::SHIPPING_CATEGORIES;
        $shippingCategories = $this->getCacheValue($key, true);

        if(!$shippingCategories){
            $categories = $this->get("doctrine.orm.entity_manager")
                               ->getRepository("YilinkerCoreBundle:ShippingCategory")
                               ->findAll();

            $shippingCategories = array();
            foreach($categories as $category){
                array_push($shippingCategories, $category->toArray());
            }

            $this->setCacheValue($key, $shippingCategories);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Shipping categories",
            "data" => $shippingCategories
        ), 200);
    }

    /**
     * List of product categories
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product Categories",
     *     statusCodes={
     *         200={
     *              "Product categories"
     *         }
     *     },
     *     parameters={
     *         {"name"="productCategoryId", "dataType"="string", "required"=false, "description"="Product category parent"}
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getCategoriesAction(Request $request)
    {
        $productCategoryId = $request->get("productCategoryId", null);
        $queryString = $request->get("queryString", null);

        if(!$productCategoryId){
            $productCategoryId = ProductCategory::ROOT_CATEGORY_ID;
        }

        $categories = $this->get("doctrine.orm.entity_manager")
                           ->getRepository("YilinkerCoreBundle:ProductCategory")
                           ->findCategoryByParentId(
                                $productCategoryId,
                                false,
                                true,
                                $queryString,
                                $queryString? false : true
                            );

        $productCategories = array();
        foreach($categories as $category){
            array_push($productCategories, $category->toArray());
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product Categories",
            "data" => $productCategories
        ), 200);
    }

    /**
     * Product upload details
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     statusCodes={
     *         200={
     *              "productId (string)",
     *              "name (string)",
     *              "shortDescription (string)",
     *              "description (string)",
     *              "youtubeVideoUrl (string)",
     *              "productConditionId (int)",
     *              "productConditionName (string)",
     *              "productCategoryId (int)",
     *              "productCategoryName (string)",
     *              "shippingCategoryId (int)",
     *              "shippingCategoryName (string)",
     *              "brandId (int)",
     *              "brandName (string)",
     *              "productGroups (JSON array)",
     *              "hasCombination (bool)",
     *              "status (int)",
     *              "productImages (JSON array)",
     *              "productUnits (JSON array)",
     *              "productVariants (JSON array)"
     *         }
     *     },
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=false, "description"="Product id"}
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getUploadDetailsAction(Request $request)
    {
        $productId = $request->get("productId", null);
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->isAffiliate()) {
            $product = $this->get("doctrine.orm.entity_manager")
                            ->getRepository("YilinkerCoreBundle:InhouseProduct")
                            ->searchBy(
                                array('affiliate' => $authenticatedUser )
                                )
                            ->getOneOrNullResult();

        } else {
            $product = $this->get("doctrine.orm.entity_manager")
                            ->getRepository("YilinkerCoreBundle:Product")
                            ->getOnebyIdOrSlug($productId, null, $authenticatedUser)
                            ->getOneOrNullResult();
        }

        if(!$product){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Product not found",
                "data" => new stdClass()
            ), 404);
        }

        $productUploadService = $this->get("yilinker_merchant.service.product_uploader");

        $translationService = $this->get("yilinker_core.translatable.listener");
        $countryLocale = $translationService->getCountry();

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product Details",
            "data" => $productUploadService->constructUploadDetails($product, $request->getLocale(), false, $countryLocale)
        ), 200);
    }

    /**
     * Handles Product Upload
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     statusCodes={
     *         200={
     *              "Product upload details"
     *         },
     *         400={
     *             "Field errors, failed upload or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="name", "dataType"="string", "required"=true, "description"="Product name"},
     *         {"name"="shortDescription", "dataType"="string", "required"=true, "description"="Product short description"},
     *         {"name"="description", "dataType"="string", "required"=true, "description"="Product description"},
     *         {"name"="youtubeVideoUrl", "dataType"="string", "required"=false, "description"="Product youtube video url"},
     *         {"name"="productConditionId", "dataType"="int", "required"=true, "description"="Product condition"},
     *         {"name"="productCategoryId", "dataType"="int", "required"=true, "description"="Product category"},
     *         {"name"="shippingCategoryId", "dataType"="int", "required"=true, "description"="Product shipping category"},
     *         {"name"="brand", "dataType"="string", "required"=true, "description"="Product brand"},
     *         {"name"="productGroups", "dataType"="JSON Array", "required"=true, "description"="[
                   'Sample product group 1',
                   'Sample product group 2',
                   'Sample product group 3'
               ]"},
     *         {"name"="productImages", "dataType"="JSON Array", "required"=true, "description"="[
                   {'name':'test_image_1.jpg', 'isPrimary':false},
                   {'name':'test_image_2.jpg', 'isPrimary':false},
                   {'name':'test_image_3.jpg', 'isPrimary':true}
               ]"},
     *         {"name"="productUnits", "dataType"="JSON Array", "required"=true, "description"="[
                   {
                       'attributes':[
                           {
                                'name':'size',
                                'value':'S'
                            },
                           {
                                'name':'color',
                                'value':'blue'
                            }
                       ],
                       'images':[
                           {'name':'test_image_1.jpg'},
                           {'name':'test_image_2.jpg'}
                       ],
                       'sku':'SMPLSKU1',
                       'length':1.25,
                       'width':1.25,
                       'weight':1.25,
                       'height':1.25
                     }
                ]"},
     *         {"name"="isDraft", "dataType"="boolean", "required"=true, "description"="Is draft"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function createAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");

        $postData = array(
            "name" => $request->get("name", null),
            "shortDescription" => $request->get("shortDescription", null),
            "description" => $request->get("description", null),
            "youtubeVideoUrl" => $request->get("youtubeVideoUrl", null),
            "condition" => $request->get("productConditionId", null),
            "productCategory" => $request->get("productCategoryId", null),
            "shippingCategory" => $request->get("shippingCategoryId", null),
            "brand" => $request->get("brand", null),
            "productGroups" => $request->get("productGroups", null),
            "productImages" => $request->get("productImages", null),
            "productUnits" => $request->get("productUnits", null)
        );

        $authenticatedUser = $this->getUser();
        $isDraft = filter_var($request->get('isDraft' , false), FILTER_VALIDATE_BOOLEAN);
        $form = $this->transactForm(
                    "api_v3_product_upload",
                    new Product(),
                    $postData,
                    array(
                        "csrf_protection" => false,
                        "isCreate" => true,
                        "user" => $authenticatedUser,
                        "isDraft" => $isDraft,
                    )
        );

        if($form->isValid()){

            $productDetails = array();

            try{

                $em->beginTransaction();

                $product = $form->getData();
                $em->persist($product);
                $product->setUser($authenticatedUser);
                $productUploadService = $this->get("yilinker_merchant.service.product_uploader");
                $product = $productUploadService->createProductCascade(
                    $product,
                    $authenticatedUser,
                    $form->getConfig()->getOptions(),
                    $form->get("brand")->getData(),
                    $form->get("productGroups")->getData(),
                    $form->get("productImages")->getData(),
                    $form->get("productUnits")->getData(),
                    $isDraft
                );

                $em->flush();

                $imageUploader = $this->get("yilinker_core.service.image_uploader");
                $imageUploader->uploadProductImages($product);

                $em->commit();

                $productPersister = $this->get('fos_elastica.object_persister.yilinker_online.product');
                $productPersister->insertOne($product);

                $translationService = $this->get("yilinker_core.translatable.listener");
                $countryLocale = $translationService->getCountry();
                $productDetails = $productUploadService->constructUploadDetails(
                                    $product,
                                    $request->getLocale(),
                                    false,
                                    $countryLocale
                                );
            }
            catch(\Exception $e){

                if($em->getConnection()->isTransactionActive()){
                    $em->rollback();
                }

                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Something went wrong",
                    "data" => new stdClass()
                ), 400);
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Product details",
                "data" => $productDetails
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, false)
            )
        ), 400);
    }

    /**
     * Handles Product Edit
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product",
     *     statusCodes={
     *         200={
     *              "Product upload details"
     *         },
     *         400={
     *             "Field errors, failed upload or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="productId", "dataType"="int", "required"=true, "description"="Product id"},
     *         {"name"="name", "dataType"="string", "required"=true, "description"="Product name"},
     *         {"name"="shortDescription", "dataType"="string", "required"=true, "description"="Product short description"},
     *         {"name"="description", "dataType"="string", "required"=true, "description"="Product description"},
     *         {"name"="youtubeVideoUrl", "dataType"="string", "required"=false, "description"="Product youtube video url"},
     *         {"name"="productConditionId", "dataType"="int", "required"=true, "description"="Product condition"},
     *         {"name"="productCategoryId", "dataType"="int", "required"=true, "description"="Product category"},
     *         {"name"="shippingCategoryId", "dataType"="int", "required"=true, "description"="Product shipping category"},
     *         {"name"="brand", "dataType"="string", "required"=true, "description"="Product brand"},
     *         {"name"="productGroups", "dataType"="JSON Array", "required"=true, "description"="[
                   'Sample product group 1',
                   'Sample product group 2',
                   'Sample product group 3'
               ]"},
     *         {"name"="productImages", "dataType"="JSON Array", "required"=true, "description"="[
                   {'name':'test_image_1.jpg', 'isPrimary':false},
                   {'name':'test_image_2.jpg', 'isPrimary':false},
                   {'name':'test_image_3.jpg', 'isPrimary':true}
               ]"},
     *         {"name"="productUnits", "dataType"="JSON Array", "required"=true, "description"="[
                   {
                       'attributes':[
                           {
                                'name':'size',
                                'value':'S'
                            },
                           {
                                'name':'color',
                                'value':'blue'
                            }
                       ],
                       'images':[
                           {'name':'test_image_1.jpg'},
                           {'name':'test_image_2.jpg'}
                       ],
                       'sku':'SMPLSKU1',
                       'length':1.25,
                       'width':1.25,
                       'weight':1.25,
                       'height':1.25
                     }
                ]"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function editAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $authenticatedUser = $this->getUser();

        $product = $em->getRepository("YilinkerCoreBundle:Product")
                      ->findOneBy(array(
                        "productId" => $request->get("productId", null),
                        "user" => $authenticatedUser
        ));

        $translationService = $this->get("yilinker_core.translatable.listener");
        $countryLocale = $translationService->getCountry();
        $country = $em->getRepository("YilinkerCoreBundle:Country")->findOneByCode($countryLocale);

        if(!$product){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Product not found"
            ), 404);
        }
        elseif($product && $country){
            $productCountry = $product->getProductCountryByCountry($country);

            if(
                !$productCountry ||
                $productCountry->getStatus() == Product::FOR_REVIEW
            ){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Products that are under review are not editable.",
                    "data" => array(
                        "errors" => "Products that are under review are not editable."
                    )
                ), 400);
            }
        }

        if($product->getDefaultLocale() != $request->getLocale()){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "This endpoint is not accessible for this locale"
            ), 400);
        }

        $postData = array(
            "name" => $request->get("name", null),
            "shortDescription" => $request->get("shortDescription", null),
            "description" => $request->get("description", null),
            "youtubeVideoUrl" => $request->get("youtubeVideoUrl", null),
            "condition" => $request->get("productConditionId", null),
            "productCategory" => $request->get("productCategoryId", null),
            "shippingCategory" => $request->get("shippingCategoryId", null),
            "brand" => $request->get("brand", null),
            "productGroups" => $request->get("productGroups", null),
            "productImages" => $request->get("productImages", null),
            "productUnits" => $request->get("productUnits", null)
        );

        $form = $this->transactForm(
                    "api_v3_product_upload",
                    $product,
                    $postData,
                    array(
                        "csrf_protection" => false,
                        "isCreate" => false,
                        "user" => $authenticatedUser,
                        "isDraft" => false,
                        "product" => $product
                    )
        );

        if($form->isValid()){

            $productDetails = array();

            try{

                $em->beginTransaction();

                $productUploadService = $this->get("yilinker_merchant.service.product_uploader");
                $product = $form->getData();
                $product = $productUploadService->updateProductCascade(
                    $product,
                    $authenticatedUser,
                    $form->getConfig()->getOptions(),
                    $form->get("brand")->getData(),
                    $form->get("productGroups")->getData(),
                    $form->get("productImages")->getData(),
                    $form->get("productUnits")->getData()
                );

                $em->flush();

                /**
                 * Will flush the entity itself since the product country
                 * in the listener is not managed by the entity manager
                 */
                $productCountry = $product->getProductCountryByCountry($country);
                $em->flush($productCountry);

                $imageUploader = $this->get("yilinker_core.service.image_uploader");
                $imageUploader->uploadProductImages($product);

                $em->commit();

                $productPersister = $this->get('fos_elastica.object_persister.yilinker_online.product');
                $productPersister->insertOne($product);

                $translationService = $this->get("yilinker_core.translatable.listener");
                $countryLocale = $translationService->getCountry();
                $productDetails = $productUploadService->constructUploadDetails(
                                    $product,
                                    $request->getLocale(),
                                    false,
                                    $countryLocale
                                );
            }
            catch(\Exception $e){

                if($em->getConnection()->isTransactionActive()){
                    $em->rollback();
                }

                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Something went wrong",
                    "data" => new stdClass()
                ), 400);
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Product details",
                "data" => $productDetails
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, false)
            )
        ), 400);
    }

    /**
     * Country Setup
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product CountrySeup",
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="productId"},
     *         {"name"="code", "dataType"="string", "required"=true, "description"="countrycode"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function countrySetupAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        //get product
        $productId = $request->get('productId');
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $product = $tbProduct->find($productId);
        $this->throwNotFoundUnless($product, 'Product does not exist');

        //get country
        $countryCode = strtolower($request->get('code','ph'));
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $country = $tbCountry->findOneByCode($countryCode);
        $this->throwNotFoundUnless($country, 'Country does not exist');

        //set country of tranlation
        $transListener = $this->get('yilinker_core.translatable.listener');
        $transListener->setCountry($countryCode);

        $tbLogistic = $em->getRepository('YilinkerCoreBundle:Logistics');

        $tbLogiscsArr = array();
        foreach ($tbLogistic->findAll() as $logistics) {
            $tbLogiscsArr[] = $logistics->toArray();
        }

        $productWarehousesArr = array();
        $productWarehousesIds = array();
        foreach ($product->getProductWarehouses(true) as $productwarehouse) {
            $pwdetail = $productwarehouse->toArray();
            $pwdetail['is_local'] = strtolower($productwarehouse->getUserWarehouse()->getCountry()->getCode()) == strtolower($request->get('country_code'));
            $productWarehousesArr[] = $pwdetail;
            array_push($productWarehousesIds, $pwdetail['user_warehouse'] ? $pwdetail['user_warehouse']['id'] : '');
        }

        $warehouses = $em->getRepository('YilinkerCoreBundle:UserWarehouse')
                ->getUserWarehouses($this->getUser())
                ->getResult();

        $warehousesArr = array();
        foreach ($warehouses as $warehouse) {

            if (!in_array($warehouse->getUserWarehouseId(),$productWarehousesIds)) {
                $local = strtolower($request->get('country_code')) == strtolower($warehouse->getCountry()->getCode()) ? true: false;
                $pw['id'] = '';
                $pw['user_warehouse'] = $warehouse->toArray();
                $pw['logistic'] = new \stdClass();
                $pw['handlingFee'] = '';
                $pw['is_cod'] = $local;
                $pw['is_local'] = $local;
                $pw['priority'] = 0;

                $warehousesArr[] = $pw;
            }
        }

        $productUnit = $em->getRepository('YilinkerCoreBundle:ProductUnit');
        $productUnits = $productUnit->findByProduct($product);

        $productDetails = $product ? $product->getDetails(false) : array();
        $productDetails = $this->preProcessProductDetails($productDetails);

        if($product && $country){
            $productCountry = $product->getProductCountryByCountry($country);
            $productDetails["status"] = $productCountry->getStatus();
        }

        $data['product'] = $productDetails;
        $data['product']['store'] = $this->getUser()->getStorename();
        $data['product']['brand'] = $this->getBrandName($product);
        $data['product']['category'] = $product->getProductCategory() ? $product->getProductCategory()->getName() : null;
        $data['defaultUnit'] = $product->getDefaultUnit()->toArray();
        $data['productWarehouses'] = array_merge($productWarehousesArr,$warehousesArr);
        $data['logistics'] = $tbLogiscsArr;

        return new JsonResponse(array(
            'isSuccessful' => true,
            'data'         => $data,
        ));
    }

    private function getBrandName($product)
    {
        $details = array('brandName' => '');

        if($product->getBrand() && $product->getBrand()->getBrandId() == Brand::CUSTOM_BRAND_ID){
            $customBrand = $product->getCustomBrand()->first();
            if($customBrand){
                $details["brandName"] = $customBrand->getName();
            }
            else{
                $details["brandName"] = "None";
            }
        }
        elseif($product->getBrand() && $product->getBrand()->getBrandId() != Brand::CUSTOM_BRAND_ID){
            $details["brandName"] = $product->getBrand()->getName();
        }
        else{
            $details["brandName"] = "None";
        }

        return $details['brandName'];

    }

    /**
     * Country Setup - Save combinations
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product CountrySeup",
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="productId"},
     *         {"name"="code", "dataType"="string", "required"=true, "description"="countrycode"},
     *         {"name"="productUnitId", "dataType"="json", "required"=true, "description"="productUnitId"},
     *         {"name"="price", "dataType"="json", "required"=true, "description"="price"},
     *         {"name"="discountedPrice", "dataType"="json", "required"=true, "description"="discountedPrice"},
     *         {"name"="commission", "dataType"="json", "required"=true, "description"="commission"},
     *         {"name"="status", "dataType"="json", "required"=true, "description"="status"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function saveCombinationAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $response = array(
            'isSuccessful' => true,
            'data' => '',
            'message' => 'Succcesfully Saved Combinations',
        );

        //get country
        $countryCode = strtolower($request->get('code','ph'));

        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $country = $tbCountry->findOneByCode($countryCode);
        $this->throwNotFoundUnless($country, 'Country does not exist');

        //set country of tranlation
        $transListener = $this->get('yilinker_core.translatable.listener');
        $transListener->setCountry($countryCode);

        $productId = $request->get('productId');
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $product = $tbProduct->find($productId);
        $this->throwNotFoundUnless($product, 'Product does not exist');

        if($product && $country){
            $productCountry = $product->getProductCountryByCountry($country);

            if(
                !$productCountry ||
                $productCountry->getStatus() == Product::FOR_REVIEW
            ){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Products that are under review are not editable.",
                    "data" => array(
                        "errors" => "Products that are under review are not editable."
                    )
                ), 400);
            }
        }

        $jsonData = array(
            'productUnitId'  => json_decode($request->get('productUnitId')),
            'price'          => json_decode($request->get('price')),
            'discountedPrice'=> json_decode($request->get('discountedPrice')),
            'commission'     => json_decode($request->get('commission')),
            'status'         => json_decode($request->get('status')),
        );

        $formData = array();
        foreach ($jsonData['productUnitId'] as $i => $unitId) {
            $formData['units'][$i]['productUnitId'] = $unitId;
            $formData['units'][$i]['price'] = $jsonData['price'][$i];
            $formData['units'][$i]['discountedPrice'] = $jsonData['discountedPrice'][$i];
            $formData['units'][$i]['commission'] = $jsonData['commission'][$i];
            $formData['units'][$i]['status'] = $jsonData['status'][$i];
        }

        $form = $this->createForm('api_v3_product_country_unit', $product);
        $form->submit($formData);

        if ($form->isValid()) {

            try{

                $em->beginTransaction();

                $currentProductCountry = $product->getProductCountryByCountry($country);
                if(!$currentProductCountry){
                    $productCountry = new ProductCountry;
                    $productCountry->setProduct($product)
                                   ->setCountry($country)
                                   ->setStatus(Product::FOR_COMPLETION);

                    $currentProductCountry = $productCountry;
                }

                foreach ($formData['units'] as $unit) {
                    $tbProductUnitReppo = $em->getRepository('YilinkerCoreBundle:ProductUnit');
                    $productUnit = $tbProductUnitReppo->findOneBy(array('product'=> $product, 'productUnitId' => $unit['productUnitId']));
                    if ($productUnit) {
                        $productUnit->setLocale(strtolower($country->getCode()));
                        $em->refresh($productUnit);
                        $productUnit->setPrice((string)$unit['price']);
                        $productUnit->setDiscountedPrice((string)$unit['discountedPrice']);
                        $productUnit->setCommission((string)$unit['commission']);
                        $productUnit->setStatus((string)$unit['status']);

                        $em->persist($productUnit);
                    }
                }

                $em->flush();

                /**
                 * Will flush the entity itself since the product country
                 * in the listener is not managed by the entity manager
                 */
                $productCountry = $product->getProductCountryByCountry($country);
                $em->flush($productCountry);

                $em->commit();

                $productPersister = $this->container->get('fos_elastica.object_persister.yilinker_online.product');
                $productPersister->insertOne($product);
            }
            catch(\Exception $e){

                if($em->getConnection()->isTransactionActive()){
                    $em->rollback();
                }

                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => 'An error occured.',
                );
            }

        } else {
            $response = array(
                'isSuccessful' => false,
                'data' => '',
                'message' => implode($formErrorService->throwInvalidFields($form), ' \n'),
            );

        }

        return new JsonResponse($response);
    }

    /**
     * Country Setup - Set Warehouse
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product CountrySeup",
     *     parameters={
     *         {"name"="code", "dataType"="string", "required"=true, "description"="countrycode"},
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="productId"},
     *         {"name"="userWarehouse", "dataType"="string", "required"=true, "description"="userWarehouse"},
     *         {"name"="logistics", "dataType"="string", "required"=true, "description"="logistics"},
     *         {"name"="isCod", "dataType"="string", "required"=true, "description"="isCod"},
     *         {"name"="handlingFee", "dataType"="string", "required"=true, "description"="handlingFee"},
     *         {"name"="priority", "dataType"="string", "required"=true, "description"="priority"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function setWarehouseAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $response = array(
            'isSuccessful' => true,
            'data' => '',
            'message' => 'Succcesfully Saved',
        );

        $productId = $request->get('productId');
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $product = $tbProduct->find($productId);
        $this->throwNotFoundUnless($product, 'Product does not exist');

        //get country
        $countryCode = strtolower($request->get('code','ph'));
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $country = $tbCountry->findOneByCode($countryCode);
        $this->throwNotFoundUnless($country, 'Country does not exist');

        $product->setCountry($country);

        //set country of tranlation
        $transListener = $this->get('yilinker_core.translatable.listener');
        $transListener->setCountry($countryCode);

        $formData = array(
            'userWarehouse'  => $request->get('userWarehouse'),
            'logistics'  => $request->get('logistics'),
            'isCod'  => $request->get('isCod') === 'true' ? true : false,
            'priority'  => $request->get('priority'),
            'handlingFee'  => $request->get('handlingFee'),
        );

        $form = $this->createForm('api_v3_product_country_warehouse', $product, array(
                'userWarehouse' => $formData['userWarehouse'],
                'priority'      => $formData['priority']
            ));

        $form->submit($formData);

        if ($form->isValid()) {

            try{
                $em->beginTransaction();

                $currentProductCountry = $product->getProductCountryByCountry($country);
                if(!$currentProductCountry){
                    $productCountry = new ProductCountry;
                    $productCountry->setProduct($product)
                                   ->setCountry($country)
                                   ->setStatus(Product::FOR_COMPLETION);

                    $em->persist($productCountry);
                }

                $em->flush();

                /**
                 * Will flush the entity itself since the product country
                 * in the listener is not managed by the entity manager
                 */
                $productCountry = $product->getProductCountryByCountry($country);
                $em->flush($productCountry);

                $em->commit();

                $productPersister = $this->container->get('fos_elastica.object_persister.yilinker_online.product');
                $productPersister->insertOne($product);
            }
            catch(\Exception $e){
                $response = array(
                    'isSuccessful' => false,
                    'message' => 'Something went wrong',
                    'data' => ''
                );
            }
        } else {
            $response = array(
                'isSuccessful' => false,
                'data' => '',
                'message' => implode($formErrorService->throwInvalidFields($form), ' \n'),
            );
        }

        return new JsonResponse($response);
    }

    /**
     * Country Setup - Country store
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product CountrySeup",
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="productId"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function countryStoreAction(Request $request)
    {

        $em = $this->getDoctrine()->getEntityManager();
        $countryRepo = $em->getRepository('YilinkerCoreBundle:Country');

        $locationService = $this->get('yilinker_core.service.location.location');
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');

        $productId = $request->get('productId');
        $countries = $tbProduct->getCountries($tbProduct->find($productId), true);

        $productCountryIds = array();

        foreach ($countries as $productCountry) {
            array_push($productCountryIds, $productCountry->getCountryId());
        }

        $countryStores = array();
        foreach($countryRepo->findByStatus(1) as $country) {
            $loc = $locationService->countryDetail($country);
            $loc['isAvailable'] = false;
            $loc['currency'] = $country->getCurrency()->toArray();

            if (in_array($country->getCountryId(), $productCountryIds)) {
                $loc['isAvailable'] = true;
            }
            $countryStores[] = $loc;
        }

        return new JsonResponse(array(
            'isSuccessful' => true,
            'data' => $countryStores,
        ));

    }

    /**
     * Product languages
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product Translation",
     *     statusCodes={
     *         200="[{'languageId':1,'languageName':'English','languageCode':'en','countryId':164,'countryName':'Philippines','countryCode':'PH','isSelected':true},{'languageId':2,'languageName':'Chinese','languageCode':'cn','countryId':49,'countryName':'China','countryCode':'CN','isSelected':true},{'languageId':5,'languageName':'Malay','languageCode':'ms','countryId':127,'countryName':'Malaysia','countryCode':'MY','isSelected':false}]",
     *         400 = "Error"
     *     },
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="productId"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getLanguagesAction(Request $request)
    {
        $productId = $request->get("productId", null);

        $em = $this->get("doctrine.orm.entity_manager");
        $productRepository = $em->getRepository("YilinkerCoreBundle:Product");
        $languageRepository = $em->getRepository("YilinkerCoreBundle:Language");
        $assetsHelper = $this->get('templating.helper.assets');

        $product = $productRepository->find($productId);

        if($product){
            $availableLanguages = $languageRepository->filterBy()->getResult();
            $productLanguages = $productRepository->getLanguages($product, true);

            $languages = array();
            foreach($availableLanguages as $key => $availableLanguage){
                $countryLanguage = $availableLanguage->getPrimaryLanguageCountry();

                if($countryLanguage){
                    $country = $countryLanguage->getCountry();
                    array_push($languages, array(
                        "languageId" => $availableLanguage->getLanguageId(),
                        "languageName" => $availableLanguage->getName(),
                        "languageCode" => $availableLanguage->getCode(),
                        "countryId" => $country->getCountryId(),
                        "countryName" => $country->getName(),
                        "countryCode" => $country->getCode(),
                        "isSelected" => in_array($availableLanguage, $productLanguages)? true : false
                    ));
                }
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Product languages",
                "data" => $languages
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Product not found"
        ), 404);
    }

    /**
     * Get Product Translation details
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product Translation",
     *     statusCodes={
     *         200={
     *              "Sample use json editor to decode this replace single quote with double quote :
{'isSuccessful':true,'message':'Product Details','data':{'default':{'productId':'2200','name':'Hello World','shortDescription':'Lorem ipsum','description':'Lorem ipsum dolor sit amet','youtubeVideoUrl':null,'productConditionId':1,'productConditionName':'New','productCategoryId':2,'productCategoryName':'Animals & Pet Supplies','shippingCategoryId':1,'shippingCategoryName':'Cameras - Point & Shoot, Bridge','brandId':1,'status':7,'hasCombination':true,'brandName':'Kev','productGroups':['Test','Hey'],'productImages':[{'raw':'1009_155_1462864373.png','imageLocation':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/1009_155_1462864373.png?ver=v1','sizes':{'thumbnail':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/thumbnail/1009_155_1462864373.png?ver=v1','small':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/small/1009_155_1462864373.png?ver=v1','medium':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/medium/1009_155_1462864373.png?ver=v1','large':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/large/1009_155_1462864373.png?ver=v1'},'isSelected':true}],'productUnits':[{'productUnitId':'3383','quantity':0,'sku':'SMPLSKU46','price':'0.00','discountedPrice':'0.00','discount':0,'length':'1.25','width':'1.25','height':'1.25','weight':'1.25','attributes':[{'id':'1610','name':'size','value':'L'},{'id':'1611','name':'color','value':'blue'}],'images':[{'raw':'1009_155_1462864373.png','imageLocation':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/1009_155_1462864373.png?ver=v1','isPrimary':true,'isDeleted':false,'sizes':{'thumbnail':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/thumbnail/1009_155_1462864373.png?ver=v1','small':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/small/1009_155_1462864373.png?ver=v1','medium':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/medium/1009_155_1462864373.png?ver=v1','large':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/large/1009_155_1462864373.png?ver=v1'},'defaultLocale':'en'}]},{'productUnitId':'3385','quantity':0,'sku':'SMPLSKU45','price':'0.00','discountedPrice':'0.00','discount':0,'length':'1.25','width':'1.25','height':'1.25','weight':'1.25','attributes':[{'id':'1610','name':'size','value':'XL'},{'id':'1611','name':'color','value':'blue'}],'images':[{'raw':'1009_155_1462864373.png','imageLocation':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/1009_155_1462864373.png?ver=v1','isPrimary':true,'isDeleted':false,'sizes':{'thumbnail':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/thumbnail/1009_155_1462864373.png?ver=v1','small':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/small/1009_155_1462864373.png?ver=v1','medium':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/medium/1009_155_1462864373.png?ver=v1','large':'http://buyer.yilinker-online.dev/assets/images/uploads/products/2200/large/1009_155_1462864373.png?ver=v1'},'defaultLocale':'en'}]}],'productVariants':[{'id':'1610','name':'size','values':[{'id':'2687','value':'L'},{'id':'2691','value':'XL'}]},{'id':'1611','name':'color','values':[{'id':'2688-2692','value':'blue'}]}]},'target':{'productId':'2200','name':'','shortDescription':'','description':'','youtubeVideoUrl':null,'productConditionId':1,'productConditionName':'New','productCategoryId':2,'productCategoryName':'Animals&PetSupplies','shippingCategoryId':1,'shippingCategoryName':'相机-相机点辅助器，架子','brandId':1,'status':7,'hasCombination':true,'brandName':'Kev','productGroups':['Test','Hey'],'productImages':[{'raw':'1009_155_1462864373.png','imageLocation':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/1009_155_1462864373.png?ver=v1','sizes':{'thumbnail':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/thumbnail/1009_155_1462864373.png?ver=v1','small':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/small/1009_155_1462864373.png?ver=v1','medium':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/medium/1009_155_1462864373.png?ver=v1','large':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/large/1009_155_1462864373.png?ver=v1'},'isSelected':false}],'productUnits':[{'productUnitId':'3383','quantity':0,'sku':'SMPLSKU46','price':'0','discountedPrice':'0','discount':0,'length':'1.25','width':'1.25','height':'1.25','weight':'1.25','attributes':[{'id':'1610','name':'','value':''},{'id':'1611','name':'','value':''}],'images':{'1009_155_1462864373.png':{'raw':'1009_155_1462864373.png','imageLocation':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/1009_155_1462864373.png?ver=v1','sizes':{'thumbnail':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/thumbnail/1009_155_1462864373.png?ver=v1','small':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/small/1009_155_1462864373.png?ver=v1','medium':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/medium/1009_155_1462864373.png?ver=v1','large':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/large/1009_155_1462864373.png?ver=v1'},'isSelected':false}}},{'productUnitId':'3385','quantity':0,'sku':'SMPLSKU45','price':'0','discountedPrice':'0','discount':0,'length':'1.25','width':'1.25','height':'1.25','weight':'1.25','attributes':[{'id':'1610','name':'','value':''},{'id':'1611','name':'','value':''}],'images':{'1009_155_1462864373.png':{'raw':'1009_155_1462864373.png','imageLocation':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/1009_155_1462864373.png?ver=v1','sizes':{'thumbnail':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/thumbnail/1009_155_1462864373.png?ver=v1','small':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/small/1009_155_1462864373.png?ver=v1','medium':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/medium/1009_155_1462864373.png?ver=v1','large':'http: //buyer.yilinker-online.dev/assets/images/uploads/products/2200/large/1009_155_1462864373.png?ver=v1'},'isSelected':false}}}],'productVariants':[{'id':'1610','name':'','values':[{'id':'2687','value':''},{'id':'2691','value':''}]},{'id':'1611','name':'','values':[{'id':'2688-2692','value':''}]}]}}}
                    ",
     *         }
     *     },
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"="productId"},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function getTranslationDetailsAction(Request $request)
    {
        $productId = $request->get("productId", null);

        $targetLocale = $request->getLocale();

        $translationService = $this->get("yilinker_core.translatable.listener");

        $em = $this->get("doctrine.orm.entity_manager");
        $authenticatedUser = $this->getUser();

        $product = $em->getRepository("YilinkerCoreBundle:Product")
                      ->findOneBy(array(
                        "productId" => $productId,
                        "user" => $authenticatedUser
                    ));

        $productUploadService = $this->get("yilinker_merchant.service.product_uploader");

        if(!$product){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Product not found",
                "data" => new stdClass()
            ), 404);
        }

        $defaultLocale = $product->getDefaultLocale();
        $product->setLocale($defaultLocale);

        $translationService = $this->get("yilinker_core.translatable.listener");
        $countryLocale = $translationService->getCountry();

        $translatedProductDetail = $productUploadService->constructUploadDetails($product, $targetLocale, true, $countryLocale);

        $translationService->setDefaultLocale($defaultLocale);
        $product->setLocale($defaultLocale);
        $em->refresh($product);
        $defaultProductDetail = $productUploadService->constructUploadDetails($product, $defaultLocale, true);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product Details",
            "data" => array(
                "default" => $defaultProductDetail,
                "target" => $translatedProductDetail
            )
        ), 200);
    }

    /**
     * Get Product Translation details
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @ApiDoc(
     *     section="Product Translation",
     *     statusCodes={
     *         200="Product Object",
     *         400 = "Error"
     *     },
     *     parameters={
     *         {"name"="name", "dataType"="string", "required"=true, "description"="Product name"},
     *         {"name"="shortDescription", "dataType"="string", "required"=true, "description"="Product short description"},
     *         {"name"="description", "dataType"="string", "required"=true, "description"="Description"},
     *         {"name"="productImages", "dataType"="string", "required"=true, "description"=" NOTE : only pass selected images, empty JSON array if none [
                   {'name':'test_image_1.jpg', 'isPrimary':false},
                   {'name':'test_image_2.jpg', 'isPrimary':false},
                   {'name':'test_image_3.jpg', 'isPrimary':true}
               ]"},
     *         {"name"="productVariants", "dataType"="string", "required"=true, "description"="NOTE: Empty JSON array if there are no combinations [
                    {
                        'id': '1610',
                        'name': 'size',
                        'values': [
                                {
                                    'id': '2687',
                                    'value': 'L'
                                },
                                {
                                    'id': '2691',
                                    'value': 'XL'
                                }
                            ]
                        },
                        {
                            'id': '1611',
                            'name': 'color',
                            'values': [
                                {
                                    'id': '2688-2692',
                                    'value': 'blue'
                                }
                            ]
                        }
                    ]
                "},
     *     },
     *     views = {"product", "default", "v3"}
     * )
     */
    public function translateProductAction(Request $request)
    {
        $productId = $request->get("productId", null);

        $em = $this->get("doctrine.orm.entity_manager");
        $authenticatedUser = $this->getUser();

        $product = $em->getRepository("YilinkerCoreBundle:Product")
                      ->findOneBy(array(
                        "productId" => $productId,
                        "user" => $authenticatedUser
                    ));

        if(!$product){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Product not found",
                "data" => new stdClass()
            ), 404);
        }

        $productUploadService = $this->get("yilinker_merchant.service.product_uploader");

        $defaultLocale = $product->getDefaultLocale();
        $translationService = $this->get("yilinker_core.translatable.listener");

        $translationService->setDefaultLocale($defaultLocale);
        $product->setLocale($defaultLocale);
        $em->refresh($product);

        $countryLocale = $translationService->getCountry();
        $defaultProductDetail = $productUploadService->constructUploadDetails($product, $defaultLocale, true, $countryLocale);

        $product->setLocale($request->getLocale());
        $em->refresh($product);

        $postData = array(
            "name" => $request->get("name", null),
            "shortDescription" => $request->get("shortDescription", null),
            "description" => $request->get("description", null),
            "productImages" => $request->get("productImages", null),
            "productVariants" => $request->get("productVariants", null)
        );

        $form = $this->transactForm(
                    "api_v3_product_translate",
                    $product,
                    $postData,
                    array(
                        "csrf_protection" => false,
                        "isCreate" => false,
                        "product" => $product,
                        "defaultValue" => $defaultProductDetail
                    )
        );

        if($form->isValid()){

            $translatedProduct = $form->getData();
            $productImages = $form->get("productImages")->getData();
            $productVariants = $form->get("productVariants")->getData();

            try{

                $em->beginTransaction();

                $translatedProduct = $productUploadService->apiTranslateProduct(
                        $translatedProduct,
                        json_decode($productImages, true),
                        json_decode($productVariants, true),
                        $request->getLocale()
                    );

                $em->persist($translatedProduct);
                $em->flush();

                $imageUploader = $this->get("yilinker_core.service.image_uploader");
                $imageUploader->uploadProductImages($translatedProduct);
                $em->commit();
            }
            catch(\Exception $e){

                if($em->getConnection()->isTransactionActive()){
                    $em->rollback();
                }

                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Something went wrong",
                    "data" => new stdClass()
                ), 400);
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Product details",
                "data" => $productUploadService->constructUploadDetails($translatedProduct, $request->getLocale(), true, $countryLocale)
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, true)
            )
        ), 400);
    }

    private function preProcessProductDetails($product)
    {
        if (!$product) {
            return $product;
        }

        $assetHelper = $this->get('templating.helper.assets');
        $product['image'] = $assetHelper->getUrl($product['image'], 'product');

        foreach ($product['images'] as &$image) {
            $image['imageLocation'] = $assetHelper->getUrl($image['imageLocation'], 'product');
        }

        return $product;
    }
}
