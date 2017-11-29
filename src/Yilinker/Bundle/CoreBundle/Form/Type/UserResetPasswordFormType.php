<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidVerificationCode;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class UserResetPasswordFormType extends AbstractType
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

        $builder->add('plainPassword', 'repeated', array(
                        'label' => 'Password',
                        'type' => 'password',
                        'invalid_message' => 'Passwords do not match',
                        'first_options' => array('label' => 'Password'),
                        'second_options' => array('label' => 'Confirm Password'),
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Password field is required"
                            )),
                            new NotNull(array(
                                "message" => "Password field is required"
                            )),
                            new Length(array(
                                    'min' => 8,
                                    'minMessage' => 'Password must be atleast {{ limit }} characters',
                                    'max' => 25,
                                    'maxMessage' => 'Password can only be up to {{ limit }} characters',
                            )),
                            new YilinkerPassword(),
                        )
                ))
                ->add('verificationCode', 'text', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Mobile verification is required"
                            )),
                            new NotNull(array(
                                "message" => "Mobile verification is required"
                            )),
                            new Length(array(
                                'max' => 10,
                                'maxMessage' => 'Mobile verification is required can only be up to {{ limit }} characters',
                            )),
                            new ValidVerificationCode($options)
                        )
                ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "storeType" => Store::STORE_TYPE_RESELLER,
            "mustVerify" => false,
            'contactNumber' => null,
            "token" => null,
            "user" => null,
            "type" => null,
            "areaCode" => null
        ));
    }

    public function getName()
    {
        return 'core_reset_password';
    }
}
