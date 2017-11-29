<?php

namespace Yilinker\Bundle\CoreBundle\Services\Cms;

use stdClass;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Routing\Router;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Helpers\ArrayHelper;
use Yilinker\Bundle\CoreBundle\Model\Discount;
use Yilinker\Bundle\CoreBundle\Services\Cms\ResourceService;
use Yilinker\Bundle\CoreBundle\Repository\PromoInstanceRepository;

/**
 * Class PagesService
 * @package Yilinker\Bundle\FrontendBundle\Services\Pages
 */
class PagesService
{
    const FLASH_SALE_LIMIT = 3;

    const WEB_HOME_FLASH_SALE_LIMIT = 4;

    const TARGET_TYPE_PRODUCT = "product";

    const TARGET_TYPE_PRODUCT_LIST = "product_list";

    const TARGET_TYPE_SELLER = "seller";

    const TARGET_TYPE_SELLER_LIST = "seller_list";

    const IMAGE_DIRECTORY = '/images/uploads/cms/daily_login/';

    const STORE_IMAGE_DIRECTORY = '/images/uploads/cms/store/';

    const PRODUCT_LIST_IMAGE_DIRECTORY = '/images/uploads/cms/product_list/';

    const HOME_IMAGE_DIRECTORY = 'images/uploads/cms/home-web/';

    const HOME_PRODUCTS_LIMIT = 60;

    const XML_PRODUCT_LIST_NODE = "productList";

    const XML_STORE_LIST_NODE = "storeList";

    const XML_AD_SPACE_NODE = "iceBreaker";

    const NODE_ID_PRODUCT_LIST = 99;

    const NODE_ID_ITEMS_YOU_MAY_LIKE = 100;

    const PRODUCT_NODE_HOT_ITEMS = "hotItems";

    const PRODUCT_NODE_NEW_ITEMS = "newItems";

    const PRODUCT_NODE_TODAYS_PROMO = "todaysPromo";

    const STORE_NODE_HOT_STORE = "hotStore";

    const STORE_NODE_NEW_STORE = "newStore";

    const STORE_LIST_NODE_ID_ONE = 'storeListOne';

    const STORE_LIST_NODE_ID_TWO = 'storeListTwo';

    const MAX_ALLOWABLE_STORE_PRODUCTS = 6;

    const HOMEPAGE_WEB_V6_CONTENT_BANNERS = 1;

    const HOMEPAGE_WEB_V6_CONTENT_FLASH_SALE = 2;

    const HOMEPAGE_WEB_V6_CONTENT_CATEGORIES_SECTION = 3;

    const HOMEPAGE_WEB_V6_CONTENT_CUSTOM = 4;

    const HOMEPAGE_WEB_V6_CONTENT_CATEGORY_SECTION = 5;

    const HOMEPAGE_WEB_V6_CONTENT_PRODUCT_IMAGES = 6;

    const HOMEPAGE_MOBILE_V2_CONTENT_FLASH_SALE = 4;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var XMLParser
     */
    private $xmlParser;

    /**
     * @var ResourceService
     */
    private $xmlResourceGetter;

    /**
     * @var Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    private $promoManager;

    /**
     * @var ServiceContainer
     */
    private $container;

    private $productService;

    private $translationService;

    /**
     *  local variables
     */
    private $categoryIds = array();

    /**
     * PagesService constructor.
     * @param Router $router
     * @param AssetsHelper $assetsHelper
     * @param XMLParserService $xmlParser
     * @param ResourceService $xmlResourceGetter
     * @param EntityManager $entityManager
     */
    public function __construct(
        Router $router,
        AssetsHelper $assetsHelper,
        XMLParserService $xmlParser,
        ResourceService $xmlResourceGetter,
        EntityManager $entityManager,
        $promoManager,
        $productService,
        $translationService
    )
    {
        $this->router = $router;
        $this->assetsHelper = $assetsHelper;
        $this->xmlParser = $xmlParser;
        $this->xmlResourceGetter = $xmlResourceGetter;
        $this->entityManager = $entityManager;
        $this->promoManager = $promoManager;
        $this->productService = $productService;
        $this->translationService = $translationService;
    }

    public function constructHomepageContent($xmlObj, $products, $productUnits, $productCategories, $userSpecialties, $users, $version)
    {
        $response = array();
        if($version === "v1"){
            $action = "getApiv1Content";
        }
        else{
            $action = "getApiv2Content";
        }

        if(method_exists($this, $action)){
            $response = $this->$action($xmlObj, $products, $productUnits, $productCategories, $userSpecialties, $users);
        }

        return $response;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function getApiv1Content($xmlObj, $products, $productUnits, $productCategories, $userSpecialties, $users)
    {
        $content = array(
            "featured" => array(
                "mainBanner" => array(
                    "image" => $this->assetsHelper->getUrl((string)$xmlObj->featured->mainBanner->image, 'cms'),
                    "target" => (string)$xmlObj->featured->mainBanner->target,
                    "targetType" => (string)$xmlObj->featured->mainBanner["targetType"]
                ),
                "subBanners" => $this->getImages($xmlObj->featured->subBanners->banner),
                "promoHeader" => (string)$xmlObj->featured->promos->header,
                "promos" => $this->getProducts($xmlObj->featured->promos->product, $products, $productUnits),
                "popularCategories" => $this->getUploadedCategories($xmlObj->featured->popularCategories->popularCategory),
                "trendingItems" => $this->getUploadedCategories($xmlObj->featured->trendingItems->trendingItem),
                "itemsYouMayLike" => $this->getProducts($xmlObj->featured->itemsYouMayLike->product, $products, $productUnits)
            ),
            "hotItems" => array(
                "topBanners" => $this->getImages($xmlObj->hotItems->topBanners->banner),
                "topPicks" => $this->getProducts($xmlObj->hotItems->topPicks->product, $products, $productUnits),
                "categories" => $this->getCategoriesWithMultiImages($xmlObj->hotItems->categories->category, $productCategories),
                "bottomBanners" => $this->getImages($xmlObj->hotItems->bottomBanners->banner)
            ),
            "newItems" => array(
                "shopByCategories" => $this->getCategoriesWithSingleImage($xmlObj->newItems->shopByCategories->category, $productCategories),
                "shopByNewRelease" => $this->getProducts($xmlObj->newItems->shopByNewRelease->product, $products, $productUnits)
            ),
            "sellers" => array(
                "newSellers" => $this->getUserInfo($xmlObj->sellers->newSellers->user, $users),
                "topSellers" => $this->getUserProducts($xmlObj->sellers->topSellers->user, $users, $products, $productUnits, $userSpecialties)
            )
        );

        $response = array(
            "isSuccessful" => true,
            "message" => "Homepage successfully fetched.",
            "data" => $content
        );

        return $response;
    }

    public function getApiv2Content($xmlObj, $products, $productUnits, $productCategories, $userSpecialties, $users)
    {
        $content = array();

        foreach($xmlObj->layout as $layout){

            $isViewMoreAvailable = filter_var($layout->isViewMoreAvailable, FILTER_VALIDATE_BOOLEAN);
            $layoutData = array(
                "layoutId" => (int)$layout["layoutId"],
                "sectionTitle" => (string)$layout->sectionTitle,
                "isViewMoreAvailable" => $isViewMoreAvailable,
                "viewMoreTarget" => $isViewMoreAvailable? $this->getTarget((array)$layout->viewMoreTarget) : new stdClass(),
            );

            $layoutContents= $this->layoutHandler(
                                $layout,
                                $products,
                                $productUnits,
                                $productCategories,
                                $userSpecialties,
                                $users
                            );

            $layoutData = array_merge($layoutData, $layoutContents);

            array_push($content, $layoutData);
        }

        $response = array(
            "isSuccessful" => true,
            "message" => "Homepage successfully fetched.",
            "data" => $content
        );

        return $response;
    }

    private function layoutHandler(
        $layout,
        $products,
        $productUnits,
        $productCategories,
        $userSpecialties,
        $users
    ){
        $layoutData = array();
        switch ($layout["layoutId"]) {
            case 4:
                $data = $this->constructHomeFlashSale($layout);

                if(
                    array_key_exists("remainingTime", $data) &&
                    array_key_exists("currentPromoProducts", $data)
                ){
                    $layoutData["remainingTime"] = $data["remainingTime"];
                    $layoutData["data"] = $data["currentPromoProducts"];
                }

                if(is_null($layoutData["remainingTime"])){
                    $layoutData["data"] = array();
                }
                break;
            case 5:
            case 7:
            case 10:
            case 14:
                $layoutData["data"] = $layout->data? $this->getTimedProductsData($layout, $products, $productUnits) : array();
                break;
            case 9:
                $layoutData["data"] = $layout->data? $this->getMobileCategories($layout, $productCategories) : array();
                break;
            case 8:
                $layoutData["data"] = $layout->data?
                                        $this->getStoreDetails(
                                            $layout,
                                            $users,
                                            $userSpecialties,
                                            $products,
                                            $productUnits
                                        ) : array();
                break;
            case 11:
                $layoutData["data"] = $layout->data ? $this->getOverseasCountry() : array();
                break;
            case 12:
                $layoutContents = array();
                if($layout->data && $layout->data->layout){
                    foreach($layout->data->layout as $subLayout){

                        $isViewMoreAvailable = filter_var($subLayout->isViewMoreAvailable, FILTER_VALIDATE_BOOLEAN);
                        $mainContent = array(
                            "layoutId" => (int)$subLayout["layoutId"],
                            "sectionTitle" => (string)$subLayout->sectionTitle,
                            "isViewMoreAvailable" => $isViewMoreAvailable,
                            "viewMoreTarget" => $isViewMoreAvailable? $this->getTarget((array)$subLayout->viewMoreTarget) : new stdClass(),
                        );

                        $content = $this->layoutHandler(
                            $subLayout,
                            $products,
                            $productUnits,
                            $productCategories,
                            $userSpecialties,
                            $users
                        );

                        array_push($layoutContents, array_merge($mainContent, $content));
                    }
                }

                $layoutData["data"] = $layoutContents;

                break;
            default:
                $layoutData["data"] = $layout->data? $this->getMobileImages($layout) : array();
                break;
        }

        return $layoutData;
    }

    public function constructHomeFlashSale($layout = null, $limit = 3)
    {
        $isCurrent = false;
        $isUpcoming = true;
        $promoInstanceRepository = $this->entityManager->getRepository("YilinkerCoreBundle:PromoInstance");
        $productUnitRepository = $this->entityManager->getRepository("YilinkerCoreBundle:ProductUnit");

        $remainingTime = null;
        $currentPromoProducts = null;

        $currentPromoInstance = $promoInstanceRepository->getCurrentPromoInstance(
                                    PromoType::FLASH_SALE,
                                    true,
                                    Carbon::now(),
                                    Carbon::now(),
                                    "pi.dateStart",
                                    "ASC",
                                    true
                                );

        if(is_null($currentPromoInstance)){

            $nextPromoInstance = $promoInstanceRepository->getPromoInstanceByType(
                                        PromoType::FLASH_SALE,
                                        true,
                                        Carbon::now(),
                                        Carbon::now()->endOfDay(),
                                        "pi.dateStart",
                                        "ASC",
                                        true
                                    );

            $firstPromoInstance = $promoInstanceRepository->getPromoInstanceByType(
                                        PromoType::FLASH_SALE,
                                        true,
                                        Carbon::now()->startOfDay(),
                                        Carbon::now()->endOfDay(),
                                        "pi.dateStart",
                                        "ASC",
                                        true
                                    );

            $currentPromoInstance = $nextPromoInstance === $firstPromoInstance? $firstPromoInstance : null;
        }
        else{
            $isCurrent = true;
            $isUpcoming = false;
        }

        if($currentPromoInstance){

            $currentPromoProducts = $productUnitRepository->getPromoProducts($currentPromoInstance->getPromoInstanceId(), $limit);

            if(!empty($currentPromoProducts)){

                foreach ($currentPromoProducts as $index => $currentPromoProduct){

                    $unit = $productUnitRepository->find($currentPromoProduct["productUnitId"]);
                    $currentPromoProducts[$index]["price"] = $unit->getPrice();

                    $currentPromoProducts[$index]["image"] = $this->assetsHelper->getUrl($unit->getPrimaryImageLocation(), "product");
                    $currentPromoProducts[$index]["thumbnail"] = $this->assetsHelper->getUrl($unit->getPrimaryImageLocationBySize("thumbnail"), "product");
                    $currentPromoProducts[$index]["small"] = $this->assetsHelper->getUrl($unit->getPrimaryImageLocationBySize("small"), "product");
                    $currentPromoProducts[$index]["medium"] = $this->assetsHelper->getUrl($unit->getPrimaryImageLocationBySize("medium"), "product");
                    $currentPromoProducts[$index]["large"] = $this->assetsHelper->getUrl($unit->getPrimaryImageLocationBySize("large"), "product");

                    $currentPromoProducts[$index]["retailPrice"] = number_format($unit->getPrice(), 2);
                    $currentPromoProducts[$index]["discountedPrice"] = number_format($unit->getAppliedDiscountPrice(), 2);
                    $currentPromoProducts[$index]["quantity"] = $unit->toArray()['quantity'];

                    if(!is_null($layout)){
                        $currentPromoProducts[$index]["target"] = array(
                            "targetUrl" => (string)$layout->target[$index]->targetUrl,
                            "targetType" => (string)$layout->target[$index]->targetType,
                            "isAuthenticated" => (string)$layout->target[$index]->isAuthenticated
                        );
                    }

                    $discountPercent = $this->getDiscountPercentage($unit->getAppliedDiscountPrice(), $unit->getPrice());
                    $discountPercent = floatval(number_format(floor($discountPercent*100)/100, 2));
                    $currentPromoProducts[$index]["discountPercentage"] = $discountPercent;

                    $param = isset($firstPromoInstance)? "dateStart" : "dateEnd";

                    $remainingTime = Carbon::createFromFormat("Y-m-d H:i:s", $currentPromoProduct[$param])->diffInSeconds(Carbon::now());
                }
            }
        }

        return array(
            "isCurrent" => $isCurrent,
            "isUpcoming" => $isUpcoming,
            "currentPromoProducts" => $currentPromoProducts,
            "remainingTime" => $remainingTime,
            "dateStart" => !is_null($remainingTime)? $currentPromoInstance->getDateStart() : null,
            "dateEnd" => !is_null($remainingTime)? $currentPromoInstance->getDateEnd() : null
        );
    }

    /**
     * Get Daily Login Data
     *
     * @param $xmlObj
     * @return array
     */
    public function getDailyLoginData ($xmlObj)
    {
        $content = array();
        foreach ($xmlObj->dailyLogin as $data) {
            $images = array();

            foreach ((array) $data->images as $image) {
                $ctr = 0;
                foreach ((array) $image as $imageAttr) {
                    $images[$ctr++] = array(
                        'fileDir' => self::IMAGE_DIRECTORY . (string) $imageAttr->fileName,
                        'src' => (string) $imageAttr->src,
                    );
                }

            }

            $content = array (
                'successMessage' => (string) $data->successMessage,
                'errorMessage' => (string) $data->errorMessage,
                'images'  => $images
            );
        }

        return $content;
    }

    /**
     * Get Stores in xml
     *
     * @param $xmlObj
     * @param $node
     * @param $limit
     * @param $page
     * @return array
     */
    public function getStoreDataWithPages($xmlObj, $node = 'hotStore', $limit, $page)
    {
        $content = array (
            'mainBanner' => '',
            'stores'     => array()
        );
        $userRepository = $this->entityManager->getRepository('YilinkerCoreBundle:User');
        $storeRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Store');

        if ( sizeof((array) $xmlObj->storeList) > 0 && isset($xmlObj->storeList->$node)) {

            foreach ($xmlObj->storeList->$node as $data) {
                $stores = array();
                $loopCount = 0;
                $pageCount = 0;
                $arrayCount = 0;

                foreach ($data->store as $key => $store) {
                    $imageArray = array();
                    $userEntity = $userRepository->find((string) $store->userId);
                    $dir = self::STORE_IMAGE_DIRECTORY . $userEntity->getUserId() . DIRECTORY_SEPARATOR;
                    $storeEntity = null;
                    $storeLogo = (string) $store->logo;

                    if ($loopCount === $limit) {
                        $pageCount++;
                        $loopCount = 0;
                        if ($pageCount - 1 === $page) {
                            $pageCount--;
                            break;
                        }
                    }

                    if ($userEntity instanceof User) {
                        $storeEntity = $storeRepository->findOneByUser($userEntity);
                    }

                    foreach ($store->images as $imageDetails) {

                        foreach ($imageDetails->image as $image) {
                            $imageArray[] = array (
                                'url' => (string) $image->src,
                                'dir' => $this->assetsHelper->getUrl($dir . (string) $image->fileName)
                            );
                        }

                    }

                    $products = $userEntity->getMostPopularUploads(3);
                    $productDetails = array();

                    if (sizeof($products) > 0) {

                        foreach ($products as $product) {
                            $productImageEntity = $product->getPrimaryImage();
                            $productDetails[] = array (
                                'slug' => $product->getSlug(),
                                'productId' => $product->getProductId(),
                                'dir' => $productImageEntity !== false ? $productImageEntity->getFullImagePath() : null
                            );
                        }

                    }

                    $stores[$pageCount]['page'] = $pageCount;
                    $stores[$pageCount]['data'][$arrayCount] = array (
                        'userFullName'   => $userEntity->getFullName(),
                        'storeSlug'      => !is_null($storeEntity) ? $storeEntity->getStoreSlug() : null,
                        'storeName'      => !is_null($storeEntity) ? $storeEntity->getStoreName() : null,
                        'storeId'        => !is_null($storeEntity) ? $storeEntity->getStoreId() : null,
                        'logo'           => $storeLogo === '' ? $userEntity->getUserCard()['image'] : $this->assetsHelper->getUrl($dir . $storeLogo),
                        'specialty'      => (string) $store->specialty,
                        'userId'         => $userEntity->getUserId(),
                        'images'         => $imageArray,
                        'productDetails' => $productDetails
                    );

                    $loopCount++;
                    $arrayCount++;
                }

                if ($page > $pageCount && $page !== 1) {
                    $stores = array();
                }
                else {
                    $stores = $stores[$pageCount];
                }

                $content = array (
                    'mainBanner' => (string) $data->mainBanner !== '' ? $this->assetsHelper->getUrl(self::STORE_IMAGE_DIRECTORY . (string) $data->mainBanner) : '',
                    'stores'     => $stores
                );

            }

        }

        return $content;
    }

    /**
     * Get Product list by node
     * MARKED FOR REFACTOR: Readability needs improvement
     *
     * @param $node
     * @param $xmlObj
     * @param $limit
     * @param $page
     * @return array
     */
    public function getProductsByNode ($node, $xmlObj, $limit, $page = 1)
    {
        $content = array();
        $productRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Product');
        $node = $xmlObj->list->$node;
        $page = (int) $page;
        $page = $page >= 1 ? $page : 1;
        $result = array(
            'currentPage' => $page,
        );

        if (sizeof($node) > 0) {
            $node = (array) $node;
            $productSection = (array) $node['products'];
            $categoryIds = isset($productSection['categoryId']) ? $productSection['categoryId'] : null;
            $sellerIds = isset($productSection['sellerId']) ? $productSection['sellerId'] : null;

            // if category exist within the node
            if ($categoryIds) {
                $result = $this->getProductNodeByFilter(array(
                    'page'          => $page,
                    'categoryIds'   => $categoryIds,
                    'sellerId'      => null,
                    'limit'         => $limit,
                    'result'        => $result,
                ));
            // if seller exist within the node
            } else if ($sellerIds) {
                $result = $this->getProductNodeByFilter(array(
                    'page'          => $page,
                    'categoryIds'   => null,
                    'sellerId'      => $sellerIds,
                    'limit'         => $limit,
                    'result'        => $result,
                ));
            } else {

                $productIds = !is_array($productSection['productId']) ? array($productSection['productId']) : $productSection['productId'];
                $result['totalPages'] = (int) ceil(count($productIds)/$limit);
                $offset = ($page - 1) * $limit;
                $slicedProductIds = array_slice($productIds, $offset, $limit);

                foreach($slicedProductIds as $productId){
                    $product = $productRepository->findOneBy(array('productId' =>$productId));
                    $productDetails = null;
                    $firstUnit = null;
                    if ($product instanceof Product) {
                        $productDetails = $product->getDetails();
                        $firstUnit = $product->getDefaultUnit() ? $product->getDefaultUnit()->toArray(): null;
                        $result['data'][] = array(
                            'product'   => $productDetails,
                            'firstUnit' => $firstUnit,
                        );
                    }
                }    
            }

            $content = array (
                'mainBanner'  => isset($node['mainBanner']) && (string) $node['mainBanner'] !== '' ?
                                 $this->assetsHelper->getUrl(self::PRODUCT_LIST_IMAGE_DIRECTORY . (string) $node['mainBanner']) : '',
                'products'    => $result,
            );

        }

        return $content;
    }

    private function getProductNodeByFilter($params=array())
    {
        extract($params);
        $productSearchResult = $this->container->get('yilinker_core.service.search.product')
                                    ->searchProductsWithElastic(
                                        null,null,null,$categoryIds,$sellerId,null,null,
                                        null,null,array(),$page,$limit,
                                        true,true,array(),null,null,null,null,
                                        null,null,null
                                    );

        foreach($productSearchResult['products'] as $product) {
             $productDetails = $product->getDetails();
                $firstUnit = $product->getDefaultUnit() ? $product->getDefaultUnit()->toArray(): null;
                $result['data'][] = array(
                    'product'   => $productDetails,
                    'firstUnit' => $firstUnit,
                );
        }

        $result['totalPages'] = $productSearchResult['totalPage'];

        return $result;
    }


    private function __addPages (array $arrayOfData, $limit)
    {
        $arrayWithPage = array();

        if (sizeof($arrayOfData) > 0) {
            $pageCount = 0;
            $count = 0;

            foreach ($arrayOfData as $key => $data) {

                if ($pageCount === $limit) {
                    $pageCount = 0;
                    $count++;
                }

                $arrayWithPage[$count]['data'][$key] = $data;
                $arrayWithPage[$count]['page'] = $count;

                $pageCount++;
            }

        }

        return $arrayWithPage;
    }

    private function getMobileCategories($xmlObj, $productCategories)
    {
        $categories = array();

        foreach($xmlObj->data as $data){
            $image = (string)$data->image;
            $productCategory = array_key_exists((int)$data->category, $productCategories)? $productCategories[(int)$data->category] : null;

            if(!is_null($productCategory)){
                $categoryDetails = array(
                    "name" => $productCategory? $productCategory->getName() : null,
                    "image" => $this->assetsHelper->getUrl("default.jpg"),
                    "target" => $this->getTarget((array)$data->target)
                );


                if($image != "" && !is_null($image)){
                    $categoryDetails["image"] = $this->assetsHelper->getUrl($image, "cms");
                }
                else if($productCategory && $productCategory->getImage()){
                    $categoryDetails["image"] = $this->assetsHelper->getUrl($productCategory->getImage(), "category");
                }

                array_push($categories, $categoryDetails);
            }
        }

        return $categories;
    }

    private function getStoreDetails($xmlObj, $users, $userSpecialties, $products, $productUnits)
    {
        $stores = array();

        foreach ($xmlObj->data as $data) {
            $userId = (int)$data->user["id"];

            if(array_key_exists($userId, $users)){
                $user = $users[$userId];
                $store = $user->getStore();

                if($store){
                    $primaryImage = $user->getPrimaryImage();

                    $defaultLocation = null;
                    if(!$primaryImage){
                        $defaultLocation = $this->assetsHelper->getUrl(UserImage::DEFAULT_SELLER_AVATAR_FILE);
                    }

                    array_push($stores, array(
                        "name" => $user->getStore()->getStoreName(),
                        "specialty" => array_key_exists($userId, $userSpecialties)? $userSpecialties[$userId]["name"] : null,
                        "image" => !$primaryImage? $defaultLocation : $this->assetsHelper->getUrl($primaryImage->getImageLocation(), "user"),
                        "thumbnail" => !$primaryImage? $defaultLocation : $this->assetsHelper->getUrl($primaryImage->getImageLocationBySize("thumbnail"), "user"),
                        "small" => !$primaryImage? $defaultLocation : $this->assetsHelper->getUrl($primaryImage->getImageLocationBySize("small"), "user"),
                        "medium" => !$primaryImage? $defaultLocation : $this->assetsHelper->getUrl($primaryImage->getImageLocationBySize("medium"), "user"),
                        "large" => !$primaryImage? $defaultLocation : $this->assetsHelper->getUrl($primaryImage->getImageLocationBySize("large"), "user"),
                        "data" => array_key_exists("topProducts", (array)$data->user)? $this->getTimedProductsData($data->user->topProducts, $products, $productUnits) : array(),
                        "target" => $this->getTarget((array)$data->user->target)
                    ));
                }
            }
        }

        return $stores;
    }

    private function getTimedProducts($xmlObj, $products, $productUnits)
    {
        $timedProducts = array();

        foreach ($xmlObj->row as $row) {
            $timedProducts["remainingTime"] = (string)$row->remainingTime;
            $timedProducts["data"] = $this->getTimedProductsData($row, $products, $productUnits);
        }

        return $timedProducts;
    }

    private function getTimedProductsData($xmlObj, $products, $productUnits)
    {
        $productCollection = array();

        foreach ($xmlObj->data as $productData) {
            $productUnit = null;

            if(!is_null($productData->product["unit"]) && array_key_exists((int)$productData->product["unit"], $productUnits)){
                $productUnitId = (int)$productData->product["unit"];

                $productUnit = $productUnits[$productUnitId];
                $product = $productUnit->getProduct();

                $productUnitImages = $productUnit->getProductUnitImages();
                $productUnit = $productUnit->toArray();
            }
            else{
                $productId = (int)$productData->product;
                if(array_key_exists($productId, $products)){
                    $product = $products[$productId];
                    $productUnit = $product->getDefaultUnit();

                    $productUnitImages = $productUnit->getProductUnitImages();

                    if($productUnit){
                        $productUnit = $productUnit->toArray();
                    }
                }
            }

            if($productUnit){
                $productDetails = $this->getProductData($productUnit, $product, $productUnitImages);

                $target = array(
                    "target" => $this->getTarget((array)$productData->target)
                );

                $data = array_merge($productDetails, $target);
                array_push($productCollection, $data);
            }
        }

        return $productCollection;
    }

    private function getProductData($productUnit, $product, $productUnitImages)
    {
        $promoTypeId = $productUnit["promoTypeId"];
        $promoInstance = array_key_exists(0, $productUnit["promoInstance"])? $productUnit["promoInstance"][0] : null;
        $maxPercentage = !is_null($promoInstance)? $promoInstance["maxPercentage"] : null;
        $minimumPercentage = !is_null($promoInstance)? $promoInstance["minPercentage"] : null;

        $imageLocations = $this->getProductUnitImage($product->getPrimaryImage(), $productUnitImages);

        extract($imageLocations);

        switch($promoTypeId){
            case PromoType::PER_HOUR:
                if($maxPercentage < 100){
                    $discountPercent = floatval($maxPercentage);
                }
                else{
                    $discountPercent = floatval($minimumPercentage);
                }
                break;
            case PromoType::BULK:
                $discountPercent = floatval($maxPercentage);
                break;
            default:
                $discountPercent = $this->getDiscountPercentage(
                                    $productUnit["discountedPrice"],
                                    $productUnit["price"]
                                );
                break;
        }

        $discountPercent = floatval(number_format(floor($discountPercent*100)/100, 0));
        return array(
            "productId" => $product->getProductId(),
            "productUnitId" => $productUnit["productUnitId"],
            "name" => $product->getName(),
            "originalPrice" => $productUnit["price"],
            "discountedPrice" => $productUnit["discountedPrice"],
            "discountPercentage" => (string) $discountPercent,
            "currency" => "P",
            "image" => $imageLocation,
            "thumbnail" => $thumbnailLocation,
            "small" => $smallLocation,
            "medium" => $mediumLocation,
            "large" => $largeLocation
        );
    }

    private function getMobileImages($xmlObj)
    {
        $value = array();
        foreach ($xmlObj->data as $data) {
            array_push($value, array(
                "image" => $this->assetsHelper->getUrl((string)$data->image, "cms"),
                "target" => $this->getTarget((array)$data->target)
            ));
        }

        return $value;
    }

    private function getTarget($target)
    {
        $result = new StdClass();

        if(
            array_key_exists("targetUrl", $target) &&
            $target["targetUrl"] != "" &&
            array_key_exists("targetType", $target) &&
            $target["targetType"] != "" &&
            array_key_exists("isAuthenticated", $target) &&
            $target["isAuthenticated"] != ""
        ){
            $result = array();
            $result["targetUrl"] = (string)$target["targetUrl"];
            $result["targetType"] = (string)$target["targetType"];
            $result["isAuthenticated"] = filter_var((string)$target["isAuthenticated"], FILTER_VALIDATE_BOOLEAN);
        }

        return $result;
    }

    /**
     * Get User info
     *
     * @param $xmlObj
     * @param $fetchedUsers
     * @return array
     */
    private function getUserInfo($xmlObj, $fetchedUsers)
    {
        $users = array();

        foreach($xmlObj as $user){
            $userId = (int)$user;

            if(array_key_exists($userId, $fetchedUsers)){
                $userProfileImage = $fetchedUsers[$userId]->getPrimaryImage();

                if(!$userProfileImage){
                    $profileImageUrl = "";
                }
                else{
                    $profileImageUrl = $userProfileImage->getImageLocation();
                }

                if($fetchedUsers[$userId]->getStore()){
                    $userDetails = array(
                        "userId" => $userId,
                        "name" => $fetchedUsers[$userId]->getStore()->getStoreName(),
                        "image" => $this->assetsHelper->getUrl($profileImageUrl, 'user'),
                        "target" => "/api/v1/seller/getDetails"
                    );

                    array_push($users, $userDetails);
                }
            }
        }

        return $users;
    }

    /**
     * Get products of the user
     *
     * @param $xmlObj
     * @param $fetchedUsers
     * @param $productUnits
     * @param $userSpecialties
     * @return array
     */
    private function getUserProducts($xmlObj, $fetchedUsers, $products, $productUnits, $userSpecialties)
    {
        $users = array();

        foreach($xmlObj as $user){
            $userId = (int)$user["id"];

            if(array_key_exists($userId, $fetchedUsers)){
                $userProfileImage = $fetchedUsers[$userId]->getPrimaryImage();

                if(!$userProfileImage){
                    $profileImageUrl = "";
                }
                else{
                    $profileImageUrl = $userProfileImage->getImageLocation();
                }

                if(array_key_exists($userId, $userSpecialties)){
                    $specialty = $userSpecialties[$userId]["name"];
                }
                else{
                    $specialty = "";
                }

                if($fetchedUsers[$userId]->getStore()){
                    $userDetails = array(
                        "userId" => $userId,
                        "sellerName" => $fetchedUsers[$userId]->getStore()->getStoreName(),
                        "image" => $this->assetsHelper->getUrl($profileImageUrl, 'cms'),
                        "specialty" => $specialty,
                        "target" => $this->router->generate("api_product_detail"),
                        "products" => $this->getProducts($user->products->product, $products, $productUnits)
                    );

                    array_push($users, $userDetails);
                }
            }
        }

        return $users;
    }

    /**
     * Get products
     *
     * @param $xmlObj
     * @param $fetchedProductUnits
     * @return array
     */
    private function getProductUnits($xmlObj, $fetchedProductUnits)
    {
        // TODO : fix targets

        $products = array();

        foreach($xmlObj as $productUnit){
            $productUnitId = (int)$productUnit;

            if(array_key_exists($productUnitId, $fetchedProductUnits)) {
                $unit = $fetchedProductUnits[$productUnitId];
                $product = $unit->getProduct();
                $productId = $product->getProductId();
                $unitDetails = $unit->toArray();

                $productDetails = array(
                    "productId" => $product->getProductId(),
                    "productUnitId" => $unitDetails["productUnitId"],
                    "productName" => $product->getName(),
                    "originalPrice" => number_format($unit->getAppliedBaseDiscountPrice(), 2),
                    "discountedPrice" => number_format($unit->getAppliedDiscountPrice(), 2),
                    "discountedPercentage" => $unitDetails["discount"],
                    "promoTypeId" => $unitDetails["promoTypeId"],
                    "promoTypeName" => $unitDetails["promoTypeName"],
                    "image" => $this->assetsHelper->getUrl($unitDetails["primaryImage"], "product"),
                    "target" => $this->router->generate("api_product_detail"),
                    "inWishlist" => $unit->inWishlist(),
                    "quantity" => $unit->getQuantity()
                );

                array_push($products, $productDetails);
            }
        }

        return $products;
    }

    /**
     * Get products
     *
     * @param $xmlObj
     * @param $fetchedProducts
     * @return array
     */
    private function getProducts($xmlObj, $fetchedProducts, $fetchedProductUnits)
    {
        // TODO : fix targets

        $products = array();

        foreach($xmlObj as $product){
            $productId = (int)$product;
            $productAttributes = $product->attributes();
            $unitId = !is_null($productAttributes["unit"])? (int)$productAttributes["unit"] : null;

            if(array_key_exists($productId, $fetchedProducts)) {
                if(array_key_exists($unitId, $fetchedProductUnits)){
                    $defaultUnit = $fetchedProductUnits[$unitId]->toArray();
                }
                else{
                    $defaultUnit = $fetchedProducts[$productId]->getDefaultUnit()->toArray();
                }

                if($defaultUnit){

                    $productPrimaryImage = $fetchedProducts[$productId]->getPrimaryImage();

                    if($productPrimaryImage){
                        $productPrimaryImage = $productPrimaryImage->getImageLocation();
                    }
                    else{
                        $productPrimaryImage = "";
                    }

                    $productDetails = array(
                        "productId" => (string)$productId,
                        "productUnitId" => $defaultUnit["productUnitId"],
                        "productName" => $fetchedProducts[$productId]->getName(),
                        "originalPrice" => number_format($defaultUnit["price"], 2),
                        "discountedPrice" => number_format($defaultUnit["discountedPrice"], 2),
                        "discountedPercentage" => $defaultUnit["discount"],
                        "image" => $this->assetsHelper->getUrl($productPrimaryImage, 'product'),
                        "target" => $this->router->generate("api_product_detail")
                    );

                    array_push($products, $productDetails);
                }
            }
        }

        return $products;
    }

    /**
     * Get categories with only one image
     *
     * @param $xmlObj
     * @param $productCategories
     * @return array
     */
    private function getCategoriesWithSingleImage($xmlObj, $productCategories)
    {
        $categories = array();

        foreach($xmlObj as $category){

            $categoryId = (int)$category["id"];

            if(array_key_exists($categoryId, $productCategories)) {
                $categoryDetails = array(
                    "categoryId" => $categoryId,
                    "categoryName" => $productCategories[$categoryId]->getName(),
                    "image" => $this->assetsHelper->getUrl((string)$category->image, 'cms'),
                    "target" => (string)$category->target
                );

                array_push($categories, $categoryDetails);
            }
        }

        return $categories;
    }

    /**
     * Get categories with multiple images
     *
     * @param $xmlObj
     * @param $productCategories
     * @return array
     */
    private function getCategoriesWithMultiImages($xmlObj, $productCategories)
    {
        $categories = array();

        foreach($xmlObj as $category){

            $categoryId = (int)$category["id"];

            if(array_key_exists($categoryId, $productCategories)) {
                $categoryDetails = array(
                    "categoryId" => $categoryId,
                    "categoryName" => $productCategories[$categoryId]->getName(),
                    "layoutId" => (string)$category["layoutId"],
                    "viewMoreTarget" => (string)$category->viewMoreTarget,
                    "images" => $this->getImages($category->categoryImage)
                );

                array_push($categories, $categoryDetails);
            }
        }

        return $categories;
    }

    /**
     * @param $xmlObj
     * @return array
     */
    private function getImages($xmlObj)
    {
        $featuredBanners = array();

        foreach($xmlObj as $subBanner){
            $banner = array(
                "image" => $this->assetsHelper->getUrl((string)$subBanner->image, 'cms'),
                "target" => (string)$subBanner->target,
                "targetType" => (string)$subBanner["targetType"]
            );

            array_push($featuredBanners, $banner);
        }

        return $featuredBanners;
    }

    /**
     * @param $xmlObj
     * @return array
     */
    private function getUploadedCategories($xmlObj)
    {
        $uploadedCategories = array();

        foreach($xmlObj as $uploadedCategory){

            $category = array(
                "categoryImage" => $this->assetsHelper->getUrl((string)$uploadedCategory->image, 'cms'),
                "target" => (string)$uploadedCategory->target
            );

            array_push($uploadedCategories, $category);
        }

        return $uploadedCategories;
    }


    /**
     * Retrieve main catgeory content
     *
     * @param string $slug
     * @return mixed
     */
    public function getMainCategoryContent($slug)
    {
        $categoryContent = array();
        $xmlObject = $this->xmlResourceGetter->fetchXML("category", "v1", "web");
        $categoryNode = $this->xmlParser->getNodeWithId($xmlObject, 'category', $slug);
        if($categoryNode !== false){
            $categoryData = json_decode(json_encode($categoryNode), 1);
            $categoryContent['header'] = $categoryData['header'];
            if(isset($categoryContent['header']['banner']['image'])){
                $categoryContent['header']['banner'] = array($categoryContent['header']['banner']);
            }

            if(isset($categoryContent['header']['brand'])){
                $brandRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Brand');
                $categoryContent['header']['brands'] = $brandRepository->getBrandsByIds($categoryContent['header']['brand']);
            }

            $productRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Product');
            $categoryRepository = $this->entityManager->getRepository('YilinkerCoreBundle:ProductCategory');
            foreach($categoryData['subcategories'] as $subCategory){
                $subCategory = $subCategory[0];
                $productSlugs = $subCategory['product']['slug'];
                $products = $productRepository->getProductsBySlug($productSlugs);
                if(count($products) > 0){
                    $category = reset($products)->getProductCategory();

                    $categoryContent['subcategories'][] = array(
                        'category' => $category,
                        'products' => $products,
                        'banner' => array(
                            'template' => $subCategory['banner']['template'],
                            'image' => isset($subCategory['banner']['image']['target']) ?
                                       array($subCategory['banner']['image']) :
                                       $subCategory['banner']['image'],
                        )
                    );
                }
            }

            $categoryContent['footer']['title'] = $categoryData['footer']['title'];
            $categoryContent['footer']['target'] = $categoryData['footer']['target'];
            foreach($categoryData['footer']['footerSection'] as $footer){
                $footerSubcategorySlugs = $footer['subCategories']['slug'];
                $parentSlug = $footer['mainCategory'];
                $footerSubcategories = $categoryRepository->getCategoriesBySlug($footerSubcategorySlugs, $parentSlug);
                $categoryContent['footer']['subsection'] = array();
                if(count($footerSubcategories) > 0){
                    $footerCategory = reset($footerSubcategories);
                    $categoryContent['footer']['subsection'][] = array(
                        'category' => $footerCategory,
                        'subcategories' => $footerSubcategories,
                        'image' => $footer['image'],
                    );
                }
            }
        }


        return $categoryContent;
    }

    /**
     * Get product units
     *
     * @param $productUnits
     * @return array
     */
    public function constructProductUnits($productUnits)
    {
        $units = array();

        foreach($productUnits as $productUnit){
            if($productUnit->getQuantity() > 0){
                $productUnitId = $productUnit->getProductUnitId();

                $product = $productUnit->getProduct();
                $productId = $product->getProductId();

                $discountPercent = 0;
                $basePrice = $this->getUnitPrice($productUnit, 'base');
                $discountedPrice = $this->getUnitPrice($productUnit, 'discount');

                //check if there is a promo
                $promoMaps = $productUnit->getProductPromoMaps();
                $promoDetails = $this->constructPromoDiscounts($promoMaps, $discountedPrice, $basePrice, $discountPercent);
                $imageLocations = $this->getProductUnitImage($product->getPrimaryImage(), $productUnit->getProductUnitImages());

                extract($promoDetails);
                extract($imageLocations);

                $productUnitDetails = array(
                    "productId" => $productId,
                    "productUnitId" => $productUnitId,
                    "productName" => $product->getName(),
                    "productCategory" => is_null($product->getProductCategory())? "":$product->getProductCategory()->getName(),
                    "categorySlug" => is_null($product->getProductCategory())? "":$product->getProductCategory()->getSlug(),
                    "productReviewRating" => $product->getReviewRating(),
                    "slug" => $product->getSlug(),
                    "originalPrice" => number_format($basePrice, 2),
                    "discountedPrice" => number_format($discountedPrice, 2),
                    "discountedPercentage" => $discountPercent,
                    "promoTypeId" => $promoTypeId,
                    "promoTypeName" => $promoTypeName,
                    "promoEndDate" => $promoEndDate,
                    "image" => $imageLocation,
                    "thumbnail" => $thumbnailLocation,
                    "small" => $smallLocation,
                    "medium" => $mediumLocation,
                    "large" => $largeLocation,
                    "inWishlist" => $productUnit->inWishlist(),
                    "quantity" => $productUnit->getQuantity()
                );

                $units[$productUnitId] = $productUnitDetails;
            }
        }

        return $units;
    }

    /**
     * Get products
     *
     * @param $products
     * @return array
     */
    public function constructProducts($products)
    {
        $productCollection = array();

        foreach($products as $product){
            $productUnit = $product->getDefaultUnit();
            if($productUnit){
                $productUnitId = $productUnit->getProductUnitId();
                $productId = $product->getProductId();

                $discountPercent = 0;
                $basePrice = $this->getUnitPrice($productUnit, 'base');
                $discountedPrice = $this->getUnitPrice($productUnit, 'discount');

                //check if there is a promo
                $promoMaps = $productUnit->getProductPromoMaps();
                $promoDetails = $this->constructPromoDiscounts($promoMaps, $discountedPrice, $basePrice, $discountPercent);
                $imageLocations = $this->getProductUnitImage($product->getPrimaryImage(), $productUnit->getProductUnitImages());

                extract($promoDetails);
                extract($imageLocations);

                $productUnitDetails = array(
                    "productId" => $productId,
                    "productUnitId" => $productUnitId,
                    "productName" => $product->getName(),
                    "productCategory" => is_null($product->getProductCategory())? "":$product->getProductCategory()->getName(),
                    "categorySlug" => is_null($product->getProductCategory())? "":$product->getProductCategory()->getSlug(),
                    "productReviewRating" => $product->getReviewRating(),
                    "slug" => $product->getSlug(),
                    "originalPrice" => number_format($basePrice, 2),
                    "discountedPrice" => number_format($discountedPrice, 2),
                    "discountedPercentage" => $discountPercent,
                    "promoTypeId" => $promoTypeId,
                    "promoTypeName" => $promoTypeName,
                    "promoEndDate" => $promoEndDate,
                    "image" => $imageLocation,
                    "thumbnail" => $thumbnailLocation,
                    "small" => $smallLocation,
                    "medium" => $mediumLocation,
                    "large" => $largeLocation,
                    "inWishlist" => $productUnit->inWishlist(),
                    "quantity" => $productUnit->getQuantity()
                );

                $productCollection[$productId] = $productUnitDetails;
            }
        }

        return $productCollection;
    }

    /**
     * Get product unit price
     *
     * @param ProductUnit $productUnit
     * @param string $type
     * @return mixed
     */
    private function getUnitPrice(ProductUnit $productUnit, $type = 'base')
    {
        if(!is_null($productUnit->getAppliedDiscountPrice())){
            return (double)($type == 'base'? $productUnit->getAppliedBaseDiscountPrice() : $productUnit->getAppliedDiscountPrice());
        }
        else{
            //if not null make sure
            return (double)($type == 'base'? $productUnit->getPrice() : $productUnit->getDiscountedPrice());
        }
    }

    /**
     * bind promo discount to unit
     *
     * @param $promoMaps
     * @param $discountedPrice
     * @param $basePrice
     * @param $promoTypeId
     * @param $promoTypeName
     * @param $discountPercent
     */
    private function constructPromoDiscounts($promoMaps, &$discountedPrice, &$basePrice, &$discountPercent)
    {
        $promoTypeId = null;
        $promoTypeName = null;
        $promoEndDate = null;

        foreach($promoMaps as $promoMap){

            $promoInstance = $promoMap->getPromoInstance();
            $promoType = $promoInstance->getPromoType();
            $dateNow = Carbon::now();
            $dateStart = Carbon::instance($promoInstance->getDateStart());
            $dateEnd = Carbon::instance($promoInstance->getDateEnd());

            if($dateNow->between($dateStart, $dateEnd) && $promoInstance->getIsEnabled()){

                $promoTypeId = $promoType->getPromoTypeId();
                $promoTypeName = $promoType->getName();
                $promoEndDate = $dateEnd->timestamp;
                $maxPercentage = $promoMap->getMaximumPercentage();
                $minimumPercentage = $promoMap->getMinimumPercentage();
                $percentPerHour = $promoMap->getPercentPerHour();

                switch($promoTypeId){
                    case PromoType::PER_HOUR:
                        if($maxPercentage < 100){
                            $discountPercent = number_format(floatval($maxPercentage), 2, '.', '');
                        }
                        else{
                            $discountPercent = number_format(floatval($minimumPercentage), 2, '.', '');
                        }
                        break;
                    case PromoType::BULK:
                        $discountPercent = number_format(floatval($maxPercentage), 2, '.', '');
                        break;
                    default:
                        $discountPercent = number_format($this->getDiscountPercentage($discountedPrice, $basePrice), 2, '.', '');
                        break;
                }
            }
        }

        if(count($promoMaps) == 0){
            $discountPercent = number_format($this->getDiscountPercentage($discountedPrice, $basePrice), 2, '.', '');
        }

        return compact("promoTypeId", "promoTypeName", "promoEndDate");
    }

    private function getProductUnitImage($productPrimaryImage, $productUnitImages)
    {
        if($productUnitImages->count() > 0){
            $unitImages = $productUnitImages->getValues();
            $hasMatch = false;
            foreach($unitImages as $unitImage){
                if($unitImage->getProductImage() == $productPrimaryImage){
                    $hasMatch = true;
                    break;
                }
            }
            if(!$hasMatch){
                if(isset($unitImages[0])){
                    $productPrimaryImage = $unitImages[0]->getProductImage();
                }
            }
        }

        $imageLocation = "";
        $thumbnailLocation = "";
        $smallLocation = "";
        $mediumLocation = "";
        $largeLocation = "";

        if($productPrimaryImage){
            $imageLocation = $this->assetsHelper->getUrl($productPrimaryImage->getImageLocation(), 'product');
            $thumbnailLocation = $this->assetsHelper->getUrl($productPrimaryImage->getImageLocationBySize("thumbnail"), 'product');
            $smallLocation = $this->assetsHelper->getUrl($productPrimaryImage->getImageLocationBySize("small"), 'product');
            $mediumLocation = $this->assetsHelper->getUrl($productPrimaryImage->getImageLocationBySize("medium"), 'product');
            $largeLocation = $this->assetsHelper->getUrl($productPrimaryImage->getImageLocationBySize("large"), 'product');
        }

        return compact("imageLocation", "thumbnailLocation", "smallLocation", "mediumLocation", "largeLocation");
    }

    /**
     * @param $discountedPrice
     * @param $basePrice
     * @return int
     */
    private function getDiscountPercentage($discountedPrice, $basePrice)
    {
        if(!is_null($discountedPrice)){

            $percentage = "0.0000";
            if(bccomp($basePrice, "0.0000") !== 0){
                $percentage = bcsub("100.00", bcmul(bcdiv($discountedPrice, $basePrice, 8), "100.00", 8), 8);
            }

            return $percentage;
        }

        return 0;
    }

    /**
     * Get home page v2 content
     *
     * @param $xmlObj
     * @return array
     */
    public function getHomePageV2Content ($xmlObj)
    {
        $xmlToArray = json_decode(json_encode($xmlObj), true);

        $categoryRepository = $this->entityManager->getRepository('YilinkerCoreBundle:ProductCategory');

        /**
         * Start of main
         */
        $main = isset($xmlToArray['main']) ? $xmlToArray['main'] : null;
        $mainData = array();

        if (!is_null($main)) {
            $mainBanner = isset($main['mainBanner']) ? $main['mainBanner'] : null;
            $banners = isset($mainBanner['banner']) && sizeof($mainBanner['banner']) > 0 ? $mainBanner['banner'] : array();

            foreach ($banners as &$banner) {
                $image = isset($banner['image']) && $banner['image'] !== '' ? $banner['image'] : null;
                $banner['alt'] = isset($banner['alt']) && !is_array($banner['alt']) &&
                                 $banner['alt'] != '' ? $banner['alt'] : '';
                $banner['target'] = isset($banner['target']) && !is_array($banner['target']) &&
                                 $banner['target'] != '' ? $banner['target'] : '';

                if (!is_null($image)) {
                    $banner['image'] =  $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY .$image);
                }

            }

            $topRight = isset($main['topRight']) ? $main['topRight'] : null;
            $topRightBanners = isset($topRight['banner']) && sizeof($topRight['banner']) > 0 ? $topRight['banner'] : array();

            foreach ($topRightBanners as &$banner) {
                $image = isset($banner['image']) && $banner['image'] !== '' ? $banner['image'] : null;
                $banner['alt'] = isset($banner['alt']) && !is_array($banner['alt']) && $banner['alt'] != '' ? $banner['alt'] : '';

                if (!is_null($image)) {
                    $banner['image'] =  $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY .$image);
                }

            }

            $bottomBanner = isset($main['bottomBanner']) ? $main['bottomBanner'] : null;
            $bottomBanners = isset($bottomBanner['banner']) && sizeof($bottomBanner['banner']) > 0 ? $bottomBanner['banner'] : array();

            foreach ($bottomBanners as &$banner) {
                $image = isset($banner['image']) && $banner['image'] !== '' ? $banner['image'] : null;
                $banner['alt'] = isset($banner['alt']) && !is_array($banner['alt']) && $banner['alt'] != '' ? $banner['alt'] : '';

                if (!is_null($image)) {
                    $banner['image'] =  $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY .$image);
                }

            }

            $ads = isset($main['ads']) ? $main['ads'] : null;
            $adBanners = isset($ads['banner']) && sizeof($ads['banner']) > 0 ? $ads['banner'] : null;

            foreach ($adBanners as &$banner) {
                $image = isset($banner['image']) && $banner['image'] !== '' ? $banner['image'] : null;
                $banner['alt'] = isset($banner['alt']) && !is_array($banner['alt']) && $banner['alt'] != '' ? $banner['alt'] : '';

                if (!is_null($image)) {
                    $banner['image'] =  $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY .$image);
                }

            }

            $mainData= array (
                'mainBanner'   => $banners,
                'topRight'     => $topRightBanners,
                'bottomBanner' => $bottomBanners,
                'ads'          => $adBanners
            );
        }
        /**
         * End of main
         */

        /**
         * Start of top brands
         */
        $brandRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Brand');
        $productRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Product');
        $topBrands = isset($xmlToArray['topBrands']) ? $xmlToArray['topBrands'] : null;
        $topBrandsData = array();

        if (!is_null($topBrands)) {
            $brands = isset($topBrands['brands']) ? isset($topBrands['brands']['brandId']) ? array($topBrands['brands']) : $topBrands['brands'] : array();
            $brandIds = array();
            $brandFinder = array();
            foreach ($brands as $brand) {
                $brandIds[] = $brand['brandId'];
                $brandFinder[$brand['brandId']] = isset($brand['products'])
                                                  ? $brand['products'] : array();
            }

            $brandCollection = $brandRepository->findByBrandId($brandIds);

            foreach ($brandCollection as $brandEntity) {
                if ($brandEntity instanceof Brand) {
                    $products = array();
                    $productSlugs = isset($brandFinder[$brandEntity->getBrandId()])
                        && isset($brandFinder[$brandEntity->getBrandId()]['slug'])
                        && sizeof($brandFinder[$brandEntity->getBrandId()]['slug']) > 0
                        ? $brandFinder[$brandEntity->getBrandId()]['slug'] : null;

                    if (!is_null($productSlugs)) {
                        $products = $productRepository->findBy(array(
                            'slug' => $brandFinder[$brandEntity->getBrandId()]['slug'],
                        ));
                    }

                    $productArray = array();
                    foreach ($products as $product) {
                        $productArray[] = $this->formatProductToArray($product);
                    }

                    $topBrandsData[] = array(
                        'brandEntity' => $this->formatBrandToArray($brandEntity),
                        'products'    => $productArray
                    );
                }
            }
        }
        /**
         * End of brands
         */

        /**
         * Start of top categories
         */
        $topCategories = isset($xmlToArray['topCategories']) ? $xmlToArray['topCategories'] : null;
        $topCategoryData = array();

        if (!is_null($topCategories)) {
            $categories = isset($topCategories['category']) ? $topCategories['category'] : array();
            $categoriesData = array();

            if (sizeof($categories) > 0) {
                $categoryIds = array();
                foreach ($categories as $key => $value) {
                    $categoryIds[] = $value['categoryId'];
                }
                $categoryCollection = $categoryRepository->findByProductCategoryId($categoryIds);

                foreach ($categoryCollection as $key => $mainCategoryEntity) {
                    if ($mainCategoryEntity instanceof ProductCategory) {
                        $mainCategoryImage = $mainCategoryEntity->getImage();
                        $mainCategoryArray = array(
                            'id'          => $mainCategoryEntity->getProductCategoryId(),
                            'name'        => $mainCategoryEntity->getName(),
                            'description' => $mainCategoryEntity->getDescription(),
                            'slug'        => $mainCategoryEntity->getSlug(),
                            'image'       => $mainCategoryImage
                        );

                        $subCategoriesXml = !is_array($categories[$key]['subCategory']['categoryId'])
                                            ? array($categories[$key]['subCategory']['categoryId'])
                                            : $categories[$key]['subCategory']['categoryId'];

                        $subCategories = $categoryRepository->findBy(array(
                            'parent' => $mainCategoryEntity,
                            'productCategoryId' => $subCategoriesXml,
                        ));

                        $subCategoryArray = array();
                        foreach ($subCategories as $category) {
                            $subCategoryArray[] = $this->formatCategory($category);
                        }

                        $categoriesData[] = array(
                            'mainCategory' => $mainCategoryArray,
                            'subCategory'  => $subCategoryArray
                        );
                    }
                }
            }

            $topCategoryData = array (
                'seeMore'  => $topCategories['seeMore'],
                'category' => $categoriesData
            );
        }
        /**
         * End of top categories
         */

        $homeContent = array (
            'categorySideBar' => $this->getV2HeaderCategories ($xmlObj),
            'main'            => $mainData,
            'topBrands'       => $topBrandsData,
            'topCategories'   => $topCategoryData,
            'itemsYouMayLike' => $this->getItemsYouMayLike ($xmlObj, self::HOME_PRODUCTS_LIMIT, 0)
        );

        return $homeContent;
    }

    /**
     * Get Products by limit and page
     *
     * @param $xmlObj
     * @param $limit
     * @param $page
     * @return array
     */
    public function getItemsYouMayLike ($xmlObj, $limit, $page)
    {
        $content = array();
        $productRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Product');
        $node = (array) $xmlObj->itemYouMayLike;

        if (isset($node['slug']) && sizeof($node['slug']) > 0) {

            $productArray = array();
            $loopCount = 0;
            $pageCount = 0;
            $arrayCount = 0;

            if (!is_array($node['slug'])) {
                $node['slug'] = array($node['slug']);
            }

            $productCollection = $productRepository->getProductsBySlug($node['slug']);

            foreach ($productCollection as $productEntity) {
                if($productEntity === null){
                    continue;
                }

                if ($loopCount === $limit) {
                    $pageCount++;
                    $loopCount = 0;

                    if ($pageCount - 1 === $page) {
                        $pageCount--;
                        break;
                    }
                }

                $productCategoryEntity = $productEntity->getProductCategory();
                $categoryDetails = array(
                    'id'    => $productCategoryEntity->getProductCategoryId(),
                    'name'  => $productCategoryEntity->getName(),
                    'slug'  => $productCategoryEntity->getSlug()
                );

                $productArray[$pageCount]['page'] = $pageCount;
                $productArray[$pageCount]['data'][$arrayCount]['product'] = $productEntity->getDetails();
                $productArray[$pageCount]['data'][$arrayCount]['product']['category'] = $categoryDetails;
                $productArray[$pageCount]['data'][$arrayCount]['product']['rating'] = $productEntity->getReviewRating();
                $productArray[$pageCount]['data'][$arrayCount]['firstUnit'] = $productEntity->getDefaultUnit() ? $productEntity->getDefaultUnit()->toArray(): null;
                $loopCount++;
                $arrayCount++;
            }

            if ($page > $pageCount && $page !== 1) {
                $productArray = array();
            }
            else {
                $productArray = isset($productArray[$pageCount]) ? $productArray[$pageCount] : array();
            }

            $content = array (
                'seeMore'  => $node['seeMore'],
                'products' => $productArray
            );
        }

        return $content;
    }

    public function getV2HeaderCategories ($xmlObj)
    {
        $xmlToArray = json_decode(json_encode($xmlObj), true);

        /**
         * Start of categorySideBar
         */
        $categoryRepository = $this->entityManager->getRepository('YilinkerCoreBundle:ProductCategory');
        $categorySideBar = isset($xmlToArray['categorySideBar']['category']) ? $xmlToArray['categorySideBar']['category'] : null;
        $categorySideBarData = array();

        if (!is_null($categorySideBar)) {

            foreach ($categorySideBar as $category) {
                $adSmall = isset($category['adSmall']) && $category['adSmall'] ? $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY . $category['adSmall']['image']) : null;
                $adBig = isset($category['adBig']) && $category['adBig'] ? $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY . $category['adBig']['image']) : null;
                $categoryEntity = $categoryRepository->getOneProductCategoryById($category['categoryId']);
                $categoryDetail = array();

                if ($categoryEntity instanceof ProductCategory) {
                    $image = $categoryEntity->getImage();
                    $categoryDetail = array (
                        'id'          => $categoryEntity->getProductCategoryId(),
                        'name'        => $categoryEntity->getName(),
                        'description' => $categoryEntity->getDescription(),
                        'slug'        => $categoryEntity->getSlug(),
                        'image'       => $image
                    );
                }

                $category['adSmall']['image'] = $adSmall;
                $category['adBig']['image'] = $adBig;

                if ($adBig === null) {
                    $category['adBig'] = null;
                }
                else {
                    $category['adBig']['alt'] = isset($category['adBig']['alt']) && !is_array($category['adBig']['alt']) &&
                                                $category['adBig']['alt'] != '' ? $category['adBig']['alt'] : '';
                }

                if ($adSmall === null) {
                    $category['adSmall'] = null;
                }
                else {
                    $category['adSmall']['alt'] = isset($category['adSmall']['alt']) && !is_array($category['adSmall']['alt']) &&
                                                  $category['adSmall']['alt'] != '' ? $category['adSmall']['alt'] : '';
                }

                if (count($categoryDetail)) {
                    $categorySideBarData[] = array (
                        'category' => $categoryDetail,
                        'adSmall'  => $category['adSmall'],
                        'adBig'    => $category['adBig'],
                    );
                }
            }

        }
        /**
         * End of categorySideBar
         */

        return $categorySideBarData;
    }

    private function formatProductToArray(Product $product)
    {
        $defaultUnit = $product->getDefaultUnit();
        $primaryImage = $product->getPrimaryImage();

        $arrayData = array(
            'productId' => $product->getProductId(),
            'name' => $product->getName(),
            'slug' => $product->getSlug(),
            'productCategory' => array(
                'name' => $product->getProductCategory()->getName(),
                'slug' => $product->getProductCategory()->getSlug(),
            ),
            'primaryImageLocation' => $product->getPrimaryImageLocation(),
            'defaultUnit' => null,
            'primaryImage' => array(
                'thumbnail' => ''
            ),
            'reviewRating' => $product->getReviewRating(),
        );

        if ($defaultUnit) {
            $defaultUnit = $defaultUnit->toArray();
            $arrayData['defaultUnit'] = array(
                'discount' => $defaultUnit['discount'],
                'price' => $defaultUnit['price'],
                'discountedPrice' => $defaultUnit['discountedPrice'],
                'promoTypeId' => $defaultUnit['promoTypeId'],
                'productUnitId' => $defaultUnit['productUnitId'],
                'quantity' => $defaultUnit['quantity'],
                'inWishlist' => $defaultUnit['inWishlist'],
            );
        }

        if ($primaryImage) {
            $arrayData['primaryImage'] = array(
                'thumbnail' => $primaryImage->getImageLocationBySize('thumbnail'),
            );
        }

        return $arrayData;
    }

    private function formatSellerToArray(Store $store)
    {
        $primaryImage = $store->getUser()->getPrimaryImage();

        $arrayData = array(
            'storeName' => $store->getStoreName(),
            'storeSlug' => $store->getStoreSlug(),
            'user' => array(
                'primaryImage' => null,
            )
        );

        if ($primaryImage) {
            $arrayData['user']['primaryImage']['thumbnail'] = $primaryImage->getImageLocationBySize('thumbnail');
        }

        return $arrayData;
    }

    private function formatBrandToArray(Brand $brand)
    {
        return array(
            'name' => $brand->getName(),
            'image' => $brand->getImage(),
            'description' => $brand->getDescription(),
        );
    }

    private function formatCategory(ProductCategory $productCategory)
    {
        return array(
            'slug' => $productCategory->getSlug(),
            'name' => $productCategory->getName(),
        );
    }

    /**
     * Get product section of homepage
     *
     * @param $xmlObj
     * @param null $nodes
     * @param null $productDetailId
     * @return array
     */
    public function getHomeProductSection($xmlObj, $nodes = null, $productDetailId = null)
    {
        $xmlArray = json_decode(json_encode($xmlObj), true);
        $mainListSection = $xmlArray['mainList'];
        $sectionArray = array();

        $em = $this->entityManager;
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $storeRepository = $em->getRepository('YilinkerCoreBundle:Store');
        $productCategoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');

        $productListOffset = self::XML_PRODUCT_LIST_NODE;
        $productSection = isset($mainListSection[$productListOffset][0])
            ? $mainListSection[$productListOffset]
            : array($mainListSection[$productListOffset]);

        if (!is_null($nodes) && !in_array($productListOffset, $nodes)) {
            $productSection = array();
        }

        foreach ($productSection as $section) {

            $products = array();
            if (isset($section['products']['slug'])) {
                $productSlugs = !is_array($section['products']['slug'])
                                ? array($section['products']['slug'])
                                : $section['products']['slug'];
                $products = $productRepository->getProductsBySlug($productSlugs);
            }

            $featuredProduct = null;

            if (isset($section['featuredProduct'])
                && isset($section['featuredProduct']['image'])
                && is_array($section['featuredProduct']['image']) === false
                && trim($section['featuredProduct']['image']) !== "") {
                $slugSection = $section['featuredProduct'];
                $featuredProduct = array(
                    'alt' => !is_array($slugSection['alt']) ? $slugSection['alt'] : "",
                    'title' => !is_array($slugSection['title']) ? $slugSection['title'] : "",
                    'target' => !is_array($slugSection['target']) ? $slugSection['target'] : "",
                    'image' => $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY . $slugSection['image']),
                    'fileName' => $slugSection['image']
                );
            }

            $productArray = array();
            foreach ($products as $product) {
                $productArray[] = $this->formatProductToArray($product);
            }

            $seeMore = !is_array($section['seeMore']) ? $section['seeMore'] : "";
            $nodeProductDetailId = isset($section['@attributes']['productListNodeId']) ? $section['@attributes']['productListNodeId'] : null;
            $sectionData = array(
                'type'            => $productListOffset,
                'order'           => !is_array($section['order']) ? $section['order'] : "",
                'name'            => !is_array($section['name']) ? $section['name'] : "",
                'featuredProduct' => $featuredProduct,
                'seeMore'         => $seeMore,
                'products'        => $productArray,
                'productDetailId' => $nodeProductDetailId,
                'sectionId'       => isset($section['@attributes']['id']) ? $section['@attributes']['id'] : null,
                'productListNodeId' => $nodeProductDetailId
            );

            if (!is_null($productDetailId) && $nodeProductDetailId != $productDetailId) {
                continue;
            }

            $sectionArray[] = $sectionData;
        }

        $storeListOffset = self::XML_STORE_LIST_NODE;

        if (isset($mainListSection[$storeListOffset][0])) {
            $storeSection = $mainListSection[$storeListOffset];
        }
        else {
            $storeSection = isset($mainListSection[$storeListOffset]) ? array($mainListSection[$storeListOffset]) : array();
        }

        if (!is_null($nodes) && !in_array($storeListOffset, $nodes)) {
            $storeSection = array();
        }

        foreach ($storeSection as $section) {
            $sellers = array();
            if (!isset($section['store'])) {
                continue;
            }

            $stores = isset($section['store'][0])
                ? $section['store']
                : array($section['store']);

            foreach ($stores as $store) {
                if ($store['storeId'] && is_array($store['storeId']) === false) {
                    if ($storeDetail = $storeRepository->getOneStoreByStoreId($store['storeId'])) {
                        $products = array();
                        if (isset($store['products']['slug'])) {
                            $productSlugs = !is_array($store['products']['slug'])
                                            ? array($store['products']['slug'])
                                            : $store['products']['slug'];
                            $products = $productRepository->getProductsBySlug($productSlugs);
                        }

                        $productArray = array();
                        foreach ($products as $product) {
                            $productArray[] = $this->formatProductToArray($product);
                        }

                        $categoryId = ProductCategory::ROOT_CATEGORY_ID;
                        if (isset($store['specialtyCategoryId'])
                            && is_array($store['specialtyCategoryId']) === false) {
                            $categoryId = (int) $store['specialtyCategoryId'];
                        }
                        $category = $productCategoryRepository->getOneProductCategoryById($categoryId);

                        if (!$category) {
                            $category = $productCategoryRepository->getOneProductCategoryById(ProductCategory::ROOT_CATEGORY_ID);
                        }

                        $sellers[] = array(
                            'seller' => $this->formatSellerToArray($storeDetail),
                            'category' => array('name' => $category->getName()),
                            'products' => $productArray,
                        );
                    }
                }
            }

            $sectionArray[] = array(
                'type' => $storeListOffset,
                'order' => !is_array($section['order']) ? $section['order'] : "",
                'seeMore' => !is_array($section['seeMore']) ? $section['seeMore'] : "",
                'sellers' => $sellers,
                'name' => !is_array($section['name']) ? $section['name'] : ""
            );
        }

        $addSpaceOffset = self::XML_AD_SPACE_NODE;
        $addSpaceSection = isset($mainListSection[$addSpaceOffset][0])
            ? $mainListSection[$addSpaceOffset]
            : array($mainListSection[$addSpaceOffset]);

        if (!is_null($nodes) && !in_array($addSpaceOffset, $nodes)) {
            $addSpaceSection = array();
        }

        foreach ($addSpaceSection as $section) {
            $images = array();
            $banners = isset($section['banner'][0])
                ? $section['banner']
                : array($section['banner']);

            foreach ($banners as $banner) {
                $images[] = array(
                    'alt' => !is_array($banner['alt']) ? $banner['alt'] : "",
                    'title' => !is_array($banner['title']) ? $banner['title'] : "",
                    'target' => !is_array($banner['target']) ? $banner['target'] : "",
                    'image' => !is_array($banner['image']) ? $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY . $banner['image']) : "",
                );
            }

            $sectionArray[] = array(
                'type' => $addSpaceOffset,
                'order' => !is_array($section['order']) ? $section['order'] : "",
                'banners' => $images
            );
        }

        usort($sectionArray, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        return !is_null($productDetailId) && isset($sectionArray[0]) ? array_shift($sectionArray) : $sectionArray;
    }

    /**
     * Get Back to School Promo
     */
    public function getBackToSchoolPromo($xmlObj)
    {
        $xmlToArray = json_decode(json_encode($xmlObj), true);
        $promos = array();

        if (isset($xmlToArray['backToSchoolPromo'])) {

            $backToSchoolPromo = $xmlToArray['backToSchoolPromo'];

            foreach ($backToSchoolPromo['promos'] as $key => $promo) {

                foreach ($promo['data'] as $value) {

                    $promos['promos'][$key][] = array(
                        'slug' =>  $this->router->generate("product_details", array('slug' => $value['slug'])),
                        'image' => $this->assetsHelper->getUrl('images/featured-products/'.$value['image']),
                    );
                }
            }

            $promos['landing'] = array(
                'slug' => $backToSchoolPromo['landing']['slug'],
                'image' => $this->assetsHelper->getUrl('images/featured-products/'.$backToSchoolPromo['landing']['image']),
            );
        }

        return $promos;
    }

    /**
     * Get Product detail in web.xml and product.xml by id
     *
     * @param $id
     * @return array
     */
    public function getProductDetailById ($id)
    {
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $homePageData = $this->getHomeProductSection($homeXmlObject, array('productList'), $id);

        $mobileXmlObject = $this->xmlResourceGetter->fetchXML("products", "v2", "web");
        $productListObj = $this->xmlParser->getNodeWithId($mobileXmlObject, 'list', $id);
        $innerProductList = json_decode(json_encode($productListObj), 1);
        $innerProductIds = $innerProductList !== false && isset($innerProductList['productId']) ? $innerProductList['productId'] : array();

        $homeMobileXmlObject = $this->xmlResourceGetter->fetchXML("home", "v2", "mobile");
        $node = $homeMobileXmlObject->list->$id;
        $webViewProductIds = array();
        $webViewBanner = null;
        $webViewBannerSrc = null;
        $webViewBannerFileName = null;

        if (sizeof($node) > 0) {
            $node = (array) $node;
            $webViewProductSection = isset($node['products']) ? (array) $node['products'] : array();
            $webViewProductIds = isset($webViewProductSection['productId']) ? $webViewProductSection['productId'] : array();
            $webViewBanner = isset($node['mainBanner']) && (string) $node['mainBanner'] !== '' ? $this->assetsHelper->getUrl(self::PRODUCT_LIST_IMAGE_DIRECTORY . $node['mainBanner']) : null;
            $webViewBannerFileName = isset($node['mainBanner']) && (string) $node['mainBanner'] !== '' ? $node['mainBanner'] : null;
            $webViewBannerSrc = isset($node['target']) && (string) $node['target'] !== '' ? $node['target'] : null;
        }

        $innerProductIds = is_array($innerProductIds) ? $innerProductIds : array($innerProductIds);
        $webViewProductIds = is_array($webViewProductIds) ? $webViewProductIds : array($webViewProductIds);

        $productIds = array_unique(array_merge($innerProductIds, $webViewProductIds));
        $productRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Product');
        $productEntities = $productRepository->findByProductId($productIds);

        if (sizeof($homePageData) == 0 && is_null($webViewBanner) && sizeof($webViewProductIds) == 0 && sizeof($innerProductIds) == 0) {
            $productDetail = null;
        }
        else {
            $sectionId = self::NODE_ID_PRODUCT_LIST;

            if (isset($homePageData['sectionId'])) {
                $sectionId = $homePageData['sectionId'];
            }
            else if (!isset($homePageData['sectionId']) && $id == 'itemYouMayLike') {
                $sectionId = self::NODE_ID_ITEMS_YOU_MAY_LIKE;
            }
            $title = isset($homePageData['name']) ? $homePageData['name'] : null;

            $productDetail = array(
                'productDetailId'    => $id,
                'sectionId'          => (int) $sectionId,
                'title'              => $sectionId == self::NODE_ID_PRODUCT_LIST ? $id : $title,
                'homePageBannerSrc'  => isset($homePageData['featuredProduct']) && $homePageData['featuredProduct']['target'] != '' ?
                                            $homePageData['featuredProduct']['target'] : null,
                'homePageBannerUrl'  => isset($homePageData['featuredProduct']) ? $homePageData['featuredProduct']['image'] : null,
                'homePageBannerFileName'  => isset($homePageData['featuredProduct']) ? $homePageData['featuredProduct']['fileName'] : null,
                'innerPageBannerSrc' => $webViewBannerSrc,
                'innerPageBannerUrl' => !is_null($webViewBanner) && $webViewBanner !== '' ? $webViewBanner : null,
                'innerPageBannerFileName' => !is_null($webViewBannerFileName) && $webViewBannerFileName !== '' ? $webViewBannerFileName : null,
                'products'           => $productEntities
            );
        }

        return $productDetail;
    }

    /**
     * Get Product detail Sections
     *
     * @return array
     */
    public function getProductDetailSection()
    {
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $homePageData = $this->getHomeProductSection($homeXmlObject, array('productList'));
        $homePageRows[] = array('id' => self::NODE_ID_PRODUCT_LIST,'name' => 'None');
        $homePageRows[] = isset($homeXmlObject->itemYouMayLike) ? array('id' => self::NODE_ID_ITEMS_YOU_MAY_LIKE, 'name' => 'Item you may like') : array();

        if (sizeof($homePageData) > 0) {

            foreach ($homePageData as $productListHomePageRow) {

                if (isset($productListHomePageRow['name']) && isset($productListHomePageRow['sectionId'])) {
                    $homePageRows[] = array(
                        'id'   => (int) $productListHomePageRow['sectionId'],
                        'name' => $productListHomePageRow['name']
                    );
                }

            }

        }

        return $homePageRows;
    }

    /**
     * @param $banner
     * @return array|null
     */
    public function getProductDetailInnerBanner($banner)
    {
        $imageDetail = null;

        if (isset($banner['image']) && $banner['image'] != '') {
            $imageDetail = array(
                'alt'      => isset($banner['alt']) ? $banner['alt'] : '',
                'title'    => isset($banner['title']) ? $banner['title'] : '',
                'target'   => isset($banner['target']) ? $banner['target'] : '',
                'imageSrc' => $this->assetsHelper->getUrl(self::PRODUCT_LIST_IMAGE_DIRECTORY . $banner['image'])
            );
        }

        return $imageDetail;
    }

    /**
     * Get main banners by id
     *
     * @return array
     */
    public function getMainBanners()
    {
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $xmlArray = json_decode(json_encode($homeXmlObject), true);
        $mainBanner = isset($xmlArray['main']['mainBanner']) ? $xmlArray['main']['mainBanner'] : null;
        $isTemp = false;
        $banners = !is_null($mainBanner) && sizeof($mainBanner['banner']) > 0 ? $mainBanner['banner'] : array();

        foreach ($banners as &$banner) {
            $image = isset($banner['image']) && $banner['image'] !== '' ? $banner['image'] : null;
            $banner['alt'] = isset($banner['alt']) && !is_array($banner['alt']) &&
            $banner['alt'] != '' ? $banner['alt'] : '';
            $banner['target'] = isset($banner['target']) && !is_array($banner['target']) &&
                              $banner['target'] != '' ? $banner['target'] : '';

            if (!is_null($image)) {
                $banner['image'] =  $this->assetsHelper->getUrl(self::HOME_IMAGE_DIRECTORY .$image);
                $banner['imageName'] =  $image;
            }

        }

        usort($banners, function($a, $b) {
            if (isset($a['order']) && isset($b['order'])) {
                return $a['order'] - $b['order'];
            }
        });

        return compact('banners', 'isTemp');
    }

    /**
     * Get brand by id
     *
     * @param Brand $brand
     * @return array
     */
    public function getBrand(Brand $brand)
    {
        $isTemp = false;
        $productEntities = array();
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $xmlArray = json_decode(json_encode($homeXmlObject), true);
        $topBrands = isset($xmlArray['topBrands']) ? $xmlArray['topBrands'] : null;

        if (!is_null($topBrands)) {
            $topBrandsXml = isset($topBrands['brands']['brandId']) ? array($topBrands['brands']) : $topBrands['brands'];
            $homePageBrandKey = array_search($brand->getBrandId(), ArrayHelper::array_column($topBrands['brands'], 'brandId'));
            $homePageBrand = $homePageBrandKey !== false ? $topBrandsXml[$homePageBrandKey] : array();

            if (isset($homePageBrand['products']['slug'])) {
                $products = !is_array($homePageBrand['products']['slug']) ? array($homePageBrand['products']['slug']) : $homePageBrand['products']['slug'];
                $productEntities = $this->entityManager->getRepository('YilinkerCoreBundle:Product')
                                                       ->getProductsBySlug($products);
            }

        }

        $data = array (
            'brandId'     => $brand->getBrandId(),
            'brandName'   => $brand->getName(),
            'image'       => $brand->getImage(),
            'description' => $brand->getDescription(),
            'products'    => $productEntities,
            'isTemp'      => $isTemp
        );

        return $data;
    }

    /**
     * Get brand ids in top brands
     *
     * @return array
     */
    public function getBrandIdsInTopBrands()
    {
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $xmlArray = json_decode(json_encode($homeXmlObject), true);
        $topBrands = isset($xmlArray['topBrands']) ? $xmlArray['topBrands'] : null;
        $brandIds = array();

        if (!is_null($topBrands)) {
            $topBrandsXml = isset($topBrands['brands']['brandId']) ? array($topBrands['brands']) : $topBrands['brands'];
            foreach ($topBrandsXml as $brand) {
                $brandIds[] = $brand['brandId'];
            }
        }

        return $brandIds;
    }

    /**
     * Get Store details in xml
     *
     * @param $storeListNodeId
     * @param Store $storeEntity
     * @return array
     */
    public function getStoreDetailsInXml($storeListNodeId = self::STORE_LIST_NODE_ID_ONE, Store $storeEntity)
    {
        $isTemp = false;
        $productEntities = array();
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $xmlStores = $homeXmlObject->mainList->xpath('storeList[@storeListNodeId="' . $storeListNodeId . '"]')[0];
        $stores = json_decode(json_encode($xmlStores), true);
        $storeKey = array_search($storeEntity->getStoreId(), ArrayHelper::array_column($stores['store'], 'storeId'));
        $store = $storeKey !== false ? $stores['store'][$storeKey] : array();

        if (count($store) > 0) {
            $isNew = false;
            if (isset($store['products']['slug'])) {
                $products = !is_array($store['products']['slug']) ? array($store['products']['slug']) : $store['products']['slug'];
                $productEntities = $this->entityManager->getRepository('YilinkerCoreBundle:Product')
                                                       ->findBySlug($products);
            }
        }
        else {
            $isNew = true;
        }

        $data = array(
            'storeId'                   => $storeEntity->getStoreId(),
            'storeName'                 => $storeEntity->getStoreName(),
            'products'                  => $productEntities,
            'isTemp'                    => $isTemp,
            'isNew'                     => $isNew,
            'storeListNodeId'           => $storeListNodeId,
            'maxAllowableStoreProducts' => self::MAX_ALLOWABLE_STORE_PRODUCTS
        );

        return $data;
    }

    public function getStoreIdsInStoreList ($storeListNodeId)
    {
        $homeXmlObject = $this->xmlResourceGetter->fetchXML('home', 'v2', 'web');
        $xmlStores = $homeXmlObject->mainList->xpath('storeList[@storeListNodeId="' . $storeListNodeId . '"]')[0];
        $stores = json_decode(json_encode($xmlStores), true);
        $storeIds = array();

        if (isset($stores['store'])) {
            $stores = $stores['store'];
            $stores = isset($stores['storeId']) ? array($stores) : $stores;
            $storeIds = ArrayHelper::array_column($stores, 'storeId');
        }

        return $storeIds;
    }

    private function getOverseasCountry()
    {
        $locationService = $this->container->get('yilinker_core.service.location.location');
        $appCountryCode = $locationService->getCountryByApi()->getCode();

        $countries = $this->entityManager->getRepository('YilinkerCoreBundle:Country')
                                   ->findAllWithExclude($appCountryCode);

        $pathInfo = explode('/',$this->container->get('request')->getPathInfo());
        foreach ($countries as &$country) {
            $country = $locationService->countryDetail($country);
            //$languagecode = isset($pathInfo[4]) ? $pathInfo[4] : $country['defaultLanguage']['code'];

            $country['target'] = array(
                "targetUrl" => "/api/v3/".$pathInfo[3].'/'.$pathInfo[4].'/product/internationalProduct?country='.$country['code'],
                "targetType" => "countryProductList",
            );
        }

        return $countries;
    }

    public function getWebv3Content($xml)
    {
        $slugs = $this->xmlParser->getAllNodeValues($xml, "slug");
        $categoryIds = $this->xmlParser->getAllNodeValues($xml, "categoryId");
        $productCategoryRepository = $this->entityManager
                                          ->getRepository("YilinkerCoreBundle:ProductCategory");

        $productRepository = $this->entityManager
                                  ->getRepository("YilinkerCoreBundle:Product");

        $productCategories = $productCategoryRepository->loadProductCategoriesIn($categoryIds, false);
        $products = $productRepository->getProductsBySlug($slugs, null, true);

        $content = array();

        foreach($xml->layout as $layout){

            $layoutContent = array();
            switch ($layout["layoutId"]) {
                case self::HOMEPAGE_WEB_V6_CONTENT_BANNERS:
                    $categories = $layout->categorySideBar instanceof \SimpleXMLElement?
                                  $this->getCategorySidebarContents(
                                    $layout->categorySideBar->category,
                                    $productCategories
                                  ) :
                                  array();

                    $main = $layout->banners instanceof \SimpleXMLElement?
                            $this->getBannerContents($layout->banners) : array();

                    $layoutContent["categorySideBar"] = $categories;
                    $layoutContent["main"] = $main;
                    break;
                case self::HOMEPAGE_WEB_V6_CONTENT_FLASH_SALE:
                    $layoutContent["image"] = $layout->banner? $this->getImageContent($layout->banner) : array();
                    break;
                case self::HOMEPAGE_WEB_V6_CONTENT_CATEGORIES_SECTION:
                    $title = (string)$layout->title;

                    $categories = array();
                    foreach($layout->category as $category){
                        array_push($categories, array(
                            "category" => $this->getCategoryDetails((int)$category->categoryId, $productCategories),
                            "image" => $category->banner? $this->getImageContent($category->banner) : array(),
                            "productBanners" => $category->productBanners? $this->getImagesContent($category->productBanners->banner) : array(),
                            "primaryBanners" => $category->primaryBanners? $this->getImagesContent($category->primaryBanners->banner) : array(),
                            "secondaryBanners" => $category->secondaryBanners? $this->getImagesContent($category->secondaryBanners->banner) : array()
                        ));
                    }

                    $layoutContent["title"] = $title;
                    $layoutContent["categories"] = $categories;
                    break;
                case self::HOMEPAGE_WEB_V6_CONTENT_CUSTOM:
                    $layoutContent["image"] = $layout->banner? $this->getImageContent($layout->banner) : array();
                    $layoutContent["products"] = $layout->products? $this->constructProductDetails($layout->products->slug, $products) : array();
                    break;
                case self::HOMEPAGE_WEB_V6_CONTENT_CATEGORY_SECTION:
                
                    $categoryIds = array();
                    foreach ($layout->categoryId as $categoryId) {
                        array_push($categoryIds, (int)$categoryId);
                    }

                    $this->getCategoryDetails($categoryIds, $productCategories);
                    $layoutContent["category"] = implode(',', $this->categoryIds);
                    $layoutContent["image"] = $layout->banner? $this->getImageContent($layout->banner) : array();
                    $layoutContent["targetUrl"] = (string)$layout->targetUrl;

                    $productRows = array();
                    if($layout->products && $layout->products->row){
                        foreach($layout->products->row as $row){
                            $productRow = $this->constructProductDetails($row->slug, $products);
                            array_push($productRows, $productRow);
                        }
                    }

                    $layoutContent["products"] = $productRows;
                    break;
                case self::HOMEPAGE_WEB_V6_CONTENT_PRODUCT_IMAGES:
                    $layoutContent["title"] = (string)$layout->title;
                    $layoutContent["landing"] = $layout->productImages? $this->getImageContent($layout->productImages->landing->banner) : array();

                    $productImages = array();
                    if($layout->productImages){
                        foreach($layout->productImages->products->promo as $promo){
                            array_push($productImages, $this->getImagesContent($promo->banner));
                        }
                    }

                    $layoutContent["products"] = $productImages;
                    break;
            }

            if($layoutContent){
                array_push($content, array(
                    "layoutId" => (int)$layout["layoutId"],
                    "content" => $layoutContent
                ));
            }
        }

        return $content;
    }

    private function constructProductDetails($slugs, $products)
    {
        $content = array();

        if(!empty($slugs)){
            foreach($slugs as $slug){
                $slug = (string)$slug;
                if(array_key_exists($slug, $products)){
                    array_push($content, $this->productService->getProductDetail($products[$slug]));
                }
            }
        }

        return $content;
    }

    private function getCategoryDetails($category, $productCategories)
    {
        $this->categoryIds = array();
        
        if (is_array($category)) {
            $categories = array();
            foreach ($category as  $categoryId) {
                if(array_key_exists($categoryId, $productCategories)){
                    $tempcategories = $this->getCategory($categoryId, $productCategories);

                    array_push($categories, $tempcategories);
                    array_push($this->categoryIds, $tempcategories['id']);
                }
            }

            return $categories;

        } else {
            if(array_key_exists($category, $productCategories)){
                return $this->getCategory($category, $productCategories);
            }
        }

        return array();
    }

    private function getCategory($category, $productCategories)
    {
        return array(
            "id" => $productCategories[$category]->getProductCategoryId(),
            "name" => $productCategories[$category]->getName(),
            "description" => $productCategories[$category]->getDescription(),
            "slug" => $productCategories[$category]->getSlug(),
            "image" => $productCategories[$category]->getImage()
        );
    }




    private function getCategorySidebarContents($children, $productCategories)
    {
        $content = array();

        foreach($children as $child){

            $id = (int)$child->categoryId;
            $category = $this->getCategoryDetails($id, $productCategories);
            $adBig = array();

            if($child->adBig){
                $adBig = array(
                    "alt" => (string)$child->adBig->alt,
                    "title" => (string)$child->adBig->title,
                    "target" => (string)$child->adBig->target,
                    "image" => $this->assetsHelper->getUrl((string)$child->adBig->image, "cms")
                );
            }

            $adSmall = array();

            if($child->adSmall){
                $adSmall = array(
                    "alt" => (string)$child->adSmall->alt,
                    "title" => (string)$child->adSmall->title,
                    "target" => (string)$child->adSmall->target,
                    "image" => $this->assetsHelper->getUrl((string)$child->adSmall->image, "cms")
                );
            }

            array_push($content, array(
                "category" => $category,
                "adBig" => $adBig,
                "adSmall" => $adSmall
            ));
        }

       return $content;
    }

    private function getBannerContents($banners)
    {
        $mainBanner = array();
        $topRight = array();

        if($banners->mainBanner){
            $mainBanner = $this->getImagesContent($banners->mainBanner->banner);
        }

        if($banners->topRight){
            $topRight = $this->getImagesContent($banners->topRight->banner);
        }

        return array(
            "mainBanner" => $mainBanner,
            "topRight" => $topRight
        );
    }

    private function getImagesContent($collection)
    {
        $content = array();
        foreach($collection as $image){
            array_push($content, $this->getImageContent($image));
        }

        return $content;
    }

    private function getImageContent($image)
    {
        return array(
            "alt" => (string)$image->alt,
            "title" => (string)$image->title,
            "target" => (string)$image->target,
            "image" => $this->assetsHelper->getUrl((string)$image->image, (string)$image->section)
        );
    }
}
