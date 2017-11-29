<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api\v3;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Yilinker\Bundle\CoreBundle\Form\Type\Product\ProductUnitFormType;

class ProductCountryUnitFormType extends AbstractType
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('units', 'collection', array(
                'type' => new ProductUnitFormType
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\Product',
            'csrf_protection' => false,
        ));
    }

    public function getName()
    {
        return 'api_v3_product_country_unit';
    }
}