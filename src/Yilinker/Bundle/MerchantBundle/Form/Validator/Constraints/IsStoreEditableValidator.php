<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsStoreEditableValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);
        
        $options = $constraint->getOptions();
        $user = array_key_exists("user", $options)? $options["user"] : null;
        $type = array_key_exists("type", $options)? $options["type"] : null;

        $store = $user && $user->getStore()? $user->getStore() : null;

        if ($value && !is_null($store)){
            if($type == "storeName" && $value != $store->getStoreName()){
                if(!$store->getIsEditable()){
                    $this->context
                         ->buildViolation($constraint->message)
                         ->setParameter('%type%', "store name")
                         ->addViolation();
                }
            }
            elseif($type == "storeSlug" && $value != $store->getStoreSlug()){
                if(!$store->getIsEditable()){
                    $this->context
                         ->buildViolation($constraint->message)
                         ->setParameter('%type%', "store link")
                         ->addViolation();
                }
            }
        }
    }

}