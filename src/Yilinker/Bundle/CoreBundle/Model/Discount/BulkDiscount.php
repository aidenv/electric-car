<?php

namespace Yilinker\Bundle\CoreBundle\Model\Discount;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;

class BulkDiscount
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
     * @var $currentQuantity
     */
    private $currentQuantity;

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

    /**
     * @return mixed
     */
    public function getCurrentQuantity()
    {
        return $this->currentQuantity;
    }

    /**
     * @param mixed $currentQuantity
     */
    public function setCurrentQuantity($currentQuantity)
    {
        $this->currentQuantity = $currentQuantity;
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

            $currentQuantity = $this->currentQuantity;
            $requiredQuantity = $promoInstance["quantityRequired"];

            $productUnit["promoInstance"] = $promoInstance;
            $productUnit["isBulkDiscount"] = true;
            $productUnit["hasCurrentPromo"] = true;

            if($currentQuantity >= $requiredQuantity){
                $discount = 1 - (floatval($promoInstance["maxPercentage"])/100);
                $discountedPrice = $productUnit["discountedPrice"] * $discount;


                $productUnit["promotions"][] = $promoInstance["advertisement"];
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
            $orderProducts = $productUnit->getProduct()->filterOrderProductsByDate(
                                $promoInstance->getDateStart(),
                                $promoInstance->getDateEnd()
                            );

            foreach ($orderProducts as $orderProduct){
                $isBought = $orderProduct->hasStatus(array(
                                    OrderProductStatus::PAYMENT_CONFIRMED,
                                    OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED
                                ),
                                $promoInstance->getDateStart(),
                                $promoInstance->getDateEnd()
                            );

                if($isBought){
                    $currentQuantity += $orderProduct->getQuantity();
                }
            }

            $dateNow = Carbon::now();
            $dateStart = Carbon::instance($promoInstance->getDateStart());
            $dateEnd = Carbon::instance($promoInstance->getDateEnd());

            if(
                $promoInstance->getIsEnabled() &&
                $dateNow->between($dateStart, $dateEnd) &&
                $currentQuantity < $maxQuantity
            ){

                $currentQuantity = $this->currentQuantity;
                $requiredQuantity = $productPromoMap->getQuantityRequired();

                $productUnit->setPromoInstance($promoInstance);
                $productUnit->setIsBulkDiscount(true);
                $productUnit->setHasCurrentPromo(true);

                if($currentQuantity >= $requiredQuantity){
                    $productUnit->addPromotions($promoInstance->getAdvertisement());
                    $productUnit->setAppliedBaseDiscountPrice($productUnit->getPrice());
                    $productUnit->setAppliedDiscountPrice(number_format($productPromoMap->getDiscountedPrice(), 2, '.', ''));
                }
            }
            elseif($promoInstance->getIsEnabled() && $dateNow->lt($dateStart)){

                $productUnit->setHasUpcomingPromo(true);
                $productUnit->addUpcomingPromoInstances($promoInstance);
            }
        }
    }
}
