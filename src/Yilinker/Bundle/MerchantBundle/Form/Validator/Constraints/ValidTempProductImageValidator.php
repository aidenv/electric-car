<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;

class ValidTempProductImageValidator extends ConstraintValidator
{
    private $productImageRepository;

    public function __construct($productImageRepository){
        $this->productImageRepository = $productImageRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $images = json_decode($value, true);

        $isDraft = (array_key_exists("isDraft", $options) && $options["isDraft"])? true : false;
        $isCreate = (array_key_exists("isCreate", $options) && $options["isCreate"])? true : false;

        if(!$isDraft){
            try{
                if(is_null($images) || !is_array($images)){
                    throw new YilinkerException($constraint->invalidRequestFormat);
                }

                $hasPrimary = $this->checkIfHasPrimary($images);

                if(!$hasPrimary){
                    throw new YilinkerException($constraint->mustHaveAtleastOnePrimary);
                }

                $hasOnlyOnePrimary = $this->checkIfHasOnlyOnePrimary($images);

                if(!$hasOnlyOnePrimary){
                    throw new YilinkerException($constraint->hasMultiplePrimary);
                }

                if($isCreate){
                    $imagesExistOnTemp = $this->checkIfImageExistInTemp($images);

                    if(!$imagesExistOnTemp){
                        throw new YilinkerException($constraint->message, 1);
                    }
                }
                else{
                    $productImages = $this->productImageRepository->getImageLocationsByProduct($options["product"]);

                    $imagesExistOnTemp = $this->checkIfImageExistInTempAndDB($images, $productImages);

                    if(!$imagesExistOnTemp){
                        throw new YilinkerException($constraint->message, 1);
                    }
                }
            }
            catch(YilinkerException $e){
                $this->context
                     ->buildViolation($e->getMessage())
                     ->addViolation();
            }
        }
    }

    private function checkIfHasPrimary($images)
    {
        foreach($images as $image){
            if(!is_array($image)){
                return false;
            }

            if(array_key_exists("isPrimary", $image) && $image["isPrimary"]){
                return true;
            }
        }

        return false;
    }

    private function checkIfHasOnlyOnePrimary($images)
    {
        $hasPrimary = false;
        foreach($images as $image){

            if(!is_array($image)){
                return false;
            }

            if($hasPrimary == true && $image["isPrimary"]){
                return false;
            }

            if(!$hasPrimary && $image["isPrimary"]){
                $hasPrimary = true;
            }
        }

        return true;
    }

    private function checkIfImageExistInTemp($images)
    {
        try{

            foreach($images as $image){
                $file = "assets/images/uploads/".ProductImage::PRODUCT_FOLDER."temp/".$image["name"];

                if(!file_exists($file)){
                    throw new YilinkerException("File doesnt exists");
                }
            }
        }
        catch(YilinkerException $e){
            return false;
        }

        return true;
    }

    private function checkIfImageExistInTempAndDB($images, $productImages)
    {
        try{

            foreach($images as $image){
                $file = "assets/images/uploads/".ProductImage::PRODUCT_FOLDER."temp/".$image["name"];

                if(!file_exists($file) && !in_array($image["name"], $productImages)){
                    throw new YilinkerException("File doesnt exists");
                }
            }
        }
        catch(YilinkerException $e){
            return false;
        }

        return true;
    }
}