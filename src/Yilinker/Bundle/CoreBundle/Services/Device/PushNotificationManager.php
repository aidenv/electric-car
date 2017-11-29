<?php

namespace Yilinker\Bundle\CoreBundle\Services\Device;

use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;
use Yilinker\Bundle\CoreBundle\Traits\SlugHandler;

use Yilinker\Bundle\CoreBundle\Services\Cms\PagesService;

use Carbon\Carbon;

class PushNotificationManager
{
    use SlugHandler;

    const ROUTE_NAME_API_HOME = "pages_home";
    
    const ROUTE_NAME_WEBVIEW_FLASH_SALE = "flash_sale_webview";

    const ROUTE_NAME_WEBVIEW_CATEGORY = "category_webview";

    const ROUTE_NAME_WEBVIEW_PRODUCT_LIST = "product_list_webview";

    const ROUTE_NAME_WEBVIEW_STORE = "store_webview";
                        
    const ROUTE_NAME_WEBVIEW_DAILY_LOGIN = "daily_login_webview";

    const ROUTE_NAME_API_PRODUCT_DETAIL = "api_product_detail";

    const ROUTE_NAME_API_PRODUCT_LIST = "api_product_list";

    const ROUTE_NAME_API_STORE_INFO = "api_store_get_info";

    const ROUTE_NAME_API_STORE_SEARCH = "api_store_search";

    private $em;

    private $router;

    private $tokenStorage;

    private $frontendHostName;

    public function __construct($em, $router, $tokenStorage, $frontendHostName)
    {
        $this->em = $em;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->frontendHostName = $frontendHostName;
    }

    public function create($data)
    {
        $deviceNotification = new DeviceNotification();
        $deviceNotification = $this->constructNotificationObject($deviceNotification, $data);

        $deviceNotification->setIsSent(false)
                           ->setDateAdded(Carbon::now());

        $this->em->persist($deviceNotification);
        $this->em->flush();

        return $deviceNotification;
    }

    public function update($data)
    {
        $deviceNotification = $this->constructNotificationObject($data["deviceNotification"], $data);
        $this->em->flush();

        return $deviceNotification;
    }

    public function constructNotificationObject($deviceNotification, $data)
    {
        $target = $this->getTarget($data["target"], $data["targetType"]);
        $targetParameters = $this->getTargetParameter($data["target"], $data["targetType"]);
        $queryString = array_key_exists("queryString", $targetParameters)? $targetParameters["queryString"] : null;

        $deviceNotification->setTitle($data["title"])
                           ->setMessage($data["message"])
                           ->setTarget($target)
                           ->setTargetType($data["targetType"])
                           ->setDateLastModified(Carbon::now())
                           ->setDateScheduled(Carbon::createFromFormat("m/d/Y (H:i:s)", $data["dateScheduled"]))
                           ->setRecipient($data["recipient"])
                           ->setTargetParameters($queryString)
                           ->setIsActive($data["isActive"])
                           ->setCreatedBy($this->tokenStorage->getToken()->getUser());

        if($data["targetType"] == DeviceNotification::TARGET_TYPE_PRODUCT){
            $product = array_key_exists("product", $targetParameters)? $targetParameters["product"] : null;
            $deviceNotification->setProduct($product);
        }
        elseif($data["targetType"] == DeviceNotification::TARGET_TYPE_STORE){
            $user = array_key_exists("user", $targetParameters)? $targetParameters["user"] : null;
            $deviceNotification->setUser($user);
        }

        return $deviceNotification;
    }

    public function constructNotificationTargets($notification)
    {
        $isAuthenticated = $notification->getTarget() == self::ROUTE_NAME_WEBVIEW_DAILY_LOGIN? true : false;

        $route = $notification->getTarget() == self::ROUTE_NAME_API_HOME? 
                    $this->router->generate($notification->getTarget(), array("version" => "v2")) :
                    $this->router->generate($notification->getTarget());

        $route = $notification->getTargetType() == DeviceNotification::TARGET_TYPE_WEBVIEW?
                    $this->frontendHostName.$route : $route;

        $target = $route."?".$notification->getTargetParameters();

        $data = array(
            "title"             => $notification->getTitle(),
            "message"           => $notification->getMessage(),
            "targetType"        => $notification->getTargetType(),
            "target"            => $target,
            "isAuthenticated"   => $isAuthenticated
        );

        return $data;
    }

    public function getTarget($input, $targetType)
    {
        $target = null;
                        
        switch ($targetType) {
            case DeviceNotification::TARGET_TYPE_HOME:
                $target = self::ROUTE_NAME_API_HOME;
                break;
            case DeviceNotification::TARGET_TYPE_WEBVIEW:
                switch ($input) {
                    case DeviceNotification::TARGET_FLASH_SALE:
                        $target = self::ROUTE_NAME_WEBVIEW_FLASH_SALE;
                        break;
                    case DeviceNotification::TARGET_CATEGORIES:
                        $target = self::ROUTE_NAME_WEBVIEW_CATEGORY;
                        break;
                    case DeviceNotification::TARGET_HOT_ITEMS:
                    case DeviceNotification::TARGET_NEW_ITEMS:
                    case DeviceNotification::TARGET_TODAYS_PROMO:
                        $target = self::ROUTE_NAME_WEBVIEW_PRODUCT_LIST;
                        break;
                    case DeviceNotification::TARGET_NEW_STORES:
                    case DeviceNotification::TARGET_HOT_STORES:
                        $target = self::ROUTE_NAME_WEBVIEW_STORE;
                    case DeviceNotification::TARGET_DAILY_LOGIN:
                        $target = self::ROUTE_NAME_WEBVIEW_DAILY_LOGIN;
                        break;
                }
                break;
            case DeviceNotification::TARGET_TYPE_PRODUCT:
                $target = self::ROUTE_NAME_API_PRODUCT_DETAIL;
                break;
            case DeviceNotification::TARGET_TYPE_PRODUCT_LIST:
                $target = self::ROUTE_NAME_API_PRODUCT_LIST;
                break;
            case DeviceNotification::TARGET_TYPE_STORE:
                $target = self::ROUTE_NAME_API_STORE_INFO;
                break;
            case DeviceNotification::TARGET_TYPE_STORE_LIST:
                $target = self::ROUTE_NAME_API_STORE_SEARCH;
                break;
        }

        return $target;
    }

    public function getTargetParameter($target, $targetType)
    {
        $targetParameters = array();

        switch ($targetType) {
            case DeviceNotification::TARGET_TYPE_WEBVIEW:
                switch ($target) {
                    case DeviceNotification::TARGET_HOT_ITEMS:
                        $targetParameters["queryString"] = "node=".PagesService::PRODUCT_NODE_HOT_ITEMS;
                        break;
                    case DeviceNotification::TARGET_NEW_ITEMS:
                        $targetParameters["queryString"] = "node=".PagesService::PRODUCT_NODE_NEW_ITEMS;
                        break;
                    case DeviceNotification::TARGET_TODAYS_PROMO:
                        $targetParameters["queryString"] = "node=".PagesService::PRODUCT_NODE_TODAYS_PROMO;
                        break;
                    case DeviceNotification::TARGET_NEW_STORES:
                        $targetParameters["queryString"] = "node=".PagesService::STORE_NODE_NEW_STORE;
                        break;
                    case DeviceNotification::TARGET_HOT_STORES:
                        $targetParameters["queryString"] = "node=".PagesService::STORE_NODE_HOT_STORE;
                        break;
                }
                break;
            case DeviceNotification::TARGET_TYPE_PRODUCT:
                $product = $this->em
                                ->getRepository("YilinkerCoreBundle:Product")
                                ->findOneBySlug($this->getLastSegment($target));

                $targetParameters["product"] = $product? $product : null;
                $targetParameters["queryString"] = $product? "productId=".$product->getProductId() : null;
                break;
            case DeviceNotification::TARGET_TYPE_STORE:
                $store = $this->em
                              ->getRepository("YilinkerCoreBundle:Store")
                              ->findOneByStoreSlug($this->getLastSegment($target));

                $targetParameters["user"] = $store? $store->getUser() : null;
                $targetParameters["queryString"] = $store? "userId=".$store->getUser()->getUserId() : null;
                break;
            case DeviceNotification::TARGET_TYPE_PRODUCT_LIST:
            case DeviceNotification::TARGET_TYPE_STORE_LIST:
                $targetParameters["queryString"] = $this->getQueryString($target);
                break;
        }

        return $targetParameters;
    }

    public function getTargetTypes()
    {
        return array(
            DeviceNotification::TARGET_TYPE_HOME => "Home",
            DeviceNotification::TARGET_TYPE_WEBVIEW => "Custom Pages",
            DeviceNotification::TARGET_TYPE_PRODUCT => "Product",
            DeviceNotification::TARGET_TYPE_PRODUCT_LIST => "Product Search",
            DeviceNotification::TARGET_TYPE_STORE => "Store",
            DeviceNotification::TARGET_TYPE_STORE_LIST => "Store Search"
        );
    }
}
