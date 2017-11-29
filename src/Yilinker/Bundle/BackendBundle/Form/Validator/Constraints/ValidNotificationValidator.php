<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidNotificationValidator extends ConstraintValidator
{
    private $deviceNotificationRepository;

    public function __construct($deviceNotificationRepository){
        $this->deviceNotificationRepository = $deviceNotificationRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();

        $isValid = true;

        $deviceNotificationId = array_key_exists("deviceNotificationId", $options)? $options["deviceNotificationId"] : null;

        $deviceNotification = $this->deviceNotificationRepository->find($deviceNotificationId);

        $message = "";

        if(!$deviceNotification){
            $message = $constraint->message;
            $isValid = false;
        }

        if($deviceNotification && $deviceNotification->getIsSent()){
            $message = $constraint->uneditable;
            $isValid = false;
        }

        if(!$isValid){
            $this->context
                 ->buildViolation($message)
                 ->addViolation();
        }
    }

}
