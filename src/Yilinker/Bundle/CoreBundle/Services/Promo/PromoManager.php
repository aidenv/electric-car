<?php

namespace Yilinker\Bundle\CoreBundle\Services\Promo;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Yilinker\Bundle\CoreBundle\Repository\PromoInstanceRepository;

/**
 * Class PromoManager
 * @package Yilinker\Bundle\CoreBundle\Services\Promo
 */
class PromoManager
{

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    private $router;

    private $assetsHelper;

    private $translatableListener;

    /**
     * Constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager,
        $router,
        $assetsHelper,
        $translatableListener
    ){
        $this->em = $entityManager;
        $this->router = $router;
        $this->assetsHelper = $assetsHelper;
        $this->translatableListener = $translatableListener;
    }

    /**
     * Get Flash Sale data base on dateTimeStart and dateTimeEnd
     *
     * @param $dateTimeStart
     * @param $dateTimeEnd
     * @return array
     */
    public function getFlashSaleData ($dateTimeStart, $dateTimeEnd)
    {
        $promoInstanceRepository = $this->em->getRepository('YilinkerCoreBundle:PromoInstance');
        $productUnitRepository = $this->em->getRepository('YilinkerCoreBundle:ProductUnit');
        $orderProductRepository = $this->em->getRepository('YilinkerCoreBundle:OrderProduct');
        $promoInstanceEntities = $promoInstanceRepository->loadPromoInstances('', $dateTimeStart, $dateTimeEnd, null, null, false, PromoType::FIXED, 'ASC');
        $promoData = array ();

        if (sizeof($promoInstanceEntities) > 0 ) {

            foreach ($promoInstanceEntities as $key => $promoInstanceEntity) {
                $promoInstanceArray = $promoInstanceEntity->toArray();
                $promoStartDateTime = $promoInstanceEntity->getDateStart()->format('Y/m/d h:i:s');
                $promoEndDateTime = $promoInstanceEntity->getDateEnd()->format('Y/m/d h:i:s');

                foreach ($promoInstanceArray['products'] as $productKey => $product) {
                    $productUnitEntity = $productUnitRepository->find($product['productUnitId']);
                    $productUnitEntity->soldQuantity = $orderProductRepository->getSoldQuantityByProduct($productUnitEntity->getProduct(), $promoStartDateTime, $promoEndDateTime);
                    $productUnitEntity->soldPercentage = @($productUnitEntity->soldQuantity/$promoInstanceEntity->getQuantityRequired()) * 100;
                    $promoInstanceArray['productUnitEntities'][$productKey] = $productUnitEntity;
                }

                $promoData[$key] = $promoInstanceArray;
                $promoData[$key]['isActive'] = false;

                if (strtotime(Carbon::now()->format('Y/m/d h:i:s')) >= strtotime($promoStartDateTime) &&
                    strtotime(Carbon::now()->format('Y/m/d h:i:s')) > strtotime($promoEndDateTime)) {
                    $promoData[$key]['isActive'] = true;
                }

            }

        }

        return $promoData;
    }

    public function constructFlashSaleProducts($productUnits)
    {
        $products = array();

        foreach($productUnits as $productUnit){

            $product = $productUnit['productUnit']->getProduct();
            $unitDetails = $productUnit['productUnit']->toArray();

            $price = $productUnit['productUnit']->getPrice();

            if (sizeof($unitDetails['promoInstance']) > 0) {
                $finalPrice = $unitDetails["appliedDiscountPrice"];
            }
            else {
                $finalPrice = $this->getPromoPrice($unitDetails, $productUnit);
            }

            $discount = 100 - (floatval($finalPrice/$price) * 100);
            $discount = floatval(number_format(floor($discount*100)/100, 2));
            $productDetails = array (
                "productId" => $product->getProductId(),
                "name" => $product->getName(),
                "shortDescription" => $product->getShortDescription(),
                "productUnitId" => $unitDetails["productUnitId"],
                "price" => number_format($price, 2),
                "discountedPrice" => number_format($finalPrice, 2),
                "discount" => $discount,
                "thumbnail" => $this->assetsHelper->getUrl($product->getPrimaryImageLocationBySize("thumbnail"), "product"),
                "small" => $this->assetsHelper->getUrl($product->getPrimaryImageLocationBySize("small"), "product"),
                "medium" => $this->assetsHelper->getUrl($product->getPrimaryImageLocationBySize("medium"), "product"),
                "large" => $this->assetsHelper->getUrl($product->getPrimaryImageLocationBySize("large"), "product"),
                "slug" => $product->getSlug(),
                "isActive" => count($unitDetails['promoInstance']) > 0,
            );

            $promoProductState = 0;
            if(
                $productUnit["maxQuantity"] > 0 &&
                $productUnit["productsSold"] > 0 &&
                $productUnit["productsSold"] < $productUnit["maxQuantity"]
            ){
                $promoProductState = floatval(($productUnit["productsSold"]/$productUnit["maxQuantity"]) * 100);
            }
            elseif(
                $productUnit["productsSold"] == $productUnit["maxQuantity"] ||
                $productUnit["productsSold"] > $productUnit["maxQuantity"]
            ){
                $promoProductState = 100;
            }

            $productPromoDetails = array(
                "productsSold" => (int)$productUnit["productsSold"] > (int)$productUnit["maxQuantity"]?
                                    (int)$productUnit["maxQuantity"] : (int)$productUnit["productsSold"],
                "maxQuantity" => (int)$productUnit["maxQuantity"],
                "promoProductState" => $promoProductState
            );

            array_push($products, array_merge($productDetails, $productPromoDetails));
        }

        return $products;
    }

    /**
     * Get Promo Price
     *
     * @param $unitDetails
     * @param $promoInstanceId
     * @return float
     */
    public function getPromoPrice ($unitDetails, $details)
    {
        $promoInstanceRepository = $this->em->getRepository('YilinkerCoreBundle:PromoInstance');
        $promoMapRepository = $this->em->getRepository('YilinkerCoreBundle:ProductPromoMap');

        $promoInstance = $promoInstanceRepository->find($details["promoInstanceId"]);

        $promoMapEntity = $promoMapRepository->findOneBy(array(
                            "productUnit" => $details["productUnit"],
                            "promoInstance" => $promoInstance
                        ));

        return  $promoMapEntity->getDiscountedPrice();
    }

    public function getFlashSaleInstancesWithSameTime ()
    {
        $promoInstanceRepository = $this->em->getRepository('YilinkerCoreBundle:PromoInstance');
        $promoInstances = $promoInstanceRepository->getPromoInstanceByType (
                                                        PromoType::FLASH_SALE,
                                                        true,
                                                        Carbon::now()->startOfDay(),
                                                        Carbon::now()->endOfDay(),
                                                        PromoInstanceRepository::ORDER_BY_DATE_START,
                                                        "ASC"
                                                    );
        $promoInstanceArray = array ();

        if (sizeof($promoInstances) > 0) {

            foreach ($promoInstances as $promoInstance) {
                $key = strtotime($promoInstance->getDateStart()->format('H:i'));
                $promoInstanceArray[$key]['promoInstances'][] = $promoInstance;
                $promoInstanceArray[$key]['dateTimeStart'] = $promoInstance->getDateStart();
                $promoInstanceArray[$key]['dateTimeEnd'] = $promoInstance->getDateEnd();
            }

            foreach ($promoInstanceArray as $key => $promoInstanceDetail) {
                $promoInstanceIds = '';

                foreach ($promoInstanceDetail['promoInstances'] as $promoInstance) {
                    $promoInstanceIds .= $promoInstance->getPromoInstanceId() . '-';
                }

                $promoInstanceArray[$key]['promoInstanceIds'] = rtrim($promoInstanceIds, '-');
            }

            $promoInstanceArray = array_values($promoInstanceArray);
        }

        return $promoInstanceArray;
    }

    public function constructPromoInstances($promos)
    {
        $instances = array();
        foreach($promos as $promo){
            array_push($instances, $this->constructPromoInstance($promo));
        }

        return $instances;
    }

    public function constructPromoInstance(
        $promo,
        $productsIncluded = true,
        $isOrdered = false,
        $activeProducts = false
    ){
        $promoInstance = array(
            "promoInstanceId"       => $promo->getPromoInstanceId(),
            "dateStart"             => $promo->getDateStart(),
            "dateEnd"               => $promo->getDateEnd(),
            "title"                 => $promo->getTitle(),
            "isEnabled"             => $promo->getIsEnabled(),
            "promoType"             => $promo->getPromoType()->toArray(),
            "dateCreated"           => $promo->getDateCreated(),
            "advertisement"         => $promo->getAdvertisement(),
            "isImageAdvertisement"  => $promo->getIsImageAdvertisement(),
        );

        if($productsIncluded){
            if($isOrdered){
                $productPromoMaps = $promo->getOrderedProductPromoMap();
            }
            else{
                $productPromoMaps = $promo->getProductPromoMap();
            }

            $promoInstance["productCount"] = count($productPromoMaps);

            $productUnits = array();

            foreach($productPromoMaps as $productPromoMap){
                $productUnit = $productPromoMap->getProductUnit();
		if (!$productUnit) {
		    continue;
		}
                $productUnit->setLocale($this->translatableListener->getCountry());
                $this->em->refresh($productUnit);

                if($productUnit){

                    $product = $productUnit->getProduct();

                    if(
                        $activeProducts &&
                        $product->getStatus() == Product::ACTIVE &&
                        $productUnit->getQuantity() > 0
                    ){

                        $productUnits[$productUnit->getProductUnitId()] = array(
                            "productId"         => $product->getProductId(),
                            "product"           => $product,
                            "name"              => $product->getName(),
                            "sku"               => $productUnit->getSku(),
                            "productUnitId"     => $productUnit->getProductUnitId(),
                            "maxQuantity"       => $productPromoMap->getMaxQuantity(),
                            "price"             => $productUnit->getPrice(),
                            "discountedPrice"   => $productPromoMap->getDiscountedPrice(),
                            "minimumPercentage" => $productPromoMap->getMinimumPercentage(),
                            "maximumPercentage" => $productPromoMap->getMaximumPercentage(),
                            "percentPerHour"    => $productPromoMap->getPercentPerHour(),
                            "quantityRequired"  => $productPromoMap->getQuantityRequired(),
                        );
                    }
                    elseif(!$activeProducts){

                        $productUnits[$productUnit->getProductUnitId()] = array(
                            "productId"         => $product->getProductId(),
                            "productUnitId"     => $productUnit->getProductUnitId(),
                            "name"              => $product->getName(),
                            "sku"               => $productUnit->getSku(),
                            "maxQuantity"       => $productPromoMap->getMaxQuantity(),
                            "price"             => $productUnit->getPrice(),
                            "discountedPrice"   => $productPromoMap->getDiscountedPrice(),
                            "minimumPercentage" => $productPromoMap->getMinimumPercentage(),
                            "maximumPercentage" => $productPromoMap->getMaximumPercentage(),
                            "percentPerHour"    => $productPromoMap->getPercentPerHour(),
                            "quantityRequired"  => $productPromoMap->getQuantityRequired(),
                        );
                    }
                }
            }

            $promoInstance["productUnits"] = $productUnits;
            $promoInstance["productUnitsCount"] = count($productUnits);
        }

        return $promoInstance;
    }
}
