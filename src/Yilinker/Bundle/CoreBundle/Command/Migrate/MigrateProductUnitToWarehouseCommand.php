<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\Query\Expr\Join;

use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\UserWarehouse;

class MigrateProductUnitToWarehouseCommand extends ContainerAwareCommand
{
    protected $em;
    protected $output;

    protected function configure()
    {
        $this->setName('yilinker:product-unit:data-migrate')
             ->setDescription('Move ProductUnit quantity to ProductUnitWarehouse');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->output = $output;

        $output->writeln("Migrate start!");
        $output->writeln("");

        do {
            $stores = $this->getStoresWithoutWarehouse();

            foreach ($stores as $store) {

                $output->write("Creating warehouse for user: ".$store->getStoreId()." ...");

                $createWarehouse = $this->createWarehouse($store);

                if ((bool) $createWarehouse['isSuccessful']) {
                    $output->writeln("<info>[OK]</info>");

                    $output->write("Moving products of user: ".$store->getStoreId()." ...");

                    if (isset($createWarehouse['data']['userWarehouse'])
                        && $userWarehouse = $createWarehouse['data']['userWarehouse']) {

                        $migrateProducts = $this->migrateStoreProducts($userWarehouse);

                        $output->write("<info>[OK]</info>");
                        $output->writeln("<info>[".$migrateProducts['message']."]</info>");

                    }
                    else {
                        $output->write("<error>[FAIL]</error>");
                        $output->writeln("<error>[No warehouse found]</error>");
                    }

                }
                else {
                    $output->write("<error>[FAIL]</error>");
                    $output->writeln("<error>[".$createWarehouse['message']."]</error>");
                }
                $output->writeln("");
                $this->em->flush();

            }
        } while (count($stores) > 0);

        $output->writeln("Migrate complete!");
    }

    private function getStoresWithoutWarehouse()
    {
        $storeRepository = $this->em->getRepository('YilinkerCoreBundle:Store');

        $stores = $storeRepository->qb()
                                  ->leftJoin('this.user', 'User')
                                  ->andWhere('this.storeType = :storeType')
                                  ->setParameter('storeType', Store::STORE_TYPE_MERCHANT)

                                  ->leftJoin('User.addresses', 'UserAddress', Join::WITH, 'UserAddress.user = User')
                                  ->andWhere('UserAddress.isDefault = :isDefault')
                                  ->setParameter('isDefault', true)

                                  ->leftJoin('User.warehouses', 'UserWarehouse', Join::WITH, 'UserWarehouse.user = User')
                                  ->andWhere('UserWarehouse is NULL')
                                  ->setLimit(10)
                                  ->getResult();

        return $stores;
    }

    private function createWarehouse($store)
    {
        $user = $store->getUser();
        if ($userDefaultAddress = $user->getDefaultAddress()) {

            $userWarehouse = new UserWarehouse;

            $storeName = strlen($store->getStoreName()) ? $store->getStoreName() : 'Default Warehouse';

            $userWarehouse->setName($storeName)
                          ->setZipCode($userDefaultAddress->getZipCode())
                          ->setAddress($userDefaultAddress)
                          ->setLocation($userDefaultAddress->getLocation())
                          ->setUser($user);

            $this->em->persist($userWarehouse);

            return array(
                'data' => array('userWarehouse' => $userWarehouse),
                'isSuccessful' => true,
                'message' => '',
            );
        }

        return array(
            'isSuccessful' => false,
            'message' => 'No default address',
        );
    }

    private function migrateStoreProducts($userWarehouse)
    {
        $output = $this->output;
        $user = $userWarehouse->getUser();
        $products = $user->getProducts();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $defaultLogistics = $em->getReference('YilinkerCoreBundle:Logistics', 1);

        foreach ($products as $product) {
            foreach ($product->getUnits() as $productUnit) {
                $productUnitWarehouse = new ProductUnitWarehouse;

                $productUnitWarehouse->setQuantity($productUnit->getQuantity())
                                     ->setProductUnit($productUnit)
                                     ->setUserWarehouse($userWarehouse);

                $this->em->persist($productUnitWarehouse);
            }

            $productWarehouse = new ProductWarehouse;
            $productWarehouse->setUserWarehouse($userWarehouse)
                             ->setLogistics($defaultLogistics)
                             ->setIsCod(true)
                             ->setProduct($product);
            $this->em->persist($productWarehouse);
        }

        return array(
            'isSuccessful' => true,
            'message' => count($products) . " products migrated",
        );
    }
}
