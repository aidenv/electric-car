<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Product;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Yilinker\Bundle\CoreBundle\Form\DataTransformer\EntityToPrimaryTransformer;

class ManufacturerProductFormType extends AbstractType
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'attr' => array(
                    'class' => 'form-ui'
                ),
                'constraints' => array(
                    new NotBlank(array(
                        'message' => 'Name should not be blank'
                    ))
                )
            ))
            ->add('description', null, array(
                'attr' => array(
                    'data-fed' => 'data.description',
                    'class' => 'form-ui'
                )
            ))
            ->add('productCategory', null, array(
                'attr' => array(
                    'class' => 'form-ui single selection search dropdown'
                ),
                'label' => 'Category',
                'query_builder' => function($er) {
                    return $er->getCategoriesByKeyword('', 10, true, true, true);
                }
            ))
            ->add('brand', null, array(
                'attr' => array(
                    'class' => 'single selection search dropdown'
                ),
                'label' => 'Brand',
                'query_builder' => function($er) {
                    return $er->getBrandByName('', 10, true, true);
                }
            ))
            ->add('primaryImage', 'hidden')
            ->add('photoImages', 'hidden', array(
                'mapped' => false
            ))
            ->add('units', 'collection', array(
                'type' => new ManufacturerProductUnitFormType
            ))
        ;

        $em = $this->container->get('doctrine.orm.entity_manager');
        $inhouseProductImageTransformer = new EntityToPrimaryTransformer($em, 'YilinkerCoreBundle:ProductImage');
        $builder->get('primaryImage')->addModelTransformer($inhouseProductImageTransformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\InhouseProduct',
            'csrf_protection' => false
        ));
    }

    public function getName()
    {
        return 'inhouse_product';
    }
}
