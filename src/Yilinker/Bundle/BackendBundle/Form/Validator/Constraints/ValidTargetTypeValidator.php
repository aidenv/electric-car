<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;

class ValidTargetTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $validTargetTypes = array(
            DeviceNotification::TARGET_TYPE_HOME,
            DeviceNotification::TARGET_TYPE_WEBVIEW,
            DeviceNotification::TARGET_TYPE_PRODUCT,
            DeviceNotification::TARGET_TYPE_PRODUCT_LIST,
            DeviceNotification::TARGET_TYPE_STORE,
            DeviceNotification::TARGET_TYPE_STORE_LIST,
        );

        if (!in_array($value, $validTargetTypes)) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }

    }

}
