<?php

namespace Yilinker\Bundle\CoreBundle\Model;

use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Model\Discount\FixedDiscount;
use Yilinker\Bundle\CoreBundle\Model\Discount\CountdownPerHourDiscount;
use Yilinker\Bundle\CoreBundle\Model\Discount\BulkDiscount;

class Discount
{
    /**
     * @var $product
     */
    private $product;

    /**
     * @var $productUnit
     */
    private $productUnit;

    /**
     * @var $currentQuantity
     */
    private $currentQuantity = 1;

    /**
     * @var array
     */
    private $promotions = array();

    /**
     * @param mixed $product
     * @return $this
     */
    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $productUnit
     * @return $this
     */
    public function setProductUnit($productUnit)
    {
        $this->productUnit = $productUnit;
        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
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
     * @return $this
     */
    public function setCurrentQuantity($currentQuantity)
    {
        $this->currentQuantity = $currentQuantity;
        return $this;
    }

    /**
     * @return array
     */
    public function getPromotions()
    {
        return $this->promotions;
    }

    /**
     * @return $this
     */
    public function setFirstUnit()
    {
        $this->productUnit = $this->product->getUnits()->first();
        return $this;
    }

    /**
     * @return $this
     */
    public function setDefaultUnit()
    {
        $this->productUnit = $this->product->getDefaultUnit();
        return $this;
    }

    /**
     * @return $this
     */
    public function setLastUnit()
    {
        $this->productUnit = $this->product->getUnits()->last();
        return $this;
    }

    /**
     * @return $this
     */
    public function apply()
    {
        $productUnit = &$this->productUnit;
        if($productUnit instanceof ProductUnit){
            $this->applyByObject($productUnit);
        }
        else{
            $this->applyByArray($productUnit);
        }

        return $this;
    }

    private function applyByArray(&$productUnit)
    {
        if($productUnit && array_key_exists('promoInstance', $productUnit)){
            $promoInstance = $productUnit["promoInstance"];

            foreach($promoInstance as $promo){
                $instance = $promo;
                $promoTypeId = $instance["promoType"];

                $discount = null;
                switch($promoTypeId){
                    case 2:
                        $discount = new BulkDiscount();
                        $discount->setCurrentQuantity($this->currentQuantity);
                        break;
                    case 3:
                        $discount = new CountdownPerHourDiscount();
                        break;
                    default:
                        $discount = new FixedDiscount();
                        break;
                }

                $discount->setProductUnit($productUnit)->setPromoInstance($instance)->apply($promo);

                $productUnit = $discount->getProductUnit();
            }
        }
    }

    private function applyByObject(&$productUnit)
    {
        if($productUnit){
            $promoMaps = $this->productUnit->getProductPromoMaps();

            foreach($promoMaps as $productPromoMap){
                
                if ($productPromoMap->getPromoInstance()) {
                    $promoInstance = $productPromoMap->getPromoInstance();
                    $promoTypeId = $promoInstance->getPromoType()->getPromoTypeId();

                    $discount = null;
                    switch($promoTypeId){
                        case 2:
                            $discount = new BulkDiscount();
                            $discount->setCurrentQuantity($this->currentQuantity);
                            break;
                        case 3:
                            $discount = new CountdownPerHourDiscount();
                            break;
                        default:
                            $discount = new FixedDiscount();
                            break;
                    }

                    $discount->setProductUnit($productUnit)
                             ->setPromoInstance($promoInstance)
                             ->apply($productPromoMap);

                    $productUnit = $discount->getProductUnit();

                    array_push($this->promotions, $promoInstance->getAdvertisement());
                }
            }
        }
    }
}
