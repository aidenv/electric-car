<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\AlphaNumUnderscore;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\YilinkerPassword;

class ChangePasswordFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', 'repeated', array(
                        'type' => 'password',
                        'invalid_message' => 'Passwords do not match.',
                        'required' => true,
                        'first_options' => array('label' => 'Password'),
                        'second_options' => array('label' => 'Confirm Password'),
                        'constraints' => array(
                            new Length(array(
                                    'min' => 8,
                                    'minMessage' => 'Password must be atleast {{ limit }} characters',
                                    'max' => 25,
                                    'maxMessage' => 'Password can only be up to {{ limit }} characters',
                            )),
                            new YilinkerPassword(),
                        )))
                ->add('oldPassword', 'password', array(
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "The old password must be provided.",
                        ))
                    ),
                ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->addDefaultOptions(array(
            'csrf_protection' => false
        ));

        $resolver->setDefaults($this->getDefaultOptions());
    }

    public function getName()
    {
        return 'core_change_password';
    }
}
