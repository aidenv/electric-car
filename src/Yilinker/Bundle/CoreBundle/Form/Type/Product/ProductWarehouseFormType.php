<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Product;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Entity\Logistics;
use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ExpressLogistics;

class ProductWarehouseFormType extends AbstractType
{
    private $container;
    private $user;

    public function setContainer($container)
    {
        $this->container = $container;
        $this->setUser();
    }

    public function setUser()
    {
        $ts = $this->container->get('security.token_storage');
        $this->user = $ts->getToken()->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userWarehouse', null, array(
                'query_builder' => function($er) {
                    return $er->getUserWarehouses($this->user)->getQB();
                },
                'choice_attr' => function($userWarehouse, $key, $index) {
                    return array(
                        'data-country-code' => $userWarehouse->getCountry()->getCode()
                    );
                },
                'label' => 'Warehouse Address'
            ))
            ->add('isCod', 'checkbox', array(
                'required' => false,
                'label' => 'Available for COD?'
            ))
            ->add('logistics', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\Logistics',
                'multiple' => false,
                'placeholder' => 'Please select logistics service',
                'required' => false,
                'property' => 'name',
                'label' => 'Logistic Service',
                'invalid_message' => 'Invalid Logistics'
            ))
            ->add('handlingFee', 'number', array(
                'required' => false,
                'constraints' => array(
                    new Range(array(
                        'min' => 0,
                        'minMessage' => 'Handling fee must be equal or greater than 0'
                    ))
                ),
                'attr' => array(
                    'placeholder' => '0.00',
                ),
                'label' => 'Shipping Cost'
            ))
        ;

        $this->addPreSubmitEventListener($builder);
        $this->addPostSubmitEventListener($builder);
    }

    public function addPreSubmitEventListener(&$builder)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $productWarehouse = $event->getData();
            $form = $event->getForm();

            if ($form->getData()->getPriority() === ProductWarehouse::DEFAULT_PRIORITY) {
                $form->add('userWarehouse', null, array(
                    'query_builder' => function($er) {
                        return $er->getUserWarehouses($this->user)->getQB();
                    },
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Warehouse is required"
                        )),
                        new NotNull(array(
                            "message" => "Warehouse is required"
                        )),
                    ),
                ));
            }

            if ($productWarehouse['userWarehouse']) {
                $form->add('logistics', 'entity', array(
                    'class' => 'Yilinker\Bundle\CoreBundle\Entity\Logistics',
                    'multiple' => false,
                    'placeholder' => 'Please select logistics service',
                    'required' => false,
                    'property' => 'name',
                    'label' => 'Logistic Service',
                    'invalid_message' => 'Invalid Logistics',
                    'constraints' => array(
                        new NotBlank(array(
                            "message" => "Logistics field is required"
                        )),
                        new NotNull(array(
                            "message" => "Logistics field is required"
                        )),
                        new ExpressLogistics($productWarehouse['userWarehouse'])
                    )
                ));

                if (isset($productWarehouse['logistics'])
                    && (int) $productWarehouse['logistics'] !== Logistics::YILINKER_EXPRESS) {
                    $form->add('handlingFee', 'number', array(
                        'required' => false,
                        'constraints' => array(
                            new Range(array(
                                'min' => 1,
                                'minMessage' => 'Handling cannot be zero'
                            ))
                        ),
                        'attr' => array(
                            'placeholder' => '0.00',
                        ),
                        'label' => 'Shipping Cost'
                    ));
                }
            }
        });
    }

    public function addPostSubmitEventListener(&$builder)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $productWarehouse = $event->getData();

            if ($productWarehouse->getProductWarehouseId()
                && $productWarehouse->getLogistics()
                && $productWarehouse->getLogistics()->getLogisticsId() === Logistics::YILINKER_EXPRESS) {
                $productWarehouse->setHandlingFee(0);
            }

            if ($productWarehouse->getProductWarehouseId() && $productWarehouse->getUserWarehouse()
                && strtolower($productWarehouse->getUserWarehouse()->getCountry()->getCode()) !== strtolower($productWarehouse->getCountryCode())) {
                $productWarehouse->setIsCod(false);
            }

            $event->setData($productWarehouse);
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse'
        ));
    }

    public function getName()
    {
        return 'product_warehouse';
    }
}