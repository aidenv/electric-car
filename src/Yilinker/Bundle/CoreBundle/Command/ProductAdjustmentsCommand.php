<?php

namespace Yilinker\Bundle\CoreBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;

class ProductAdjustmentsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('product:adjustments')
            ->setDescription('Adjust products depends on the situation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deactivateOutOfStockProducts($input, $output);
    }

    /**
     * product status sets to inactive if product is
     * out of stock for one week
     */
    private function deactivateOutOfStockProducts($input, $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $tbManufacturerProduct = $em->getRepository('YilinkerCoreBundle:ManufacturerProduct');

        $products = $tbProduct->meantForInactive();
        foreach ($products as $product) {
            $product->setStatus(Product::INACTIVE);
            $output->writeln('Product #'.$product->getProductId().' `'.$product->getName().'` will be deactivated.');
        }

        $manufacturerProducts = $tbManufacturerProduct->meantForInactive();
        foreach ($manufacturerProducts as $manufacturerProduct) {
            $manufacturerProduct->setStatus(ManufacturerProduct::STATUS_INACTIVE);
            $output->writeln('Manufacturer Product #'.$manufacturerProduct->getManufacturerProductId().' `'.$manufacturerProduct->getName().'` will be deactivated.');
        }

        $em->flush();
    }
}