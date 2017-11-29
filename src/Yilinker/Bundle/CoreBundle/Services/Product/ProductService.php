<?php

namespace Yilinker\Bundle\CoreBundle\Services\Product;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;

class ProductService
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    private $container;
    private $trans;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(
        EntityManager $entityManager,
        AssetsHelper $assetsHelper
    ){
        $this->em = $entityManager;
        $this->assetsHelper = $assetsHelper;
    }

    public function setContainer($container)
    {
        $this->container = $container;
        $this->trans = $this->container->get('yilinker_core.translatable.listener');
    }

    /**
     * Return viewable product statuses
     *
     * @return int[]
     */
    public function viewableSellerProductStatuses()
    {
        return array(
            Product::FOR_COMPLETION,
            Product::ACTIVE,
            Product::INACTIVE,
            Product::DRAFT,
            Product::DELETE,
            Product::FOR_REVIEW,
            Product::REJECT,
        );
    }

    /**
     * Return viewable product statuses
     *
     * @return int[]
     */
    public function viewableAffiliateProductStatuses()
    {
        return array(
            Product::ACTIVE,
            Product::INACTIVE,
        );
    }

    /**
     * Return product statuses for update FROM
     *
     * @return int[]
     */
    public function allowedUserUpdateStatusesFrom()
    {
        return array(
            Product::ACTIVE,
            Product::DELETE,
            Product::REJECT,
            Product::INACTIVE,
            Product::DRAFT,
            Product::FOR_REVIEW,
            Product::FOR_COMPLETION
        );
    }

    /**
     * Return affiliate product statuses for update FROM
     *
     * @return int[]
     */
    public function allowedAffiliateUpdateStatusesFrom()
    {
        return array(
            Product::ACTIVE,
            Product::INACTIVE,
        );
    }

    /**
     * Return product statuses for update TO
     *
     * @return int[]
     */
    public function allowedUserUpdateStatusesTo()
    {
        return array(
            Product::FOR_REVIEW,
            Product::DELETE,
            Product::FULL_DELETE,
            Product::INACTIVE,
            Product::ACTIVE,
        );
    }

    /**
     * Return affiliate product statuses for update TO
     *
     * @return int[]
     */
    public function allowedAffiliateUpdateStatusesTo()
    {
        return array(
            Product::INACTIVE,
            Product::ACTIVE,
            Product::FULL_DELETE,
        );
    }

    public static function statusPerProduct()
    {
        return array(
            Product::DRAFT,
            Product::DELETE,
            Product::FULL_DELETE,
            Product::INACTIVE,
            Product::FOR_COMPLETION,
        );
    }

    public function updateProductStatus(User $user, $productIds, $status, $country = "ph")
    {
        $response = array(
            'isSuccessful' => false,
            'data'         => array(),
            'message'      => "",
        );

        if(is_array($productIds) === false){
            $productIds = array($productIds);
        }

        $storeType = $user->getStore()->getStoreType();

        $allowedStatusesTo = $storeType == Store::STORE_TYPE_MERCHANT? $this->allowedUserUpdateStatusesTo() : $this->allowedAffiliateUpdateStatusesTo();
        $allowedStatusesFrom = $storeType == Store::STORE_TYPE_MERCHANT? $this->allowedUserUpdateStatusesFrom() : $this->allowedAffiliateUpdateStatusesFrom();

        if($storeType == Store::STORE_TYPE_RESELLER){
            if($status == Product::DELETE){
                $status = Product::FULL_DELETE;
            }
        }


        if(in_array($status, $allowedStatusesTo) === false){
            $response['message'] = "Update to given status is not allowed";
        }
        else{
            $updatedProductIds = array();
            if(!empty($productIds)){

                $this->em->beginTransaction();

                try{

                    $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
                    $products = $productRepository->loadUserProductsIn($user, $productIds);

                    $country = $this->em
                                    ->getRepository("YilinkerCoreBundle:Country")
                                    ->findOneByCode($country);

                    foreach($products as $product){

                        $productCountry = $product->getProductCountryByCountry($country);
                        $currentStatus = $product->getProductCountryStatus($country);

                        if(in_array($currentStatus, $allowedStatusesFrom) && !is_null($productCountry)){
                            if(
                                $status == Product::ACTIVE &&
                                $product->getInventory(false, false, true) > 0
                            ){

                                if(
                                    $storeType == Store::STORE_TYPE_MERCHANT && (
                                    $productCountry->getStatus() == Product::INACTIVE ||
                                    $productCountry->getStatus() == Product::DELETE
                                )){
                                    $status = Product::FOR_REVIEW;
                                }

                                $this->pushUpdatedProducts($product, $status, $updatedProductIds, $productCountry, $user);
                            }
                            else if($status != Product::ACTIVE){
                                if(
                                    $storeType == Store::STORE_TYPE_RESELLER &&
                                    $status == Product::FULL_DELETE
                                ){
                                    $affiliateProductService = $this->container->get('yilinker_merchant.service.api.affiliate_product');
                                    $manufacturerProductMap = $product->getManufacturerProductMap();

                                    if($manufacturerProductMap){
                                        $manufacturerProductId = $manufacturerProductMap->getManufacturerProduct()
                                                                                        ->getManufacturerProductId();

                                        $existingProductMaps = $affiliateProductService->existingProductMaps(
                                                                    $user,
                                                                    $manufacturerProductId,
                                                                    $country
                                                                );

                                        foreach ($existingProductMaps as $existingProductMap) {
                                            $affiliateProductService->removeAffiliateProduct(
                                                $existingProductMap['productId'],
                                                $country
                                            );
                                        }
                                    }

                                    $this->em->flush();
                                }
                                elseif(
                                    $storeType == Store::STORE_TYPE_MERCHANT &&
                                    $status == Product::DELETE &&
                                    (
                                        $productCountry->getStatus() == Product::DRAFT ||
                                        $productCountry->getStatus() == Product::FOR_REVIEW
                                    )
                                ){
                                    $status = Product::FULL_DELETE;
                                }

                                $this->pushUpdatedProducts($product, $status, $updatedProductIds, $productCountry, $user);
                            }
                            else{
                                if(!array_key_exists("errors", $response['data'])){
                                    $response['data']['errors'] = array();
                                }

                                $response['data']['errors'][] = $product->getName();
                            }
                        }
                    }

                    if($storeType == Store::STORE_TYPE_MERCHANT){
                        $response['message'] = "No updatable product found";
                    }
                    else{
                        $response['message'] = "The selected products is not updatable/out of stock";
                    }

                    if(array_key_exists("errors", $response['data']) && count($response['data']['errors']) > 0){
                        throw new Exception("Not updatable products found", 1);
                    }
                    if(count($updatedProductIds) > 0){
                        $response['isSuccessful'] = true;
                        $response['data']['products'] = $updatedProductIds;
                        $response['message'] = count($updatedProductIds)." products successfully updated";
                        $this->em->flush();
                    }

                    $this->em->commit();
                }
                catch(Exception $e){
                    $this->em->rollback();
                    $response['message'] = $e->getMessage();
                }
            }
        }

        return $response;
    }

    public function getProductListCount(
        User $user,
        $keyword,
        $status,
        $hydrate = false,
        $orderBy = array(),
        $country = "ph"
    ){
        return $this->em->getRepository("YilinkerCoreBundle:Product")
                    ->searchUserProducts(
                        $user,
                        $keyword,
                        $status,
                        $orderBy,
                        null,
                        null,
                        $country,
                        true
        );
    }

    public function getProductList(
        User $user,
        $keyword,
        $status,
        $hydrate = false,
        $orderBy = array(),
        $page = 1,
        $perPage = 30,
        $country = "ph"
    ){
        $productsCollection = array();

        $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
        $offset = 0;
        if($page > 0){
            $offset = ($page - 1) * $perPage;
        }

        $products = $productRepository->searchUserProducts(
            $user,
            $keyword,
            $status,
            $orderBy,
            $offset,
            $perPage,
            $country,
            false
        );

        if(!empty($products)){
            $productsCollection = $this->constructProduct($products,$country,$hydrate);
        }

        return $productsCollection;
    }

    /**
     * v2
     */
    public function getProductListV2(
        User $user,
        $keyword,
        $status,
        $hydrate = false,
        $page = 1,
        $perPage = 30,
        $country = "ph",
        $locale = 'en'
    ){
        $productsCollection = array();

        $priceFrom = 0.00;
        $priceTo = null;
        $sortType = 'BYDATE';
        $sortDirection = 'DESC';
        $sellerId = $user;

        $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
        $userProducts = $productRepository->searchProductsBy(compact(
            "sellerId",
            "priceFrom",
            "priceTo",
            "status",
            "sortType",
            "sortDirection",
            "page",
            "perPage",
            "keyword"
        ));

        $products = $userProducts['products'];

        if(!empty($products)){
            foreach($products as $product){
                $product->setLocale($locale);
                $imageLocation = $product->getPrimaryImageLocation();
                $category = $product->getProductCategory();

                $productImageUrl = "";
                if($imageLocation != ""){
                    $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
                }

                if(!$hydrate){
                    array_push($productsCollection, array(
                        "id" => $product->getProductId(),
                        "name" => $product->getName(),
                        "category" => !is_null($category)? $category->getName() : null,
                        "image" => $productImageUrl,
                        "dateCreated" => $product->getDateCreated(),
                        "dateLastModified" => $product->getDateLastModified(),
                        "slug" => $product->getSlug(),
                        "status" => $product->getProductCountryStatus($country),
                        "condition" => !is_null($product->getCondition())? $product->getCondition()->getName() : null,
                    ));
                }
                else{
                    $productsCollection[(int)$product->getProductId()] = array(
                        "id" => $product->getProductId(),
                        "name" => $product->getName(),
                        "category" => !is_null($category)? $category->getName() : null,
                        "image" => $productImageUrl,
                        "dateCreated" => $product->getDateCreated(),
                        "dateLastModified" => $product->getDateLastModified(),
                        "slug" => $product->getSlug(),
                        "status" => $product->getStatus(),
                        "condition" => !is_null($product->getCondition())? $product->getCondition()->getName() : null,
                    );
                }
            }
        }

        return $productsCollection;
    }

    public function getAllUserProducts(User $sellerId, $status = null, $dateLastModifiedFrom = null, $dateLastModifiedTo = null, $categoryId = null, $page = 1, $perPage = 20, $locale = 'en')
    {
        $productsCollection = array(
            "count" => 0,
            "products" => array()
        );

        $storeType = $sellerId->getStore()->getStoreType();

        if($storeType == Store::STORE_TYPE_RESELLER){
            $excludedStatus = array(Product::FULL_DELETE, Product::DELETE);
        }
        else{
            $excludedStatus = is_null($status)? Product::FULL_DELETE : null;
        }

        $priceFrom = 0.00;
        $priceTo = null;
        $sortType = 'BYDATE';
        $sortDirection = 'DESC';

        $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
        $country = $this->trans->getCountry(true);
        $countryId = is_object($country) ? $country->getCountryId(): $country;
        $userProducts = $productRepository->searchProductsBy(compact(
            "sellerId",
            "priceFrom",
            "priceTo",
            "status",
            "dateLastModifiedFrom",
            "dateLastModifiedTo",
            "categoryId",
            "sortType",
            "sortDirection",
            "page",
            "perPage",
            "excludedStatus",
            "countryId"
        ));

        $productsCollection["count"] = $userProducts["totalResultCount"];

        foreach ($userProducts["products"] as $product) {
            $product->setLocale($locale);
            $imageLocation = $product->getPrimaryImageLocation();
            $category = $product->getProductCategory();

            $productImageUrl = "";
            if($imageLocation != ""){
                $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
            }

            $quantity = 0;
            $productUnits = $product->getUnits();

            foreach ($productUnits as $productUnit) {
                $quantity += $productUnit->getQuantity();
            }

            $defaultUnit = $product->getDefaultUnit();
            $attributeValues = array();

            if($defaultUnit){
                $attributes = $defaultUnit->getProductAttributeValues();
                foreach ($attributes as $attribute) {
                    array_push($attributeValues, $attribute->getValue());
                }
            }

            array_push($productsCollection["products"], array(
                    "object" => $product,
                    "productId" => $product->getProductId(),
                    "name" => $product->getName(),
                    "category" => !is_null($category)? $category->getName() : null,
                    "image" => $productImageUrl,
                    "quantity" => $quantity,
                    'originalPrice' => $defaultUnit ? $defaultUnit->getPrice() : null,
                    'discountedPrice' => $defaultUnit ? $defaultUnit->getAppliedDiscountPrice() : null,
                    "attributes" => $attributeValues,
                    "dateCreated" => $product->getDateCreated(),
                    "dateLastModified" => $product->getDateLastModified(),
                    "slug" => $product->getSlug(),
                    "status" => $product->getStatus(),
                    "condition" => !is_null($product->getCondition())? $product->getCondition()->getName() : null,
            ));
        }

        return $productsCollection;
    }

    /**
     * Also reloads the product attribute values by retrieves the details. Explicitly reloads the values
     * because the postLoad translation event on ProductAttributeValue happens late for some reason.
     *
     * @param Product $product
     * @return mixed
     */
    public function reloadUnitDetailsByCountry(Product $product)
    {
        $productUnits = array();
        foreach ($product->getUnits() as $unit) {
            $unitData = $unit->toArray(false, true);
            $attributeValues = $unit->getProductAttributeValues();
            $combination = array();
            foreach ($attributeValues as $attributeValue) {
                $this->em->refresh($attributeValue);
                $combination[] = $attributeValue->toArray();
            }

            $unitData['attributes'] = $combination;
            $productUnits[] = $unitData;
        }

        return $productUnits;
    }


    public function affilateUpdateStatus($params = array())
    {
        $updatedProductIds = array();
        $userProducts = $this->em
                                    ->getRepository('YilinkerCoreBundle:InhouseProductUser')
                                    ->findBy(array(
                                        'user' => $params['authenticatedUser'],
                                        'product' => $params['productIds']
                                    ));
        
        foreach ($userProducts as $userProduct) {
            
            if (!is_null($userProduct) && !is_null($params['status'])) {
                if ($params['status'] == Product::DELETE || $params['status'] == PROduct::FULL_DELETE) {
                    $this->em->remove($userProduct);
                }
                else {
                    $userProduct->setStatus($params['status']);
                    $userProduct->setDateLastModified(Carbon::now());
                }

                array_push($updatedProductIds, $userProduct->getProduct()->getProductId());
            }
        }

        $this->em->flush();

        $response['isSuccessful'] = true;
        $response['data']['products'] = $updatedProductIds;
        $response['message'] = count($updatedProductIds)." products successfully updated";

        return $response;
    }

    private function pushUpdatedProducts(
        Product $product,
        $status,
        &$updatedProductIds,
        $productCountry,
        $user = null
    ){
        if ($user && $user->isAffiliate(false)) {
            $inhouseProductUser = $product->getInhouseProductUserByUser($user);
            $inhouseProductUser->setStatus($status);
        }
        else {
            $productCountry->setStatus($status);
        }
        $updatedProductIds[] = $product->getProductId();
    }

    public function getProductDetail($product)
    {
        $productUnit = $product->getFeaturedUnit();
        $isBulkDiscount = $productUnit->getIsBulkDiscount();

        return array(
            "productId" => $product->getProductId(),
            "productUnitId" => $productUnit->getProductUnitId(),
            "name" => $product->getName(),
            "quantity" => $product->getInventory(true, false),
            "category" => $product->getProductCategory()->getName(),
            "description" => $product->getDescription(),
            "shortDescription" => $product->getShortDescription(),
            "discountPercentage" => $productUnit->getDiscount($isBulkDiscount? true : false),
            "sku" => $productUnit->getSku(),
            "slug" => $product->getSlug(),
            "price" => $productUnit->getAppliedBaseDiscountPrice(),
            "discountedPrice" => $productUnit->getAppliedDiscountPrice(),
            "raw" => $product->getPrimaryImageLocation(true),
            "image" => $this->assetsHelper->getUrl($productUnit->getPrimaryImageLocation(), "product"),
            "thumbnail" => $this->assetsHelper->getUrl($productUnit->getPrimaryImageLocationBySize("thumbnail"), "product"),
            "small" => $this->assetsHelper->getUrl($productUnit->getPrimaryImageLocationBySize("small"), "product"),
            "medium" => $this->assetsHelper->getUrl($productUnit->getPrimaryImageLocationBySize("medium"), "product"),
            "large" => $this->assetsHelper->getUrl($productUnit->getPrimaryImageLocationBySize("large"), "product"),
            "rating" => $product->getReviewRating(),
            "reviewCount" => $product->getVisibleReviewsCount(),
            "isCod" => $product->getIsCod(),
            "isFreeShipping" => $product->getIsFreeShipping(),
            "isBulkDiscount" => $isBulkDiscount
        );
    }

    public function constructProduct($products,$country,$hydrate = false)
    {
        $productsCollection = array();

        foreach($products as $product){
            $imageLocation = $product->getPrimaryImageLocation();
            $category = $product->getProductCategory();

            $productImageUrl = "";
            if($imageLocation != ""){
                $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
            }

            if(!$hydrate){
                array_push($productsCollection, array(
                    "id" => $product->getProductId(),
                    "name" => $product->getName(),
                    "category" => !is_null($category)? $category->getName() : null,
                    "image" => $productImageUrl,
                    "dateCreated" => $product->getDateCreated(),
                    "dateLastModified" => $product->getDateLastModified(),
                    "slug" => $product->getSlug(),
                    "status" => $product->getProductCountryStatus($country),
                    "condition" => !is_null($product->getCondition())? $product->getCondition()->getName() : null,
                ));
            }
            else{
                $productsCollection[(int)$product->getProductId()] = array(
                    "id" => $product->getProductId(),
                    "name" => $product->getName(),
                    "category" => !is_null($category)? $category->getName() : null,
                    "image" => $productImageUrl,
                    "dateCreated" => $product->getDateCreated(),
                    "dateLastModified" => $product->getDateLastModified(),
                    "slug" => $product->getSlug(),
                    "status" => $product->getStatus(),
                    "condition" => !is_null($product->getCondition())? $product->getCondition()->getName() : null,
                );
            }
        }



        return $productsCollection;
    }
}
