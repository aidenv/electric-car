<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidVerificationCode;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class UserForgotPasswordCodeFormType extends AbstractType
{
    private $kernel = null;

    public function setKernel($kernel)
    {
        $this->kernel = $kernel;
    }

    public function userType()
    {
        $userType = null;

        if ($this->kernel) {
            if ($this->kernel->getName() == 'frontend') {
                $userType = User::USER_TYPE_BUYER;
            }
            elseif ($this->kernel->getName() == 'merchant') {
                $userType = User::USER_TYPE_SELLER;
            }
        }

        return $userType;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userType = $this->userType();
        $options['userType'] = $userType;

        $builder->add('code', 'text', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Field is required."
                            )),
                            new NotNull(array(
                                "message" => "Field is required."
                            )),
                            new ValidVerificationCode($options)
                        ),
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "csrf_protection" => true,
            "mustVerify" => false,
            "user" => null,
            "contactNumber" => null,
            "token" => null,
            "type" => OneTimePasswordService::OTP_TYPE_FORGOT_PASSWORD,
            "areaCode" => null
        ));
    }

    public function getName()
    {
        return 'user_forgot_password_code';
    }
}
