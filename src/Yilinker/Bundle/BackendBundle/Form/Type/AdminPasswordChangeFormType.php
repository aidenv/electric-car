<?php

namespace Yilinker\Bundle\BackendBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;

/**
 * Class AdminPasswordChangeFormType
 * @package Yilinker\Bundle\BackendBundle\Form\Type
 */
class AdminPasswordChangeFormType extends YilinkerBaseFormType
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
            ));
        ;
    }

    /**
     * Get Form name
     * @return string
     */
    public function getName()
    {
        return 'admin_password_change';
    }

}
