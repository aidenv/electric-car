<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidVerificationCode;

class UpdateBasicInfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $verificationCodeConstraints = array();
        $emailConstraints = array();

        if($options["mustVerify"]){
            $verificationCodeConstraints["constraints"] = array(
                new NotBlank(array(
                    "message" => "Confirmation Code is required"
                )),
                new NotNull(array(
                    "message" => "Confirmation Code is required"
                )),
                new ValidVerificationCode($options)
            );
        }

        if($options["hasEmail"]){

            $emailConstraints["constraints"] = array(
                    new Email(array(
                            "message" => "Email Address is not valid"

                    )),
                    new UniqueEmail($options)
            );
        }

        $builder->add('firstName', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "First Name is required"
                        )),
                        new NotNull(array(
                            "message" => "First Name is required"
                        )),
                        new Name(array("message" => "First name contains invalid characters")),
                    )
                ))
                ->add('lastName', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Last Name is required"
                        )),
                        new NotNull(array(
                            "message" => "Last Name is required"
                        )),
                        new Name(array("message" => "Last name contains invalid characters")),
                    )
                )) 
                ->add('contactNumber', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Contact No is required"
                        )),
                        new NotNull(array(
                            "message" => "Contact No is required"
                        )),
                        new Length(array(
                            'min' => 10,
                            'max' => 20,
                            'minMessage' => 'Contact number must be atleast {{ limit }} characters',
                            'maxMessage' => 'Contact number can only be up to {{ limit }} characters',
                        )),
                        new UniqueContactNumber($options),
                        new ValidContactNumber()
                    )
                ))
                ->add('email', 'email', $emailConstraints)
                ->add('confirmationCode', 'text', $verificationCodeConstraints)
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            "excludeUserId" => null,
            "userType" => null,
            "storeType" => null,
            "contactNumber" => null,
            "type" => null,
            "user" => null,
            "mustVerify" => true,
            "hasEmail" => false,
            "areaCode" => null
        ));
    }

    public function getName()
    {
        return 'api_core_update_basic_info';
    }
}
