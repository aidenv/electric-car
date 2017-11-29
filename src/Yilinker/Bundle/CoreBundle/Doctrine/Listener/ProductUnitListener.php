<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Model\Discount;

class ProductUnitListener
{

    private $serviceContainer;

    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function applyDiscountHandler(ProductUnit $productUnit)
    {
        $productUnit = $this->setUnitLocale($productUnit);
    }

    public function checkInWishlist(ProductUnit $productUnit)
    {
        $productUnit = $this->setUnitLocale($productUnit);
        if ($this->serviceContainer && $this->serviceContainer->has('yilinker_front_end.service.cart')) {
            $requestStack = $this->serviceContainer->get('request_stack');
            if ($requestStack->getCurrentRequest()) {
                $cartService = $this->serviceContainer->get('yilinker_front_end.service.cart');
                $unitId = $productUnit->getProductUnitId();
                $inWishlist = $cartService->inWishlist($unitId);
                $productUnit->inWishlist($inWishlist);
            }
        }
    }

    private function setUnitLocale(ProductUnit $productUnit)
    {
        if($this->serviceContainer && is_null($productUnit->getLocale())){
            $em = $this->serviceContainer->get("doctrine.orm.entity_manager");
            $translationListener = $this->serviceContainer->get("yilinker_core.translatable.listener");

            $productUnit->setLocale($translationListener->getCountry());

            $em->refresh($productUnit);
            $discount = new Discount();
            $discount->setProductUnit($productUnit)->apply();
        }

        return $productUnit;
    }
}
