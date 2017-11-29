<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;

class ValidUnitsValidator extends ConstraintValidator
{
    private $productUnitRepository;
    private $productImageRepository;

    public function __construct($productUnitRepository, $productImageRepository){
        $this->productUnitRepository = $productUnitRepository;
        $this->productImageRepository = $productImageRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $units = json_decode($value, true);

        $isDraft = (array_key_exists("isDraft", $options) && $options["isDraft"])? true : false;
        $isCreate = (array_key_exists("isCreate", $options) && $options["isCreate"])? true : false;
        $product = (array_key_exists("product", $options) && $options["product"])? $options["product"] : null;

        if(is_null($units) || !is_array($units)){
            $this->addError(null, $constraint->invalidRequestFormat);
        }
        else{
            if(!$isDraft){

                if(sizeof($units) <= 0){
                    $this->addError(null, $constraint->mustHaveAtleastOneUnit);
                }

                if($isCreate){
                    $userSkus = $this->productUnitRepository->getAllSkuByUser($options["user"]);
                }
                else{
                    $userSkus = $this->productUnitRepository->getAllSkuByUser($options["user"], $product);
                }

                $productAttributes = array();
                $productSkus = array();

                foreach($units as $unit){
                    if(array_key_exists("sku", $unit) && $unit["sku"]){
                        $this->validateCombinations($productAttributes, $unit, $constraint, sizeof($units));
                        $this->validateSku($userSkus, $productSkus, $unit, $constraint, $isDraft);
                        $this->validateDimensions($unit, $constraint);
                    }
                    else{
                        $this->addError(null, $constraint->skuRequired);
                    }
                }

                $this->validateImages($units, $product, $isCreate, $constraint);
            }
            else{
                if(sizeof($units) > 0){
                    $userSkus = $this->productUnitRepository->getAllSkuByUser($options["user"]);
                    $productSkus = array();

                    foreach($units as $unit){
                        if(array_key_exists("sku", $unit) && $unit["sku"]){
                            $this->validateSku($userSkus, $productSkus, $unit, $constraint, $isDraft);
                        }
                    }
                }
            }
        }
    }

    private function validateImages($units, $product, $isCreate, $constraint)
    {
        if(sizeof($units) > 1){

            if(!$isCreate){
                $productImages = $this->productImageRepository->getImageLocationsByProduct($product);
            }
            else{
                $productImages = array();
            }

            foreach ($units as $unit) {
                if(
                    array_key_exists("images", $unit) &&
                    sizeof($unit["images"]) > 0
                ){
                    foreach($unit["images"] as $image){
                        $file = "assets/images/uploads/".
                                ProductImage::PRODUCT_FOLDER.
                                "temp/".$image["name"];

                        if(!file_exists($file) && !in_array($image["name"], $productImages)){
                            $this->addError($unit["sku"], $constraint->imageDoesntExists);
                        }
                    }
                }
            }
        }
    }

    private function validateDimensions($unit, $constraint)
    {
        $length = array_key_exists("length", $unit)? floatval($unit["length"]) : 0;
        $width = array_key_exists("width", $unit)? floatval($unit["width"]) : 0;
        $weight = array_key_exists("weight", $unit)? floatval($unit["weight"]) : 0;
        $height = array_key_exists("height", $unit)? floatval($unit["height"]) : 0;

        if(
            $length <= 0 ||
            $width <= 0 ||
            $weight <= 0 ||
            $height <= 0
        ){
            $this->addError($unit["sku"], $constraint->invalidDimensions);
        }
    }

    private function validateSku(&$userSkus, &$productSkus, $unit, $constraint, $isDraft = false)
    {
        if(
            $isDraft == false &&
            !in_array(trim($unit["sku"]), $productSkus) &&
            !in_array(trim($unit["sku"]), $userSkus)
        ){
            array_push($productSkus, trim($unit["sku"]));
        }
        elseif(
            $isDraft == true &&
            $unit["sku"] != "" &&
            !in_array(trim($unit["sku"]), $productSkus) &&
            !in_array(trim($unit["sku"]), $userSkus)
        ){
            array_push($productSkus, trim($unit["sku"]));
        }
        else{
            if(
                $isDraft == false ||
                ($isDraft == true && $unit["sku"] != "")
            ){
                $this->addError($unit["sku"], $constraint->duplicateSku);
            }
        }
    }

    private function validateCombinations(&$productAttributes, $unit, $constraint, $unitsCount)
    {
        $hasCombination = $this->checkIfHasCombination($unit, $unitsCount);

        if(!$hasCombination){
            $this->addError($unit["sku"], $constraint->mustHaveAtleastOneCombination);
        }
        else{
            if(array_key_exists("attributes", $unit)){
                $validCombinationFormat = $this->checkIfValidCombinationFormat($unit["attributes"]);

                if(!$validCombinationFormat){
                    $this->addError(null, $constraint->invalidCombinationFormat);
                }

                $attribute = json_encode($unit["attributes"]);
                if(!in_array($attribute, $productAttributes)){
                    array_push($productAttributes, $attribute);
                }
                else{
                    $this->addError(null, $constraint->duplicateAttribute);
                }
            }
        }
    }

    private function checkIfValidCombinationFormat($attributes)
    {
        if(is_array($attributes)){
            foreach($attributes as $attribute){
                if(
                    !is_array($attribute) &&
                    !array_key_exists("name", $attribute) &&
                    !array_key_exists("value", $attribute)
                ){
                    return false;
                }
            }
        }
        else{
            return false;
        }

        return true;
    }

    private function checkIfHasCombination($unit, $unitsCount)
    {
        if(
            array_key_exists("attributes", $unit) &&
            sizeof($unit["attributes"]) > 0
        ){
            return true;
        }

        if(!array_key_exists("attributes", $unit) && $unitsCount == 1){
            return true;
        }

        return false;
    }

    private function addError($parameter = null, $message)
    {
        if(!is_null($parameter)){
            $this->context
                 ->buildViolation($message)
                 ->setParameter('%string%', $parameter)
                 ->addViolation();
        }
        else{
            $this->context
                 ->buildViolation($message)
                 ->addViolation();
        }
    }
}
