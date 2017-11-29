<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;
use Yilinker\Bundle\CoreBundle\Traits\SlugHandler;

class ValidTargetValidator extends ConstraintValidator
{
    use SlugHandler;

    private $productRepository;

    private $storeRepository;

    public function __construct($productRepository, $storeRepository){
        $this->productRepository = $productRepository;
        $this->storeRepository = $storeRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();

        $targetType = array_key_exists("targetType", $options)? $options["targetType"] : null;

        $isValid = true;
        if($targetType == DeviceNotification::TARGET_TYPE_WEBVIEW){
            $validWebviewTargets = array(
                DeviceNotification::TARGET_FLASH_SALE,
                DeviceNotification::TARGET_CATEGORIES,
                DeviceNotification::TARGET_HOT_ITEMS,
                DeviceNotification::TARGET_NEW_ITEMS,
                DeviceNotification::TARGET_TODAYS_PROMO,
                DeviceNotification::TARGET_NEW_STORES,
                DeviceNotification::TARGET_HOT_STORES,
                DeviceNotification::TARGET_DAILY_LOGIN,
            );

            if (!in_array($value, $validWebviewTargets)) {
                $isValid = false;
            }
        }
        elseif($targetType == DeviceNotification::TARGET_TYPE_PRODUCT){
            $slug = $this->getLastSegment($value);
            $product = $this->productRepository->findOneBySlug($slug);

            if(!$product){
                $isValid = false;
            }
        }
        elseif($targetType == DeviceNotification::TARGET_TYPE_STORE){
            $slug = $this->getLastSegment($value);
            $store = $this->storeRepository->findOneByStoreSlug($slug);

            if(!$store){
                $isValid = false;
            }
        }

        if(!$isValid){
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }

}
