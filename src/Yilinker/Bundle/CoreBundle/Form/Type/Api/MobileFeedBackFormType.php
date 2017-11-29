<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class MobileFeedBackFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Title is required"
                    )),
                    new NotNull(array(
                        "message" => "Title is required"

                    )),
                )
            ))
            ->add('description', 'textarea', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Description is required"
                    )),
                    new NotNull(array(
                        "message" => "Description is required"

                    )),
                )
            ))
            ->add('phoneModel', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "PhoneModel is required"
                    ))
                )
            ))
            ->add('osVersion', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Os Version is required"
                    )),
                )
            ))
            ->add('osName', 'text', array(
                'constraints' => array(
                    new NotBlank(array(
                        "message" => "Os Name is required"
                    )),
                )
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'api_mobilefeedback';
    }
}
