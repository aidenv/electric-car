<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\BackendBundle\Form\Validator\Constraints\UniqueUsername;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class AccountRegisterFormType
 * @package Yilinker\Bundle\FrontendBundle\Form\Type
 */
class AccountEditFormType extends YilinkerBaseFormType
{

    /**
     * Build Registration Form Validation
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "First name is required"
                    )),
                    new NotNull(array(
                        "message" => "Last name is required"
                    ))
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
                    ))
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
        return 'admin_account_edit';
    }

}
