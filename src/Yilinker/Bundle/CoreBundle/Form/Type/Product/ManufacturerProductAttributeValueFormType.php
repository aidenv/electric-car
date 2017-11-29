<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Product;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotNull;

class ManufacturerProductAttributeValueFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'required' => true,
                'empty_data' => ' ',
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue',
            'csrf_protection' => false
        ));
    }

    public function getName()
    {
        return 'manufacturer_product_attribute_value';
    }
}