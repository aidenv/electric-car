<?php

namespace Yilinker\Bundle\BackendBundle\Services\Cms;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\File;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Services\Cms\PagesService;
use Yilinker\Bundle\CoreBundle\Services\Cms\ResourceService;
use Yilinker\Bundle\CoreBundle\Services\Upload\UploadService;
use Yilinker\Bundle\CoreBundle\Services\Redis\Keys as RedisKeys;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CmsManager
 * @package Yilinker\Bundle\BackendBundle\Services\Cms
 */
class CmsManager
{
    const TOP_BANNERS_JSON_FILE_NAME = 'top_banner';

    const TOP_BRANDS_JSON_FILE_NAME = 'top_brands';

    const SELLER_JSON_FILE_NAME = 'seller';

    const PRODUCTS_JSON_FILE_NAME = 'products';

    /**
     * @var Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\Cms\ResourceService
     */
    private $resourceService;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\Cms\UploadService
     */
    private $uploadService;

    private $kernel;

    private $container;

    private $redis;

    public function setContainer($container)
    {
        $this->container = $container;

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->resourceService = $container->get('yilinker_core.service.xml_resource_service');
        $this->uploadService = $container->get('yilinker_core.service.upload.upload');
        $this->kernel = $container->get('kernel');

        $this->redis = $container->has('snc_redis.default') ? $container->get('snc_redis.default') : null;

        return $this;
    }

    /**
     * @param $newDailyLoginData
     */
    public function updateDailyLogin ($newDailyLoginData)
    {
        $xml = $this->resourceService->fetchXML("home", "v2", "mobile");
        $bannerUrls = array (
            $newDailyLoginData['firstBannerUrl'],
            $newDailyLoginData['secondBannerUrl'],
            $newDailyLoginData['thirdBannerUrl']
        );

        $this->uploadService->setUploadDirectory('assets/images/uploads/cms/daily_login/');

        foreach ($xml->dailyLogin as $data) {
            $data->successMessage = $newDailyLoginData['firstMessage'];
            $data->errorMessage = $newDailyLoginData['secondMessage'];

            foreach ( (array) $data->images as $image ) {

                foreach ((array) $image as $key => $imageAttr) {

                    if ($newDailyLoginData['images'][$key] instanceof File) {
                        $fileName = $this->uploadService->uploadFile($newDailyLoginData['images'][$key]);
                        $imageAttr->fileName = $fileName;
                    }

                    $imageAttr->src = $bannerUrls[$key];
                }

            }

        }

        $this->resourceService->saveXml($xml, "home", "v2", "mobile");
    }

    /**
     * Save data to 3 files:
     *  home/v2/web.xml
     *  home/v2/mobile.xml
     *  products/v2/web.xml
     *
     * @param $formData
     * @return array
     */
    public function saveProductList($formData)
    {
        $response = true;
        $title = $formData['title'];
        $productEntities = $formData['products'];
        $sectionId = (int) $formData['sectionId'];
        $innerPageBannerFile = $formData['innerPageBannerSrc'][0];
        $featuredProductBannerFile = $formData['featuredProductBanner'][0];
        $innerPageBannerUrl = $formData['innerPageBannerUrl'];
        $featuredProductUrl = $formData['featuredProductUrl'];

        try {
            $isSuccess = true;

            $innerPageBannerFileName = $formData['innerPageBannerFileName'];
            if ($innerPageBannerFile instanceof File) {
                $this->uploadService->setUploadDirectory('assets' . PagesService::PRODUCT_LIST_IMAGE_DIRECTORY);
                $innerPageBannerFileName = $this->uploadService->uploadFile($innerPageBannerFile);
            }

            $featuredProductBannerFileName = $formData['featuredProductBannerFileName'];
            if ($featuredProductBannerFile instanceof File) {
                $this->uploadService->setUploadDirectory('assets/' . PagesService::HOME_IMAGE_DIRECTORY);
                $featuredProductBannerFileName = $this->uploadService->uploadFile($featuredProductBannerFile);
            }

            $file = $this->getTempJsonFile(self::PRODUCTS_JSON_FILE_NAME);
            $tempData = json_decode($file, true);
            if ($formData['applyImmediate']) {
                if ($sectionId === PagesService::NODE_ID_ITEMS_YOU_MAY_LIKE) {
                    $title = 'itemYouMayLike';
                    $this->addProductsInItemsYouMayLike($productEntities);
                }
                else if ($sectionId !== PagesService::NODE_ID_PRODUCT_LIST) {
                    $title = $this->updateProductListInWebHomePage($title, $sectionId, $productEntities, $featuredProductBannerFileName, $featuredProductUrl);
                }

                if ($isSuccess) {
                    $nodeId = $title;
                    $this->addNodeInProductList($nodeId, $productEntities, $innerPageBannerFileName, $innerPageBannerUrl);
                    $this->addNodeInMobileProductList($nodeId, $productEntities, $innerPageBannerFileName, $innerPageBannerUrl);
                    unset($tempData[$nodeId]);
                    if (count($tempData) == 0) {
                        $this->removeTempJsonFile(self::PRODUCTS_JSON_FILE_NAME);
                    }
                    else {
                        $response = $this->createTempJsonFile($tempData, self::PRODUCTS_JSON_FILE_NAME);
                    }
                }

                if ($this->redis) {
                    $this->redis->del(RedisKeys::HOME_DATA);
                    $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
                }
            }
            else {
                if ($sectionId === PagesService::NODE_ID_ITEMS_YOU_MAY_LIKE) {
                    $title = 'itemYouMayLike';
                }
                else if ($sectionId !== PagesService::NODE_ID_PRODUCT_LIST) {
                    $productListHomeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
                    $productNode = sizeof($productListHomeWebXmlObject->mainList->xpath('productList[@id="' . $sectionId . '"]')) > 0 ?
                                            $productListHomeWebXmlObject->mainList->xpath('productList[@id="' . $sectionId . '"]')[0] : null;
                    if (!is_null($productNode)) {
                        $title = (string) $productNode['productListNodeId'];
                    }
                }
                $nodeId = $title;

                $formData['innerPageBannerSrc'][0] = $innerPageBannerFileName;
                $formData['featuredProductBanner'][0] = $featuredProductBannerFileName;
                $products = array();
                foreach ($formData['products'] as $product) {
                    $products[] = $product->getProductId();
                }
                $formData['products'] = $products;
                $formData['applyImmediate'] = true;
                $tempData[$nodeId] = $formData;

                $response = $this->createTempJsonFile($tempData, self::PRODUCTS_JSON_FILE_NAME);
            }
        }
        catch (\Exception $e) {
            $response = false;
        }

        return $response;
    }

    /**
     * Add node if product list xml
     *
     * @param $nodeId
     * @param array $products
     * @param null $bannerSrc
     * @param null $bannerUrl
     */
    public function addNodeInProductList($nodeId, $products = array(), $bannerSrc = null, $bannerUrl = null)
    {
        $productListXmlObject = $this->resourceService->fetchXML("products", "v2", "web");
        unset($productListXmlObject->xpath('list[@id="' . $nodeId . '"]')[0][0]);

        $newNode = $productListXmlObject->addChild('list');
        $newNode->addAttribute('id', $nodeId);

        if (sizeof($products) > 0) {

            foreach($products as $product) {

                if ($product instanceof Product) {
                    $newNode->addChild('productId', $product->getProductId());
                }

            }

            $banner = $newNode->addChild('banner');
            if (!is_null($bannerSrc) && !is_null($bannerUrl)) {
                $banner->addChild('alt', $nodeId);
                $banner->addChild('title', $nodeId);
                $banner->addChild('target', $bannerUrl);
                $banner->addChild('image', $bannerSrc);
            }

        }

        $this->resourceService->saveXml($productListXmlObject, "products", "v2", "web");
    }

    /**
     * Add node in list in home/v2/mobile.xml
     *
     * @param $nodeId
     * @param array $products
     * @param null $banner
     * @param null $bannerSrc
     */
    public function addNodeInMobileProductList($nodeId, $products = array(), $banner = null, $bannerSrc = null)
    {
        $productListMobileXmlObject = $this->resourceService->fetchXML("home", "v2", "mobile");
        unset($productListMobileXmlObject->list->xpath($nodeId)[0][0]);
        $newNode = $productListMobileXmlObject->list->addChild($nodeId);

        if (!is_null($banner)) {
            $newNode->mainBanner = $banner;
        }

        if (sizeof($products) > 0) {
            $productNode = $newNode->addChild('products');

            foreach($products as $product) {

                if ($product instanceof Product) {
                    $productNode->addChild('productId', $product->getProductId());
                }

            }

        }

        $this->resourceService->saveXml($productListMobileXmlObject, "home", "v2", "mobile");
    }

    /**
     * Add products in items you may like in home/v2/web.xml
     *
     * @param array $products
     */
    public function addProductsInItemsYouMayLike($products = array())
    {
        $productListWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');

        if (sizeof($products) > 0) {
            unset($productListWebXmlObject->itemYouMayLike->slug);
            $itemYouMayLike = $productListWebXmlObject->itemYouMayLike;

            foreach($products as $product) {

                if ($product instanceof Product) {
                    $itemYouMayLike->addChild('slug', $product->getSlug());
                }

            }

        }

        $this->resourceService->saveXml($productListWebXmlObject, 'home', 'v2', 'web');
    }

    /**
     *
     * @param $title
     * @param $nodeId
     * @param array $products
     * @param null $bannerFileName
     * @param null $bannerSrc
     * @return null/string
     */
    public function updateProductListInWebHomePage($title, $nodeId, $products = array(), $bannerFileName = null, $bannerSrc = null)
    {
        $productListHomeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
        $productNode = sizeof($productListHomeWebXmlObject->mainList->xpath('productList[@id="' . $nodeId . '"]')) > 0 ?
                       $productListHomeWebXmlObject->mainList->xpath('productList[@id="' . $nodeId . '"]')[0] : null;
        $nodeId = null;

        if (!is_null($productNode)) {
            $nodeId = (string) $productNode['productListNodeId'];
            $productNode->name = $title;
            unset($productNode->products);

            if (!is_null($bannerFileName) && isset($productNode->featuredProduct) && isset($productNode->featuredProduct->image)) {
                $productNode->featuredProduct->image = $bannerFileName;
            }

            if (!is_null($bannerSrc) && isset($productNode->featuredProduct) && isset($productNode->featuredProduct->target)) {
                $productNode->featuredProduct->target = $bannerSrc;
            }

            if (sizeof($products) > 0) {

                $productList = $productNode->addChild('products');

                foreach($products as $product) {

                    if ($product instanceof Product) {
                        $productList->addChild('slug', $product->getSlug());
                    }

                }

            }

            $this->resourceService->saveXml($productListHomeWebXmlObject, 'home', 'v2', 'web');
        }

        return $nodeId;
    }

    public function getCoreTempDirectory()
    {
        $env = $this->kernel->getEnvironment();
        $translationService = $this->container->get('yilinker_core.translatable.listener');
        $country = $translationService->getCountry();

        return $this->kernel->getRootDir().DIRECTORY_SEPARATOR.
            '..'.DIRECTORY_SEPARATOR.
            '..'.DIRECTORY_SEPARATOR.
            'src'.DIRECTORY_SEPARATOR.
            'Yilinker'.DIRECTORY_SEPARATOR.
            'Bundle'.DIRECTORY_SEPARATOR.
            'CoreBundle'.DIRECTORY_SEPARATOR.
            'Resources'.DIRECTORY_SEPARATOR.
            'content'.DIRECTORY_SEPARATOR.
            $country . DIRECTORY_SEPARATOR .
            $env . DIRECTORY_SEPARATOR.
            'temp';
    }

    public function removeTempJsonFile($fileName)
    {
        $fs = new Filesystem;
        $tempFolder = $this->getCoreTempDirectory();

        $fs->remove(array($tempFolder.'/'.$fileName.'.json'));

        return true;
    }

    public function getTempJsonFile($fileName)
    {
        $fs = new Filesystem;
        $tempFolder = $this->getCoreTempDirectory();
        $filePath = "{$tempFolder}/{$fileName}.json";

        if ($fs->exists($filePath)) {
            return $jsonFile = file_get_contents($filePath);
        }

        return null;
    }

    public function createTempJsonFile($formData, $title)
    {
        $fs = new Filesystem;
        $tempFolder = $this->getCoreTempDirectory();

        if (!$fs->exists($tempFolder)) {
            $fs->mkdir($tempFolder);
        }

        $fs->dumpFile($this->getCoreTempDirectory() . '/' . $title . '.json', json_encode($formData, JSON_PRETTY_PRINT), 0777);

        return true;
    }

    public static function camelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = lcfirst($str);

        return $str;
    }

    /**
     * Save main banners
     *
     * @param array $data
     * @param $applyImmediately
     * @return bool
     */
    public function saveMainBanners($data = array(), $applyImmediately = false)
    {
        $homeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
        try {
            $dataToJson = array();

            if ($applyImmediately) {
                unset($homeWebXmlObject->main->mainBanner->banner);
            }

            foreach ($data as $bannerFile) {
                $banner = $homeWebXmlObject->main->mainBanner->addChild('banner');

                if ($bannerFile['isNew'] == true && $bannerFile['bannerFile'] instanceof File) {
                    $this->uploadService->setUploadDirectory('assets/' . PagesService::HOME_IMAGE_DIRECTORY);
                    $bannerFile['fileName'] = $this->uploadService->uploadFile($bannerFile['bannerFile']);
                }

                $banner->addChild('order', $bannerFile['order']);
                $banner->addChild('alt', 'Home page Image');
                $banner->addChild('title', 'Home page Image');
                $banner->addChild('target', $bannerFile['link']);
                $banner->addChild('image', $bannerFile['fileName']);

                $bannerFile['bannerFile'] = null;
                $bannerFile['isNew'] = false;
                $bannerFile['imageName'] = $bannerFile['fileName'];
                $bannerFile['target'] = $bannerFile['link'];
                $dataToJson[] = $bannerFile;
            }
            $response = true;
        }
        catch (\Exception $e) {
            $response = false;
        }

        if ($response && $applyImmediately) {
            $this->resourceService->saveXml($homeWebXmlObject, 'home', 'v2', 'web');
            $this->removeTempJsonFile(self::TOP_BANNERS_JSON_FILE_NAME);

            if ($this->redis) {
                $this->redis->del(RedisKeys::HOME_DATA);
                $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
            }

        }
        else if ($response && $applyImmediately == false && isset($dataToJson)) {
            $response = $this->createTempJsonFile($dataToJson, self::TOP_BANNERS_JSON_FILE_NAME);
        }

        return $response;
    }

    /**
     * Save brand
     *
     * @param $data
     * @param $applyImmediately
     * @return array
     */
    public function saveBrand($data, $applyImmediately)
    {
        $homeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
        $brand = $this->em->getRepository('YilinkerCoreBundle:Brand')->find($data['brand']);

        if (!$brand instanceof Brand) {
            return false;
        }

        try {
            $fileName = $data['imageFileName'];

            if ((bool) $data['isImageNew'] && isset($data['image'][0]) && $data['image'][0] instanceof File) {
                $this->uploadService->setUploadDirectory('assets/images/brands/');
                $fileName = $this->uploadService->uploadFile($data['image'][0]);
            }

            $productNode = sizeof($homeWebXmlObject->topBrands->xpath('brands[@brandId="' . $brand->getBrandId() . '"]')) > 0 ?
                            $homeWebXmlObject->topBrands->xpath('brands[@brandId="' . $brand->getBrandId() . '"]')[0] : null;

            if (is_null($productNode)) {
                $brandNode = $homeWebXmlObject->topBrands->addChild('brands');
                $brandNode->addAttribute('brandId', $brand->getBrandId());
                $brandNode->addChild('brandId', $brand->getBrandId());
                $productNode = $brandNode->addChild('products');
            }

            if ($applyImmediately) {
                unset($productNode->products);
                $productNode = $productNode->addChild('products');
                $productEntities = $this->em->getRepository('YilinkerCoreBundle:Product')->findByProductId($data['products']);

                foreach ($productEntities as $product) {
                    $productNode->addChild('slug', $product->getSlug());
                }

                $brand->setDescription($data['description']);
                $brand->setImage($fileName);
                $this->em->flush();
            }
            else {
                $data['imageFileName'] = $fileName;
                $data['isImageNew'] = 0;
                $data['image'] = null;
                $data['brandName'] = $brand->getName();
                $data['brandId'] = $brand->getBrandId();
            }
            $response = true;

        }
        catch (\Exception $e) {
            $response = $e->getMessage();
        }

        if ($response && $applyImmediately) {
            $this->resourceService->saveXml($homeWebXmlObject, 'home', 'v2', 'web');
            $this->removeTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);

            $file = $this->getTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);
            $tempTopBrands = json_decode($file, true);
            unset($tempTopBrands[$brand->getBrandId()]);
            if (count($tempTopBrands) == 0) {
                $this->removeTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);
            }
            else {
                $response = $this->createTempJsonFile($tempTopBrands, self::TOP_BRANDS_JSON_FILE_NAME);
            }
            if ($this->redis) {
                $this->redis->del(RedisKeys::HOME_DATA);
                $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
            }
        }
        else if ($response && $applyImmediately == false) {
            $file = $this->getTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);
            $tempTopBrands = json_decode($file, true);
            $tempTopBrands[$brand->getBrandId()] = $data;
            $response = $this->createTempJsonFile($tempTopBrands, self::TOP_BRANDS_JSON_FILE_NAME);
        }

        return $response;
    }

    /**
     * Save store in xml
     *
     * @param $storeId
     * @param $storeListNodeId
     * @param $applyImmediately
     * @param $productIds
     * @param $oldStoreId
     * @return bool
     */
    public function saveStore($storeId, $storeListNodeId, array $productIds = array(), $applyImmediately, $oldStoreId)
    {
        $store = $this->em->getRepository('YilinkerCoreBundle:Store')->find($storeId);

        if (!$store instanceof Store) {
            return false;
        }

        $response = false;
        $homeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
        $storeDetailsNode = $homeWebXmlObject->mainList->xpath('storeList[@storeListNodeId="' . $storeListNodeId . '"]');
        $storeDetailsNode = sizeof($storeDetailsNode) > 0 ? $storeDetailsNode[0] : null;
        $storeNode = !is_null($oldStoreId) ? $storeDetailsNode->xpath('store/storeId[.="' . $oldStoreId .'"]/parent::*') : array();
        $productEntities = $this->em->getRepository('YilinkerCoreBundle:Product')->findByProductId($productIds);
        $slugs = array();

        if (count($productIds) > 0) {
            try {

                if ($applyImmediately) {

                    if (count($storeNode) > 0) {
                        $storeNode = $storeNode[0];
                        $storeNode->storeId = $store->getStoreId();
                        unset($storeNode->products);
                    }
                    else {
                        $storeNode = $storeDetailsNode->addChild('store');
                        $storeNode->addChild('storeId', $store->getStoreId());
                        $storeNode->addChild('specialtyCategoryId', '0');
                    }

                    $products = $storeNode->addChild('products');

                    foreach ($productEntities as $productEntity) {
                        $products->addChild('slug', $productEntity->getSlug());
                    }
                }
                else {
                    foreach ($productEntities as $productEntity) {
                        $slugs[] = $productEntity->getSlug();
                    }
                }

                $response = true;
            }
            catch (\Exception $e) {
                $response = $e->getMessage();
            }
        }

        if ($response && $applyImmediately) {
            $this->resourceService->saveXml($homeWebXmlObject, 'home', 'v2', 'web');
            $file = $this->getTempJsonFile(self::SELLER_JSON_FILE_NAME);
            $this->removeTempJsonFile(self::SELLER_JSON_FILE_NAME);
            $tempSellers = json_decode($file, true);
            unset($tempSellers[$storeListNodeId][$storeId]);
            if (count($tempSellers) == 0) {
                $this->removeTempJsonFile(self::SELLER_JSON_FILE_NAME);
            }
            else {
                $response = $this->createTempJsonFile($tempSellers, self::SELLER_JSON_FILE_NAME);
            }
            if ($this->redis) {
                $this->redis->del(RedisKeys::HOME_DATA);
                $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
            }
        }
        else if ($response && $applyImmediately == false) {
            $file = $this->getTempJsonFile(self::SELLER_JSON_FILE_NAME);
            $tempSellers = json_decode($file, true);
            $data = array(
                'storeId'           => $oldStoreId,
                'storeName'         => $store->getStoreName(),
                'productIds'        => $productIds,
                'storeListNodeId'   => $storeListNodeId,
                'isTemp'            => true,
                'isNew'             => false,
                'slugs'             => $slugs,
                'maxAllowableStoreProducts' => PagesService::MAX_ALLOWABLE_STORE_PRODUCTS
            );
            $tempSellers[$storeListNodeId][$storeId] = $data;
            $response = $this->createTempJsonFile($tempSellers, self::SELLER_JSON_FILE_NAME);
        }

        return $response;
    }

    /**
     * Remove brands
     *
     * @param array $brandIds
     * @return bool
     */
    public function removeBrands(array $brandIds = array())
    {
        try {
            $file = $this->getTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);
            $this->removeTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);
            $tempBrands = json_decode($file, true);
            $homeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
            foreach($brandIds as $brandId) {
                $brandNode = sizeof($homeWebXmlObject->topBrands->xpath('brands[@brandId="' . $brandId . '"]')) > 0 ?
                    $homeWebXmlObject->topBrands->xpath('brands[@brandId="' . $brandId . '"]')[0] : null;
                unset($tempBrands[$brandId]);

                if (!is_null($brandNode)) {
                    unset($homeWebXmlObject->topBrands->xpath('brands[@brandId="' . $brandId . '"]')[0][0]);
                }

            }

            if (count($homeWebXmlObject->topBrands->xpath('brands')) == 0) {
                throw new \Exception('Remove all brands is Invalid');
            }

            if (count($tempBrands) == 0) {
                $this->removeTempJsonFile(self::TOP_BRANDS_JSON_FILE_NAME);
            }
            else {
                $this->createTempJsonFile($tempBrands, self::TOP_BRANDS_JSON_FILE_NAME);
            }

            if ($this->redis) {
                $this->redis->del(RedisKeys::HOME_DATA);
                $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
            }
            $this->resourceService->saveXml($homeWebXmlObject, 'home', 'v2', 'web');
            $isSuccessful = true;
        }
        catch(\Exception $e) {
            $isSuccessful = false;
        }

        return $isSuccessful;
    }

    /**
     * Remove Store
     *
     * @param array $storeIds
     * @return bool
     */
    public function removeStores(array $storeIds = array())
    {
        try {
            $file = $this->getTempJsonFile(self::SELLER_JSON_FILE_NAME);
            $this->removeTempJsonFile(self::SELLER_JSON_FILE_NAME);
            $tempStores = json_decode($file, true);
            $homeWebXmlObject = $this->resourceService->fetchXML('home', 'v2', 'web');
            foreach($storeIds as $storeDetail) {
                $storeDetail = explode('-', $storeDetail);
                $storeId = isset($storeDetail[0]) ? $storeDetail[0] : null;
                $storeListNodeId = isset($storeDetail[1]) ? $storeDetail[1] : null;

                if (!is_null($storeId) && !is_null($storeListNodeId)) {
                    unset($tempStores[$storeListNodeId][$storeId]);
                    $storeDetailsNode = $homeWebXmlObject->mainList->xpath('storeList[@storeListNodeId="' . $storeListNodeId . '"]');
                    $storeDetailsNode = sizeof($storeDetailsNode) > 0 ? $storeDetailsNode[0] : null;
                    $storeNode = !is_null($storeId) ? $storeDetailsNode->xpath('store/storeId[.="' . $storeId .'"]/parent::*') : null;

                    if (!is_null($storeNode)) {
                        unset($storeDetailsNode->xpath('store/storeId[.="' . $storeId .'"]/parent::*')[0][0]);
                    }

                }

            }

            if (count($homeWebXmlObject->mainList->xpath('storeList')) == 0) {
                throw new \Exception('Remove all stores is Invalid');
            }

            if (count($tempStores) == 0) {
                $this->removeTempJsonFile(self::SELLER_JSON_FILE_NAME);
            }
            else {
                $this->createTempJsonFile($tempStores, self::SELLER_JSON_FILE_NAME);
            }

            if ($this->redis) {
                $this->redis->del(RedisKeys::HOME_DATA);
                $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
            }

            $this->resourceService->saveXml($homeWebXmlObject, 'home', 'v2', 'web');
            $isSuccessful = true;
        }
        catch(\Exception $e) {
            $isSuccessful = false;
        }

        return $isSuccessful;
    }

    /**
     * Remove Products
     *
     * @param array $productIds
     * @return bool
     */
    public function removeProducts(array $productIds = array())
    {
        try {
            $file = $this->getTempJsonFile(self::PRODUCTS_JSON_FILE_NAME);
            $this->removeTempJsonFile(self::PRODUCTS_JSON_FILE_NAME);
            $tempProducts = json_decode($file, true);
            $productListXmlObject = $this->resourceService->fetchXML("products", "v2", "web");
            $productListMobileXmlObject = $this->resourceService->fetchXML("home", "v2", "mobile");

            foreach($productIds as $productId) {
                unset($tempProducts[$productId]);
                unset($productListXmlObject->xpath('list[@id="' . $productId . '"]')[0][0]);
                unset($productListMobileXmlObject->list->xpath($productId)[0][0]);
            }

            if (count($tempProducts) == 0) {
                $this->removeTempJsonFile(self::PRODUCTS_JSON_FILE_NAME);
            }
            else {
                $this->createTempJsonFile($tempProducts, self::PRODUCTS_JSON_FILE_NAME);
            }

            if ($this->redis) {
                $this->redis->del(RedisKeys::HOME_DATA);
                $this->redis->del(RedisKeys::HOME_PRODUCT_SECTION);
            }

            $this->resourceService->saveXml($productListXmlObject, "products", "v2", "web");
            $this->resourceService->saveXml($productListMobileXmlObject, "home", "v2", "mobile");
            $isSuccessful = true;
        }
        catch(\Exception $e) {
            $isSuccessful = false;
        }

        return $isSuccessful;
    }

}
