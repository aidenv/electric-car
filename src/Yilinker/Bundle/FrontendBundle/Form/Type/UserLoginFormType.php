<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

class UserLoginFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', 'text', array(
                        'constraints' => array(
                            new NotBlank(array(
                                "message" => "Username field is is required"
                            )),
                            new NotNull(array(
                                "message" => "Username field is is required"
                            ))
                        ),
                    ))
                ->add('password', 'password', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Password field is is required"
                        )),
                        new NotNull(array(
                            "message" => "Password field is is required"
                        ))
                    ),
                ))
                ->add('save', 'submit', array('label' => 'Sign in'));
    }

    public function getName()
    {
        return 'user_login';
    }
}
