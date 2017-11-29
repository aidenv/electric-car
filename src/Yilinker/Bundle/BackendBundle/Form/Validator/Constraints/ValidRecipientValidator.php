<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;

class ValidRecipientValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $validRecipients = array(
            DeviceNotification::RECIPIENT_ALL,
            DeviceNotification::RECIPIENT_ANDROID,
            DeviceNotification::RECIPIENT_IOS,
        );

        if (!in_array($value, $validRecipients)) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }

    }

}
