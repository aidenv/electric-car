<?php

namespace Yilinker\Bundle\CoreBundle\Form\Type\Product;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\CallbackTransformer;
use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;

class ProductCountryFormType extends AbstractType
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $warehouseForm = new ProductWarehouseFormType;
        $warehouseForm->setContainer($this->container);

        $builder
            ->add('units', 'collection', array(
                'type' => new ProductUnitFormType
            ))
            ->add('productWarehouses', 'collection', array(
                'type' => $warehouseForm
            ))
        ;

        $builder->addModelTransformer(new CallbackTransformer(
            function($product) {
                $primary = null;
                $secondary = null;
                $warehouses = $product->getProductWarehouses();
                foreach ($warehouses as $warehouse) {
                    if ($warehouse->getPriority() == 1) {
                        $primary = $warehouse;
                    }
                    elseif ($warehouse->getPriority() == 2) {
                        $secondary = $warehouse;
                    }
                }
                if (!$primary) {
                    $warehouse = new ProductWarehouse;
                    $warehouse->setPriority(1);
                    $warehouse->setProduct($product);
                    $warehouse->setCountryCode($product->getCountryCode());
                    $product->addProductWarehouse($warehouse);
                }
                if (!$secondary) {
                    $warehouse = new ProductWarehouse;
                    $warehouse->setPriority(2);
                    $warehouse->setProduct($product);
                    $warehouse->setCountryCode($product->getCountryCode());
                    $product->addProductWarehouse($warehouse);
                }

                return $product;
            },
            function($product) {
                $warehouses = $product->getProductWarehouses();
                foreach ($warehouses as $warehouse) {
                    if (!$warehouse->getUserWarehouse()) {
                        $product->removeProductWarehouse($warehouse);
                    }
                }

                $trans = $this->container->get('yilinker_core.translatable.listener');
                $em = $this->container->get('doctrine.orm.entity_manager');
                $countryRepository = $em->getRepository('YilinkerCoreBundle:Country');
                $productCountryRepository = $em->getRepository('YilinkerCoreBundle:ProductCountry');
                $country = $countryRepository->findOneBy(array(
                    'code' => $trans->getCountry(),
                ));
                $countryProduct = $productCountryRepository->findOneBy(array(
                    'country' => $country,
                    'product' => $product,
                ));

                if($country && $countryProduct === null){
                    $countryProduct = new ProductCountry();
                    $countryProduct->setProduct($product);
                    $countryProduct->setCountry($country);

                    $product->addProductCountry($countryProduct);
                    $em->persist($countryProduct);
                }

                return $product;
            }
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yilinker\Bundle\CoreBundle\Entity\Product'
        ));
    }

    public function getName()
    {
        return 'product_country';
    }
}