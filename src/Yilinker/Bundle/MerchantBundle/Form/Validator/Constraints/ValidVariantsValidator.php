<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;

class ValidVariantsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $variants = json_decode($value, true);

        $product = (array_key_exists("product", $options) && $options["product"])? $options["product"] : null;
        $defaultValue = (array_key_exists("defaultValue", $options) && $options["defaultValue"])? $options["defaultValue"] : null;

        try{
            if(!is_array($variants)){
                throw new YilinkerException($constraint->message);
            }
            else{
                if(
                    array_key_exists("productVariants", $defaultValue) &&
                    sizeof($defaultValue["productVariants"]) == sizeof($variants)
                ){
                    foreach($variants as $variant){
                        $isValidAttributeName = false;
                        foreach($defaultValue["productVariants"] as $defaultVariant){
                            if($variant["id"] == $defaultVariant["id"]){
                                $isValidAttributeName = true;

                                if(
                                    array_key_exists("values", $defaultVariant) &&
                                    array_key_exists("values", $variant) &&
                                    sizeof($defaultVariant["values"]) ==
                                    sizeof($variant["values"])
                                ){
                                    foreach($variant["values"] as $value){
                                        $isValidAttributeValue = false;

                                        foreach($defaultVariant["values"] as $attributeValue){
                                            if($attributeValue["id"] == $value["id"]){
                                                $isValidAttributeValue = true;
                                            }
                                        }

                                        if(!$isValidAttributeValue){
                                            throw new YilinkerException($constraint->attributeValueDoesNotExists);
                                        }
                                    }
                                }
                                else{
                                    throw new YilinkerException($constraint->notEqualAttributeValues);
                                }
                            }
                        }

                        if(!$isValidAttributeName){
                            throw new YilinkerException($constraint->noAttributeNameExists);
                        }
                    }
                }
                elseif(
                    array_key_exists("productVariants", $defaultValue) &&
                    sizeof($defaultValue["productVariants"]) != sizeof($variants)
                ){
                    throw new YilinkerException($constraint->variantsNotFound);
                }
            }
        }
        catch(YilinkerException $e){
            $this->addError(null, $e->getMessage());
        }
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