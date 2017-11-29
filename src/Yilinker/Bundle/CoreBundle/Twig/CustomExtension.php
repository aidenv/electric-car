<?php

namespace Yilinker\Bundle\CoreBundle\Twig;

use Carbon\Carbon;
use Twig_SimpleFilter;
use Twig_SimpleTest;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;

class CustomExtension extends \Twig_Extension
{    
    const REMAINING_TIME_TYPE_UNIX = "unix";
    const REMAINING_TIME_TYPE_WHOLE = "whole";
    const REMAINING_TIME_TYPE_HOURS = "hours";
    const REMAINING_TIME_TYPE_MINUTES = "minutes";
    const REMAINING_TIME_TYPE_SECONDS = "seconds";

    protected $assetHelper;
    protected $logService;
    protected $notificationService;
    protected $serviceContainer;

    public function __construct($assetHelper = null, $logService, $notificationService, $serviceContainer)
    {
        $this->assetHelper = $assetHelper;
        $this->logService = $logService;
        $this->notificationService = $notificationService;
        $this->serviceContainer = $serviceContainer;
    }

    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('remaining_time', array($this, 'remainingTimeFilter')),
            new Twig_SimpleFilter('time_elapsed', array($this, 'timeElapsedFilter')),
            new Twig_SimpleFilter('json_decode', array($this, 'jsonDecode')),
            new Twig_SimpleFilter('cast_to_array', array($this, 'castToArray')),
            new Twig_SimpleFilter('phrasify', array($this, 'phrasify')),
            new Twig_SimpleFilter('product_status', array($this, 'productStatus')),
            new Twig_SimpleFilter('shorten_number', array($this, 'shortenNumber')),
            new Twig_SimpleFilter('escape_attr', array($this, 'escapeAttr'))
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('is_current_or_upcoming', array($this, 'isCurrentOrUpcomingFilter')),
            new \Twig_SimpleFunction('remaining_time', array($this, 'remainingTimeFilter')),
            new \Twig_SimpleFunction('is_reviewable', array($this, 'isReviewable')),
            new \Twig_SimpleFunction('safe_route', array($this, 'safeRoute')),
            new \Twig_SimpleFunction('file_exists', array($this, 'fileExists')),
            new \Twig_SimpleFunction('get_image', array($this, 'getImage')),
            new \Twig_SimpleFunction('asset', array($this, 'assetHelper')),
            new \Twig_SimpleFunction('activity_log', array($this, 'activityLog'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFunction('notification', array($this, 'notification'), array(
                'is_safe' => array('html')
            )),
            new \Twig_SimpleFunction('entity_array', array($this, 'entityArray'))
        );
    }

    public function entityArray($entity)
    {
        $entityService = $this->serviceContainer->get('yilinker_core.service.entity');

        return $entityService->toArray($entity);
    }

    public function safeRoute($routeName, $options = array())
    {
        $router = $this->serviceContainer->get('router');
        try {
            return $router->generate($routeName, $options);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function productStatus($status)
    {
        switch ($status) {
            case Product::DRAFT:
                return 'Draft';
            case Product::FOR_REVIEW:
                return 'For Review';
            case Product::ACTIVE:
                return 'Active';
            case Product::DELETE:
                return 'Deleted';
            case Product::FULL_DELETE:
                return 'Permanently Deleted';
            case Product::INACTIVE:
                return 'Inactive';
            default:
                return '';
        }
    }

    public function escapeAttr($attr)
    {
        return preg_replace("/\r\n|\r|\n/",'<br/>',htmlspecialchars($attr));
    }

    public function fileExists($path)
    {
        $parsedUrl = parse_url($path);

        if(@getimagesize($parsedUrl["scheme"]."://".$parsedUrl["host"].$parsedUrl["path"])){
            return true;
        }

        return false;
    }

    public function getImage($image, $module = 'user', $size = 'thumbnail', $imageType ='0')
    {
        if($image){
            $imageUrl = $this->assetHelper->getUrl($image->getImageLocationBySize($size), $module); 

            if($this->fileExists($imageUrl)){
                return $imageUrl;
            }
            else{
                return $this->assetHelper->getUrl($image->getImageLocation(), $module); 
            }
        }
        else{
            if($module === 'user' && $imageType == User::USER_TYPE_BUYER){
                return $this->assetHelper->getUrl('default-buyer.png');
            }
            elseif($module === 'user' && $imageType == User::USER_TYPE_SELLER){
                return $this->assetHelper->getUrl('default-merchant.png');
            }
        }
    }

    public function castToArray($stdClassObject)
    {
        $response = array();
        foreach ($stdClassObject as $key => $value) {
            $response[] = array($key, $value);
        }
        return $response;
    }

    public function jsonDecode($string, $array = false) 
    {
        return json_decode($string, $array);
    }

    public function activityLog($activity)
    {
        return $this->logService->activityView($activity);
    }

    public function notification($notification)
    {
        return $this->notificationService->getTemplate($notification);
    }

    public function assetHelper($path, $packageName = null, $version = null)
    {
        $url = null;
        if(!$packageName && preg_match("/\.(css|js)$/", $path)){
            /**
             * All CSS and JS files use the app hostname as the base_url unless a package is defined
             */

            $prefix = strpos($path, 'assets/') === 0 ? "" : "assets/";
            $assetversion = $this->assetHelper->getVersion();
            $url = "/{$prefix}{$path}?ver={$assetversion}";
        }
        else{
            /**
             * Add package defaults here is path is empty
             */

            if (!is_null($packageName) && !$path) {
                $url = $this->assetHelper->getUrl('images/default.jpg');
            }
        }

        if($url === null){
            $url = $this->assetHelper->getUrl($path, $packageName, $version);
        }
       
        return $url;
    }

    public function timeElapsedFilter($date)
    {
        $timeElapsed = Carbon::createFromTimeStamp(strtotime($date))
                             ->diffForHumans();

        return $timeElapsed;
    }

    public function remainingTimeFilter($date, $type)
    {
        $date = $date->format("U");
        $dateNow = Carbon::now()->format("U");
        $unixTimeDiff = $date - $dateNow;
        $minutes = floor($unixTimeDiff / 60);
        $seconds = $unixTimeDiff - $minutes * 60;
        $hours = "00";

        if($seconds < 10){
            $seconds = "0".$seconds;
        }

        if($minutes >= 60){
            $hours = floor($minutes/60);
            $minutes = $minutes - ($hours*60);
            if($hours < 10){
                $hours = "0".$hours;
            }
        }

        if($minutes < 10){
            $minutes = "0".$minutes;
        }

        switch ($type) {
            case self::REMAINING_TIME_TYPE_UNIX:
                return $unixTimeDiff;
                break;

            case self::REMAINING_TIME_TYPE_HOURS:
                return $hours;
                break;

            case self::REMAINING_TIME_TYPE_MINUTES:
                return $minutes;
                break;

            case self::REMAINING_TIME_TYPE_SECONDS:
                return $seconds;
                break;
            
            default:
                return $hours.":".$minutes.":".$seconds;
                break;
        }
    }

    /**
     * separates pascal and camel case strings and turn them into phrases
     */
    public function phrasify($name)
    {
        $phrase = preg_split('/(?=[A-Z])/', $name);
        $phrase = ucfirst(trim(implode(' ', $phrase)));

        return $phrase;
    }

    public function getTests()
    {
        return array(
            new Twig_SimpleTest('isInstanceOfProductCategory', array($this, 'isInstanceOfProductCategory')),
            new Twig_SimpleTest('classof', array($this, 'isClassOf'))
        );
    }

    public function isReviewable($entity, $sellerId = null)
    {
        $em = $this->serviceContainer->get('doctrine.orm.entity_manager');
        if ($entity instanceof UserOrder) {
            $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
            return $tbUserOrder->isReviewable($entity, $sellerId);
        }
        elseif ($entity instanceof OrderProduct)  {
            $tbOrderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct');
            return $tbOrderProduct->isReviewable($entity);
        }

        return false;
    }

    /**
     * Credits to original function
     * @link https://gist.github.com/bcole808/9371754
     * @return number
     */
    public function shortenNumber($number)
    {
        $isNegative = $number < 0;
        $number = abs($number);

        if ($number !== 0) {
            $abbrevs = array(12 => "T", 9 => "B", 6 => "M", 3 => "K", 0 => "");
            foreach($abbrevs as $exponent => $abbrev) {
                if($number >= pow(10, $exponent)) {
                    $display_num = $number / pow(10, $exponent);
                    $decimals = ($exponent >= 3 && round($display_num) < 100) ? 1 : 0;

                    $computedNumber = number_format($display_num, $decimals);

                    $fraction = $computedNumber - floor($computedNumber);

                    $returnValue = number_format($display_num, $fraction > 0) . $abbrev;

                    return ($isNegative ? '-' : '').$returnValue;
                }
            }
        }

        return $number;
    }

    public function isCurrentOrUpcomingFilter($isUpcoming, $isCurrent)
    {
        if($isUpcoming || $isCurrent){
            return true;
        }

        return false;
    }

    /**
     * @param $obj
     * @return bool
     */
    public function isInstanceOfProductCategory($obj)
    {
        if($obj instanceof ProductCategory){
            return true;
        }

        return false;
    }

    public function isClassOf($var, $instance)
    {
        return get_class($var) == $instance;
    }

    public function getName()
    {
        return 'custom_extension';
    }
}
