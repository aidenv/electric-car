<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\PromoType;

use Carbon\Carbon;

class ValidPromoUnitsValidator extends ConstraintValidator
{
    private $em;
    private $productUnitRepository;
    private $promoTypeRepository;
    private $translatableListener;

    public function __construct(
        $em,
        $productUnitRepository,
        $promoTypeRepository,
        $translatableListener
    ){
        $this->em = $em;
        $this->productUnitRepository = $productUnitRepository;
        $this->promoTypeRepository = $promoTypeRepository;
        $this->translatableListener = $translatableListener;
    }

    public function validate($value, Constraint $constraint)
    {
        $error = $constraint->message;
        $options = $constraint->options;

        $isValid = true;

        $promoTypeId = array_key_exists("promoType", $options)? $options["promoType"] : null;
        $products = array_key_exists("products", $options)? $options["products"] : array();
        $excludedInstance = array_key_exists("excludedInstance", $options)? $options["excludedInstance"] : null;
        $dateStart = array_key_exists("dateStart", $options)? $options["dateStart"] : null;
        $dateEnd = array_key_exists("dateEnd", $options)? $options["dateEnd"] : null;

        $promoType = $this->promoTypeRepository->find($promoTypeId);

        if(!$promoType){
            $isValid = false;
            $error = $constraint->invalidPromoType;
        }

        if(empty($products)){
            $isValid = false;
            $error = $constraint->productsIsRequired;
        }

        switch ($promoType->getPromoTypeId()) {
            case PromoType::BULK:
                extract($this->validateBulkDiscount($products, $options));

                if(isset($invalidQuantityRequiredItems) && !empty($invalidQuantityRequiredItems)){
                    $this->addErrors($constraint->invalidQuantityRequired, implode(", ", $invalidQuantityRequiredItems));
                }

                if(isset($invalidDiscountedPriceItems) && !empty($invalidDiscountedPriceItems)){
                    $this->addErrors($constraint->invalidDiscountedPrice, implode(", ", $invalidDiscountedPriceItems));
                }

                if(isset($maxQuantityRequiredItems) && !empty($maxQuantityRequiredItems)){
                    $this->addErrors($constraint->maxQuantityRequired, implode(", ", $maxQuantityRequiredItems));
                }

                if(isset($maxQuantityInvalidItems) && !empty($maxQuantityInvalidItems)){
                    $this->addErrors($constraint->maxQuantityInvalid, implode(", ", $maxQuantityInvalidItems));
                }

                if(isset($invalidSetDiscountedPrice) && !empty($invalidSetDiscountedPrice)){
                    $this->addErrors($constraint->invalidSetDiscountedPrice, implode(", ", $invalidSetDiscountedPrice));
                }

                break;
            case PromoType::PER_HOUR:
                extract($this->validatePerHourDiscount($products, $options));

                if(isset($invalidMinPercentItems) && !empty($invalidMinPercentItems)){
                    $this->addErrors($constraint->invalidMinPercent, implode(", ", $invalidMinPercentItems));
                }

                if(isset($invalidMaxPercentItems) && !empty($invalidMaxPercentItems)){
                    $this->addErrors($constraint->invalidMaxPercent, implode(", ", $invalidMaxPercentItems));
                }

                if(isset($invalidPercentPerHourItems) && !empty($invalidPercentPerHourItems)){
                    $this->addErrors($constraint->invalidPercentPerHour, implode(", ", $invalidPercentPerHourItems));
                }

                if(isset($maxQuantityRequiredItems) && !empty($maxQuantityRequiredItems)){
                    $this->addErrors($constraint->maxQuantityRequired, implode(", ", $maxQuantityRequiredItems));
                }

                if(isset($maxQuantityInvalidItems) && !empty($maxQuantityInvalidItems)){
                    $this->addErrors($constraint->maxQuantityInvalid, implode(", ", $maxQuantityInvalidItems));
                }

                break;
            default:
                extract($this->validateDefaultDiscount($products, $options));

                if(isset($invalidDiscountedPriceItems) && !empty($invalidDiscountedPriceItems)){
                    $this->addErrors($constraint->invalidDiscountedPrice, implode(", ", $invalidDiscountedPriceItems));
                }

                if(isset($maxQuantityRequiredItems) && !empty($maxQuantityRequiredItems)){
                    $this->addErrors($constraint->maxQuantityRequired, implode(", ", $maxQuantityRequiredItems));
                }

                if(isset($maxQuantityInvalidItems) && !empty($maxQuantityInvalidItems)){
                    $this->addErrors($constraint->maxQuantityInvalid, implode(", ", $maxQuantityInvalidItems));
                }

                if(isset($invalidSetDiscountedPrice) && !empty($invalidSetDiscountedPrice)){
                    $this->addErrors($constraint->invalidSetDiscountedPrice, implode(", ", $invalidSetDiscountedPrice));
                }

                break;
        }

        $isValid = $this->validateProductUnits($products, $excludedInstance, $dateStart, $dateEnd, $constraint);

        if(!$isValid){
            $this->context
                 ->buildViolation($error)
                 ->addViolation();
        }
    }

    private function validateProductUnits($products, $excludedInstance, $dateStart, $dateEnd, $constraint)
    {
        $errors = array();

        $productUnitIds = array();
        foreach ($products as $product){
            array_push($productUnitIds, $product["productUnitId"]);
        }

        $productUnits = $this->productUnitRepository->getPromoUnitsIn(
                            $productUnitIds,
                            $excludedInstance,
                            Carbon::createFromFormat("m-d-Y H:i:s", $dateStart),
                            Carbon::createFromFormat("m-d-Y H:i:s", $dateEnd)
                        );

        $invalidProducts = array();

        foreach($productUnits as $productUnit){

            $productPromoMap = $productUnit->getProductPromoMaps()->first();
            $promoInstance = $productPromoMap->getPromoInstance();

            array_push($invalidProducts, $productUnit);
            $this->addErrors($constraint->unitInActivePromo, $productUnit->getSku(), $promoInstance->getTitle());
        }

        return $invalidProducts? false : true;
    }

    private function validateDefaultDiscount($products, $options)
    {
        $errors = array();

        $productUnitIds = array();
        foreach ($products as $product){
            array_push($productUnitIds, $product["productUnitId"]);
        }

        $productUnits = $this->productUnitRepository->loadProductUnitsIn($productUnitIds);

        $errors["invalidDiscountedPriceItems"] = array();
        $errors["maxQuantityRequiredItems"] = array();
        $errors["maxQuantityInvalidItems"] = array();
        $errors["invalidSetDiscountedPrice"] = array();

        foreach ($products as $product) {

            $productUnitId = $product["productUnitId"];

            if(array_key_exists($productUnitId, $productUnits)){

                $productUnits[$productUnitId]->setLocale($this->translatableListener->getCountry());
                $this->em->refresh($productUnits[$productUnitId]);

                if(floatval($product["discountedPrice"]) <= 0){
                    array_push($errors["invalidDiscountedPriceItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["maxQuantity"]) <= 0){
                    array_push($errors["maxQuantityRequiredItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["maxQuantity"]) > $productUnits[$productUnitId]->getQuantity()){
                    array_push($errors["maxQuantityInvalidItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["discountedPrice"]) > $productUnits[$productUnitId]->getPrice()){
                    array_push($errors["invalidSetDiscountedPrice"], $productUnits[$productUnitId]->getSku());
                }
            }
        }

        return $errors;
    }

    private function validateBulkDiscount($products, $options)
    {
        $errors = array();

        $productUnitIds = array();
        foreach ($products as $product){
            array_push($productUnitIds, $product["productUnitId"]);
        }

        $productUnits = $this->productUnitRepository->loadProductUnitsIn($productUnitIds);


        $errors["invalidDiscountedPriceItems"] = array();
        $errors["invalidQuantityRequiredItems"] = array();
        $errors["maxQuantityRequiredItems"] = array();
        $errors["maxQuantityInvalidItems"] = array();

        foreach ($products as $product) {

            $productUnitId = $product["productUnitId"];

            if(array_key_exists($productUnitId, $productUnits)){

                if(floatval($product["discountedPrice"]) < 1){
                    array_push($errors["invalidDiscountedPriceItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["quantityRequired"]) < 1){
                    array_push($errors["invalidQuantityRequiredItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["maxQuantity"]) <= 0){
                    array_push($errors["maxQuantityRequiredItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["maxQuantity"]) > $productUnits[$productUnitId]->getQuantity()){
                    array_push($errors["maxQuantityInvalidItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["discountedPrice"]) > $productUnits[$productUnitId]->getPrice()){
                    array_push($errors["invalidSetDiscountedPrice"], $productUnits[$productUnitId]->getSku());
                }
            }
        }

        return $errors;
    }

    private function validatePerHourDiscount($products, $options)
    {
        $errors = array();

        $productUnitIds = array();
        foreach ($products as $product){
            array_push($productUnitIds, $product["productUnitId"]);
        }

        $productUnits = $this->productUnitRepository->loadProductUnitsIn($productUnitIds);

        $errors["invalidMinPercentItems"] = array();
        $errors["invalidMaxPercentItems"] = array();
        $errors["invalidPercentPerHourItems"] = array();
        $errors["maxQuantityRequiredItems"] = array();
        $errors["maxQuantityInvalidItems"] = array();

        foreach ($products as $product) {

            $productUnitId = $product["productUnitId"];

            if(array_key_exists($productUnitId, $productUnits)){

                if(((int)$product["maxQuantity"]) > $productUnits[$productUnitId]->getQuantity()){
                    array_push($errors["maxQuantityInvalidItems"], $productUnits[$productUnitId]->getSku());
                }

                if(((int)$product["maxQuantity"]) <= 0){
                    array_push($errors["maxQuantityRequiredItems"], $productUnits[$productUnitId]->getSku());
                }

                if(floatval($product["minimumPercentage"]) <= 0 || floatval($product["minimumPercentage"]) >= 100){
                    array_push($errors["invalidMinPercentItems"], $productUnits[$productUnitId]->getSku());
                }

                if(floatval($product["maximumPercentage"]) <= 0 || floatval($product["maximumPercentage"]) >= 100){
                    array_push($errors["invalidMaxPercentItems"], $productUnits[$productUnitId]->getSku());
                }

                if(floatval($product["percentPerHour"]) <= 0 || floatval($product["percentPerHour"]) >= 100){
                    array_push($errors["invalidPercentPerHourItems"], $productUnits[$productUnitId]->getSku());
                }
            }
        }

        return $errors;
    }

    private function addErrors($message, $parameter, $promo = null)
    {
        if(!is_null($promo)){
            $this->context
                     ->buildViolation($message)
                     ->setParameter('%string%', $parameter)
                     ->setParameter('%promo%', $promo)
                     ->addViolation();
        }
        else{
            $this->context
                 ->buildViolation($message)
                 ->setParameter('%string%', $parameter)
                 ->addViolation();
        }
    }
}
