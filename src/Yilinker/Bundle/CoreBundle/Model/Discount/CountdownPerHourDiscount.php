<?php

namespace Yilinker\Bundle\CoreBundle\Model\Discount;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;

class CountdownPerHourDiscount
{
    /**
     * @var ProductUnit $productUnit
     */
    private $productUnit;

    /**
     * @var PromoInstance $promoInstance
     */
    private $promoInstance;

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param ProductUnit $productUnit
     * @return $this
     */
    public function setProductUnit($productUnit)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * @return PromoInstance
     */
    public function getPromoInstance()
    {
        return $this->promoInstance;
    }

    /**
     * @param PromoInstance $promoInstance
     * @return $this
     */
    public function setPromoInstance($promoInstance)
    {
        $this->promoInstance = $promoInstance;

        return $this;
    }

    public function apply($productPromoMap)
    {
        $promoInstance = &$this->promoInstance;
        $productUnit = &$this->productUnit;

        if($promoInstance instanceof PromoInstance){
            $this->applyByObject($promoInstance, $productUnit, $productPromoMap);
        }
        else{
            $this->applyByArray($promoInstance, $productUnit, $productPromoMap);
        }

        return $this;
    }

    private function applyByArray(&$promoInstance, &$productUnit, $productPromoMap)
    {
        $dateNow = Carbon::now();
        $dateStart = Carbon::instance($promoInstance["dateStart"]);
        $dateEnd = Carbon::instance($promoInstance["dateEnd"]);

        if($promoInstance["isEnabled"] && $dateNow->between($dateStart, $dateEnd)){


            $maxPercentage = floatval($promoInstance["maxPercentage"]);
            $minPercentage = floatval($promoInstance["minPercentage"]);
            $percentPerHour = floatval($promoInstance["percentPerHour"]);

            $hours = $dateNow->diffInHours($dateStart);

            $computedPercentage = ($maxPercentage - ($percentPerHour * $hours));
            $discount = 1 - ($computedPercentage/100);
            $minPercentageDiscount = 1 - (floatval($minPercentage)/100);

            $productUnit["promoInstance"] = $promoInstance;
            $productUnit["hasCurrentPromo"] = true;
            $productUnit["isBulkDiscount"] = false;

            if($computedPercentage > $minPercentage){
                $discountedPrice = $productUnit["discountedPrice"] * $discount;
                $productUnit["promotions"] = $promoInstance["advertisement"];
                $productUnit["appliedBaseDiscountPrice"] = $productUnit["discountedPrice"];
                $productUnit["appliedDiscountPrice"] = number_format($discountedPrice, 2, '.', '');
            }
            else{
                $discountedPrice = $productUnit["discountedPrice"] * $minPercentageDiscount;
                $productUnit["promotions"] = $promoInstance["advertisement"];
                $productUnit["appliedBaseDiscountPrice"] = $productUnit["discountedPrice"];
                $productUnit["appliedDiscountPrice"] = number_format($discountedPrice, 2, '.', '');
            }
        }
        elseif($promoInstance["isEnabled"] && $dateNow->lt($dateStart)){

            if(!array_key_exists("upcomingPromoInstances", $productUnit)){
                $productUnit["upcomingPromoInstances"] = array();
            }

            $productUnit["hasUpcomingPromo"] = true;
            array_push($productUnit["upcomingPromoInstances"], $promoInstance);
        }
    }

    private function applyByObject(&$promoInstance, &$productUnit, $productPromoMap)
    {
        $maxQuantity = $productPromoMap->getMaxQuantity();
        $currentQuantity = 0;

        if($productUnit){

            $dateNow = Carbon::now();
            $dateStart = Carbon::instance($promoInstance->getDateStart());
            $dateEnd = Carbon::instance($promoInstance->getDateEnd());

            if(
                $promoInstance->getIsEnabled() &&
                $dateNow->between($dateStart, $dateEnd)
            ){

                $productUnit->setPromoInstance($promoInstance);
                $productUnit->setHasCurrentPromo(true);
                $productUnit->setIsBulkDiscount(false);

                $maxPercentage = floatval($productPromoMap->getMaximumPercentage());
                $minPercentage = floatval($productPromoMap->getMinimumPercentage());
                $percentPerHour = floatval($productPromoMap->getPercentPerHour());

                $hours = $dateNow->diffInHours($dateStart);

                $computedPercentage = ($maxPercentage - ($percentPerHour * $hours));
                $discount = 1 - ($computedPercentage/100);
                $minPercentageDiscount = 1 - (floatval($minPercentage)/100);

                if($computedPercentage > $minPercentage){
                    $discountedPrice = $productUnit->getPrice() * $discount;
                    $productUnit->addPromotions($promoInstance->getAdvertisement());
                    $productUnit->setAppliedBaseDiscountPrice($productUnit->getPrice());
                    $productUnit->setAppliedDiscountPrice(number_format($discountedPrice, 2, '.', ''));
                }else{
                    $discountedPrice = $productUnit->getPrice() * $minPercentageDiscount;
                    $productUnit->addPromotions($promoInstance->getAdvertisement());
                    $productUnit->setAppliedBaseDiscountPrice($productUnit->getPrice());
                    $productUnit->setAppliedDiscountPrice(number_format($discountedPrice, 2, '.', ''));
                }
            }
            elseif($promoInstance->getIsEnabled() && $dateNow->lt($dateStart)){

                $productUnit->setHasUpcomingPromo(true);
                $productUnit->addUpcomingPromoInstances($promoInstance);
            }
        }
    }
}
