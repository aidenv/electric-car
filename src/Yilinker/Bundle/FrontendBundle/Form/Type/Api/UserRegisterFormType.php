<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\UniqueEmail;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class UserRegisterFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', 'password', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Password field is required"
                    )),
                    new NotNull(array(
                        "message" => "Password field is required"
                    )),
                    new Length(array(
                        'min' => 6,
                        'minMessage' => 'Password must be atleast {{ limit }} characters',
                        'max' => 15,
                        'maxMessage' => 'Password can only be up to {{ limit }} characters',
                    ))
                )
            ))
            ->add('email', 'email', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Email Address field is required"
                    )),
                    new NotNull(array(
                        "message" => "Email Address field is required"

                    )),
                    new Email(array(
                        "message" => "Email Address is not valid"

                    )),
                    new UniqueEmail()
                )
            ))
            ->add('firstName', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "First name is required"
                    )),
                    new NotNull(array(
                        "message" => "First name is required"
                    ))
                )
            ))
            ->add('lastName', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Last name is required"
                    )),
                    new NotNull(array(
                        "message" => "Last name is required"
                    ))
                )
            ))
            ->add('userType', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "User Type is required"
                    )),
                    new NotNull(array(
                        "message" => "User Type is required"
                    ))
                )
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
                        new UniqueContactNumber(),
                        new ValidContactNumber()
                    )
            ))
        ;
    }

    public function getName()
    {
        return 'api_user_register';
    }
}
