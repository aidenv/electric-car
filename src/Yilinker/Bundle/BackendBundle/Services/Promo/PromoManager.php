<?php

namespace Yilinker\Bundle\BackendBundle\Services\Promo;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;
use Yilinker\Bundle\CoreBundle\Entity\ProductPromoMap;

/**
 * Class PromoManager
 */
class PromoManager
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct (EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createProductPromoMaps(PromoInstance $promoInstance, $products)
    {
        $productUnitRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnit");

        $productUnitIds = array();
        foreach($products as $product){
            array_push($productUnitIds, $product["productUnitId"]);
        }

        $productUnits = $productUnitRepository->loadProductUnitsIn($productUnitIds);

        foreach($products as $product){

            if(array_key_exists($product["productUnitId"], $productUnits)){

                $productPromoMap = new ProductPromoMap();
                $productPromoMap->setProductUnit($productUnits[$product["productUnitId"]])
                                ->setPromoInstance($promoInstance);

                array_key_exists("discountedPrice", $product)? 
                    $productPromoMap->setDiscountedPrice($product["discountedPrice"]) : null;

                array_key_exists("minimumPercentage", $product)? 
                    $productPromoMap->setMinimumPercentage(floatval($product["minimumPercentage"])) : null;

                array_key_exists("maximumPercentage", $product)? 
                    $productPromoMap->setMaximumPercentage(floatval($product["maximumPercentage"])) : null;

                array_key_exists("percentPerHour", $product)? 
                    $productPromoMap->setPercentPerHour(floatval($product["percentPerHour"])) : null;

                array_key_exists("quantityRequired", $product)? 
                    $productPromoMap->setQuantityRequired((int)$product["quantityRequired"]) : null;

                array_key_exists("maxQuantity", $product)? 
                    $productPromoMap->setMaxQuantity((int)$product["maxQuantity"]) : null;

                $promoInstance->addProductPromoMap($productPromoMap);
                $productUnits[$product["productUnitId"]]->addProductPromoMap($productPromoMap);
                $this->em->persist($productPromoMap);
            }
        }

        $this->em->flush();
    }

    public function updateProductPromoMaps(PromoInstance $promoInstance, $products)
    {
        $productUnitRepository = $this->em->getRepository("YilinkerCoreBundle:ProductUnit");

        $productUnitIds = array();
        foreach($products as $product){
            array_push($productUnitIds, $product["productUnitId"]);
        }

        $productUnits = $productUnitRepository->loadProductUnitsIn($productUnitIds);

        $productPromoMaps = $promoInstance->getProductPromoMap();
        $indexesToRemove = array();

        foreach($productPromoMaps as $productPromoMap){
            $productUnitId = $productPromoMap->getProductUnit()->getProductUnitId();
            if(!in_array($productUnitId, $productUnitIds)){
                $productPromoMaps->removeElement($productPromoMap);
                $this->em->remove($productPromoMap);
            }
            else{

                if(
                    array_key_exists("discountedPrice", $products[$productUnitId]) &&
                    $products[$productUnitId]["discountedPrice"]
                ){
                    $productPromoMap->setDiscountedPrice($products[$productUnitId]["discountedPrice"]);
                }

                if(
                    array_key_exists("minimumPercentage", $products[$productUnitId]) &&
                    $products[$productUnitId]["minimumPercentage"]
                ){
                    $productPromoMap->setMinimumPercentage($products[$productUnitId]["minimumPercentage"]);
                }

                if(
                    array_key_exists("maximumPercentage", $products[$productUnitId]) &&
                    $products[$productUnitId]["maximumPercentage"]
                ){
                    $productPromoMap->setMaximumPercentage($products[$productUnitId]["maximumPercentage"]);
                }

                if(
                    array_key_exists("percentPerHour", $products[$productUnitId]) &&
                    $products[$productUnitId]["percentPerHour"]
                ){
                    $productPromoMap->setPercentPerHour($products[$productUnitId]["percentPerHour"]);
                }

                if(
                    array_key_exists("quantityRequired", $products[$productUnitId]) &&
                    $products[$productUnitId]["quantityRequired"]
                ){
                    $productPromoMap->setQuantityRequired($products[$productUnitId]["quantityRequired"]);
                }

                if(
                    array_key_exists("maxQuantity", $products[$productUnitId]) &&
                    $products[$productUnitId]["maxQuantity"]
                ){
                    $productPromoMap->setMaxQuantity($products[$productUnitId]["maxQuantity"]);
                }

                foreach($products as $index => $product){
                    if($product["productUnitId"] == $productUnitId){
                        unset($products[$index]);
                    }
                }
            }
        }

        $this->createProductPromoMaps($promoInstance, $products);
    }

    public function changePromoInstanceStatus($promoInstanceIds, $status)
    {
        $promoInstanceRepository = $this->em->getRepository("YilinkerCoreBundle:PromoInstance");
        $promoInstances = $promoInstanceRepository->getPromoInstanceIn($promoInstanceIds);

        foreach($promoInstances as $promoInstance){
            $promoInstance->setIsEnabled(filter_var($status, FILTER_VALIDATE_BOOLEAN));
        }

        $this->em->flush();
    }

    public function deletePromoInstance($promoInstanceIds)
    {
        $promoInstanceRepository = $this->em->getRepository("YilinkerCoreBundle:PromoInstance");
        $promoInstances = $promoInstanceRepository->getPromoInstanceIn($promoInstanceIds);

        foreach($promoInstances as $promoInstance){
            $productPromoMap = $promoInstance->getProductPromoMap();

            foreach($productPromoMap as $map){
                $this->em->remove($map);
            }

            $this->em->remove($promoInstance);
        }

        $this->em->flush();
    }
}
