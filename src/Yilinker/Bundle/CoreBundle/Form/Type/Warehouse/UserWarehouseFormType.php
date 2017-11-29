<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Warehouse;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Length;

use Yilinker\Bundle\CoreBundle\Form\Type\YilinkerBaseFormType;

/**
 * Class UserWarehouseFormType
 * @package Yilinker\Bundle\CoreBundle\Form\Type
 */
class UserWarehouseFormType extends YilinkerBaseFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
                    'label' => 'Warehouse name',
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Warehouse name can only be up to {{ limit }} characters',
                        ))
                    ),
                    'attr' => array(
                        'placeholder' => 'Enter warehouse name here'
                    )
                ))
                ->add('address', 'text', array(
                    'label' => 'Warehouse address',
                    'attr' => array(
                        'placeholder' => 'Enter warehouse address here'
                    ),
                    'constraints' => array(
                        new NotBlank(array(
                            'message' => 'Warehouse address cannot be empty',
                        )),
                        new NotNull(array(
                            'message' => 'Warehouse address is required',
                        )),
                        new Length(array(
                            'max' => 255,
                            'maxMessage' => 'Warehouse name can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('location', 'location', array(
                    'include_location_js' => !$options['edit_mode'],
                    'include_country' => true,
                ))
                ->add('zipCode', null, array(
                    'invalid_message' => "Please enter a valid zip code.",
                    'required' => false,
                    'attr' => array(
                        'placeholder' => 'Enter zip code'
                    ),
                    'constraints' => array(
                        new Length(array(
                            'max' => 45,
                            'maxMessage' => 'Zip code can only be up to {{ limit }} characters',
                        ))
                    )
                ))
                ->add('submit', 'submit')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\UserWarehouse',
            'edit_mode'  => false,
            'csrf_protection' => false,
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user_warehouse';
    }

}
