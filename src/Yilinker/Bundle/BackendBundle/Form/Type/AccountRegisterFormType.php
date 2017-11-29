<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\UniqueUsername;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\Name;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;

/**
 * Class AccountRegisterFormType
 * @package Yilinker\Bundle\FrontendBundle\Form\Type
 */
class AccountRegisterFormType extends YilinkerBaseFormType
{

    /**
     * Build Registration Form Validation
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                        'minMessage' => 'Password must be at least {{ limit }} characters',
                        'max' => 25,
                        'maxMessage' => 'Password can only be up to {{ limit }} characters',
                    )),
                    new YilinkerPassword(),
                )
            ))
            ->add('username', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Username field is required"
                    )),
                    new NotNull(array(
                        "message" => "Username field is required"
                    )),
                    new UniqueUsername()
                )
            ))
            ->add('firstName', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "First name is required"
                    )),
                    new NotNull(array(
                        "message" => "Last name is required"
                    )),
                    new Name()
                )
            ))
            ->add('adminRole', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\AdminRole',
                'multiple' => false,
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'No Role is selected')
                    ),
                    new NotNull(
                        array('message' => 'No Role is selected')
                    ),
                ),
                'invalid_message' => 'Invalid Role'
            ))
            ->add('lastName', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Last Name is required"
                    )),
                    new NotNull(array(
                        "message" => "Last Name is required"
                    )),
                    new Name()
                )
            ))
        ;
    }

    /**
     * Get Form name
     * @return string
     */
    public function getName()
    {
        return 'admin_account_registration';
    }

}
