<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductCountry;

class MigrateManufacturerProductCountryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('yilinker:manufacturer-product-country:data-migrate')
             ->setDescription('Create default country all existing manufacturer product.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->em = $container->get('doctrine')->getManager();

        $defaultCountry = $this->em->getRepository('YilinkerCoreBundle:Country')
                                   ->findOneByCode(Country::COUNTRY_CODE_PHILIPPINES);

        while ($manufacturerProducts = $this->getProductWithoutCountry()) {
            foreach ($manufacturerProducts as $product) {

                $manufacturerProductCountry = new ManufacturerProductCountry;

                $manufacturerProductCountry->setCountry($defaultCountry)
                                           ->setManufacturerProduct($product);

                $this->em->persist($manufacturerProductCountry);
                $this->em->flush();
            }
        }

        $output->writeln("");
        $output->writeln("Migrate Complete!");
    }


    private function getProductWithoutCountry()
    {
        $manufacturerProductRepo = $this->em->getRepository('YilinkerCoreBundle:ManufacturerProduct');

        $products = $manufacturerProductRepo->qb()
                                            ->leftJoin('this.manufacturerProductCountries', 'ManufacturerProductCountry')
                                            ->andWhere('ManufacturerProductCountry is NULL')
                                            ->setLimit(10)
                                            ->getResult();

        return $products;
    }
}
