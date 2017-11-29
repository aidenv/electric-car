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
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidVerificationCode;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidAreaCode;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidReferralCode;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Form\DataTransformer\EntityToPrimaryTransformer;

class UserRegisterFormType extends AbstractType
{
    private $container;
    private $kernel = null;

    public function setContainer($container)
    {
        $this->container = $container;
    }

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
        $passwordConstraint = array();
        if($options['validatePassword']){
            $passwordConstraint =  array(
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
            );
        }

        $builder->add('plainPassword', 'repeated', array(
                    'label' => 'Password',
                    'type' => 'password',
                    'invalid_message' => 'Passwords do not match',
                    'first_options' => array('label' => 'Password'),
                    'second_options' => array('label' => 'Confirm Password'),
                    'constraints' => $passwordConstraint,
                ))
                ->add('contactNumber', 'text', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Contact No field is required"
                            )),
                            new NotNull(array(
                                "message" => "Contact No is required"
                            )),
                            new Length(array(
                                'min' => 10,
                                'max' => 20,
                                'minMessage' => 'Contact No must be atleast {{ limit }} characters',
                                'maxMessage' => 'Contact No can only be up to {{ limit }} characters',
                            )),
                            new UniqueContactNumber($options),
                            new ValidContactNumber($options),
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
                            new ValidVerificationCode($options)
                        )
                ))
                ->add('areaCode', 'text', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "SMS support is not available in your country."
                            )),
                            new NotNull(array(
                                "message" => "SMS support is not available in your country."
                            )),
                            new Length(array(
                                'max' => 10,
                                'maxMessage' => 'Area code can only be up to {{ limit }} characters',
                            )),
                            new ValidAreaCode($options)
                        )
                ))
                ->add("referralCode", "text", array(
                        "constraints" => array(
                            new ValidReferralCode($options)
                        ),
                ))
        ;
        
        $em = $this->container->get('doctrine.orm.entity_manager');
        $builder->add('language');
        $builder->get('language')->addModelTransformer(new EntityToPrimaryTransformer($em, 'YilinkerCoreBundle:Language'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'excludeUserId' => null,
            'validatePassword' => true,
            'storeType' => Store::STORE_TYPE_RESELLER,
            "mustVerify" => false,
            'contactNumber' => null,
            "token" => null,
            "user" => null,
            "type" => null,
            "areaCode" => "63",
            "userType" => $this->userType(),
        ));
    }

    public function getName()
    {
        return 'core_user_add';
    }
}
