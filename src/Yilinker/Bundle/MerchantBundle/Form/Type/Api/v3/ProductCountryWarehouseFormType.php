<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Type\Api\v3;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\CallbackTransformer;
use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Yilinker\Bundle\CoreBundle\Form\Type\Product\ProductWarehouseFormType;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Yilinker\Bundle\CoreBundle\Entity\Logistics;

/**
 * THIS FORM TYPE SHOULD BY REFACTORED
 * Put the entity manipulations on the entity instead!
 */
class ProductCountryWarehouseFormType extends AbstractType
{
    private $container;
    private $productWarehouse = null;
    private $userWarehouseId = null;
    private $priority = null;
    private $countryCode = null;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->userWarehouseId = $options['userWarehouse'];
        $this->priority = $options['priority'];

        $builder
            ->add('userWarehouse', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\UserWarehouse',
                'required' => true,
                'property' => 'name',
                'constraints' => array(
                    new NotBlank(
                        array('message' => 'Invalid Userwarehouse')
                    ),
                    new NotNull(
                        array('message' => 'Invalid Userwarehouse')
                    ),
                )
            ))
            ->add('isCod', 'checkbox', array(
                'required' => false,
            ))
            ->add('priority', 'text', array(
                'required' => true,
            ))
            ->add('logistics', 'entity', array(
                'class' => 'Yilinker\Bundle\CoreBundle\Entity\Logistics',
                'multiple' => false,
                'required' => false,
                'property' => 'name',
                'invalid_message' => 'Invalid Logistics'
            ))
            ->add('handlingFee', 'number', array(
                'required' => false,
                'constraints' => array(
                    new Range(array(
                        'min' => 0,
                        'minMessage' => 'Handling fee must be greater than 0'
                    ))
                ),
                'attr' => array(
                    'placeholder' => '0.00',
                ),
                'label' => 'Shipping Cost'
            ))
        ;

        $this->addPostSubmitEventListener($builder);

        $builder->addModelTransformer(new CallbackTransformer(
            function($product) {

                $trans = $this->container->get('yilinker_core.translatable.listener');
                $em = $this->container->get('doctrine.orm.entity_manager');
                $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');
                $productCountryRepository = $em->getRepository('YilinkerCoreBundle:ProductCountry');

                $productWarehouseRepo = $em->getRepository('YilinkerCoreBundle:ProductWarehouse');
                $productWarehouse = $productWarehouseRepo->findOneBy(
                    array(
                        'priority'      => $this->priority,
                        'product'       => $product,
                        'countryCode'   => $trans->getCountry()
                    ));

                $warehouse = $productWarehouse ? $productWarehouse : new ProductWarehouse;
                $warehouse->setProduct($product);
                $warehouse->setCountryCode($trans->getCountry());
                $product->addProductWarehouse($warehouse);

                return $warehouse;
            },
            function($product) {
                return null;
            }
        ));
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

            if ($productWarehouse->getProductWarehouseId()
                && strtolower($productWarehouse->getUserWarehouse()->getCountry()->getCode()) !== strtolower($productWarehouse->getCountryCode())) {
                $productWarehouse->setIsCod(false);
            }

            $event->setData($productWarehouse);
        });
    }


    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse',
            'csrf_protection' => false,
            'userWarehouse' => null,
            'priority'  => null,
        ));
    }

    public function getName()
    {
        return 'api_v3_product_country_warehouse';
    }
}
