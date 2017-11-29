<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\UserImage;

class ExistingUserImageValidator extends ConstraintValidator
{
    private $userImageRepository;

    public function __construct($userImageRepository)
    {
        $this->userImageRepository = $userImageRepository;
    }

    public function validate($fileName, Constraint $constraint)
    {
        if($fileName){

            $options = $constraint->getOptions();
            $user = array_key_exists("user", $options)? $options["user"] : null;
            $type = array_key_exists("type", $options)? $options["type"] : null;

            $userImage = $this->userImageRepository->loadUserImageByName(
                            $fileName,
                            $user,
                            $type,
                            false
                       );

            if(is_null($userImage)){

                $imageType = ($type == UserImage::IMAGE_TYPE_AVATAR)? "profile photo" : "cover photo";

                $this->context
                     ->buildViolation($constraint->message)
                     ->setParameter('%type%', $imageType)
                     ->addViolation();
            }
        }
    }
}
