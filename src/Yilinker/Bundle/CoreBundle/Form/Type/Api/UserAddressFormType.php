<?php
namespace Yilinker\Bundle\CoreBundle\Form\Type\Api;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class UserAddressFormType extends YilinkerBaseFormType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Address title can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('unitNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Unit number can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('buildingName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Building name can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('streetNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 11,
                            'maxMessage' => 'Street number can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('streetName', 'text', array(
                    'constraints' => array(
                        new NotBlank(array(
                            'message' => 'Street name cannot be empty',
                        )),
                        new NotNull(array(
                            'message' => 'Street name is required',
                        )),
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Street name can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('subdivision', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Subdivision can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('zipCode', 'text', array(
                    'invalid_message' => "Please enter a valid zip code.",
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Zip code can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('streetAddress', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 1024,
                            'maxMessage' => 'Street Address can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('longitude', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Invalid longitude',
                        ))
                    )
                ))
                ->add('latitude', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Invalid latitude',
                        ))
                    )
                ))
                ->add('landline', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Invalid landline',
                        ))
                    )
                ))
        ;
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
        return 'core_user_address';
    }
}
