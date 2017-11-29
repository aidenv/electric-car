<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;

class MigrateProductCountryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('yilinker:product-country:data-migrate')
             ->setDescription('Create default country all existing manufacturer product.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->em = $container->get('doctrine')->getManager();

        $defaultCountry = $this->em->getRepository('YilinkerCoreBundle:Country')
                                   ->findOneByCode(Country::COUNTRY_CODE_PHILIPPINES);

        while ($products = $this->getProductWithoutCountry()) {
            foreach ($products as $product) {

//                $productCountry = new ProductCountry;

//                $productCountry->setCountry($defaultCountry)
//                               ->setProduct($product)
//                               ->setStatus($product->getStatus());
$this->em->getConnection()->exec("INSERT INTO `ProductCountry` (`product_country_id`, `country_id`, `product_id`, `date_created`, `date_last_modified`, `status`) VALUES (NULL, ".$defaultCountry->getCountryId().", ".$product->getProductId().", CURDATE(), CURDATE(), ".$product->getStatus().");");
//                $this->em->persist($productCountry);
                $output->writeln("Processing product #".$product->getProductId());
//                $this->em->flush();
            }
	    $this->em->clear();
            $products = null;
            gc_collect_cycles();
        }

        $output->writeln("");
        $output->writeln("Migrate Complete!");
    }


    private function getProductWithoutCountry()
    {
        $manufacturerProductRepo = $this->em->getRepository('YilinkerCoreBundle:Product');

        $products = $manufacturerProductRepo->qb()
                                            ->leftJoin('this.productCountries', 'ProductCountry')
                                            ->andWhere('ProductCountry is NULL')
                                            ->setLimit(10)
                                            ->getResult();

        return $products;
    }
}
