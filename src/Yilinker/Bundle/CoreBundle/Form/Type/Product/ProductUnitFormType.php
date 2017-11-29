<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Product;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints\Range;

class ProductUnitFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('price', null, array(
                'attr' => array(
                    'data-product-unit-price' => ''
                )
            ))
            ->add('discountedPrice', null, array(
                'attr' => array(
                    'data-product-unit-discountedPrice' => ''
                )
            ))
            ->add('commission', 'text' ,array(
                'required' => false
            ))
            // ->add('status', 'checkbox', array(
            //     'required' => false
            // ))
        ;

        // $builder->get('status')->addModelTransformer(new CallbackTransformer(
        //     function($status) {
        //         return (boolean)$status;
        //     },
        //     function($booleanStatus) {
        //         return $booleanStatus ? 1: 0;
        //     }
        // ));

        $this->addPreSubmitEventListener($builder);
    }

    public function addPreSubmitEventListener(&$builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $productUnit = $event->getData();
            $form = $event->getForm();

            // if (isset($productUnit['status']) && (bool) $productUnit['status']) {
                $form
                    ->add('price', null, array(
                        'attr' => array(
                            'data-product-unit-price' => ''
                        ),
                        'constraints' => array(
                            new Range(array(
                                'min' => 0.01,
                                'minMessage' => 'value must be greater than 0'
                            ))
                        )
                    ));
            // }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductUnit',
            'allow_extra_fields' => true
        ));
    }

    public function getName()
    {
        return 'product_unit';
    }
}