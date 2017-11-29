<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Product;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ManufacturerProductUnitFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultConstraint = array(
            new NotBlank(array(
                'message' => 'value is required'
            )),
            new Range(array(
                'min' => 0,
                'minMessage' => 'value must be equal or greater than 0'
            ))
        );

        $builder
            ->add('quantity', null, array(
                'attr' => array(
                    'class'    => 'form-ui',
                    'readonly' => true,
                ),
            ))
            ->add('price', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'constraints' => $defaultConstraint
            ))
            ->add('discounted', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'constraints' => $defaultConstraint
            ))
            ->add('width', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                )
            ))
            ->add('height', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                )
            ))
            ->add('length', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                )
            ))
            ->add('weight', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                )
            ))
            ->add('commission', 'text', array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'constraints' => $defaultConstraint
            ))
            ->add('shippingFee', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'constraints' => $defaultConstraint
            ))
            ->add('retailPrice', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'constraints' => $defaultConstraint
            ))
            ->add('productAttributeValues', 'collection', array(
                'type' => new ManufacturerProductAttributeValueFormType
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\InhouseProductUnit'
        ));
    }

    public function getName()
    {
        return 'manufacturer_product_unit';
    }
}
