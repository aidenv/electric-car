<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\User;

class UserAddressFormType extends YilinkerBaseFormType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', array(
                    'label' => 'Address Title',
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Address title can only be up to {{ limit }} characters',
                        ))
                    ),
                    'required'  => $options['user'] instanceof User
                ))
                ->add('unitNumber', 'text', array(
                    'label' => 'Unit Number',
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Unit number can only be up to {{ limit }} characters',
                        ))
                    ),
                    'required'  => false
                ))
                ->add('buildingName', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Building name can only be up to {{ limit }} characters',
                        ))
                    ),
                    'required'  => false
                ))
                ->add('streetNumber', 'text', array(
                    'constraints' => array(
                        new Length(array(
                            'max' => 11,
                            'maxMessage' => 'Street number can only be up to {{ limit }} characters',
                        ))
                    ),
                    'required'  => false
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
                    ),
                    'required'  => false
                ))
                ->add('location', 'location', array(
                    'include_location_js' => !$options['edit_mode']
                ))
                ->add('zipCode', null, array(
                    'invalid_message' => "Please enter a valid zip code.",
                    'required' => false,
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Zip code can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('isDefault', 'checkbox', array(
                    'required'  => false
                ))
                ->add('latitude', 'hidden', array(
                    'required'  => false
                ))
                ->add('longitude', 'hidden', array(
                    'required'  => false
                ))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\UserAddress',
            'user'       => null,
            'edit_mode'  => false
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user_address';
    }
}
