<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use \PDO;

/**
 * Class SyncProductUnitQuantityCommand
 * @package Yilinker\Bundle\CoreBundle\Command\Migrate
 */
class SyncProductUnitQuantityCommand extends ContainerAwareCommand
{

    const PER_PAGE = 100;
    
    protected $em;
    
    protected $output;   

    protected function configure()
    {
        $this->setName('yilinker:product-unit:sync-quantity')
             ->setDescription('Sync ProductUnit quantity to ProductUnitWarehouse quantity');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->output = $output;
        $output->writeln("Syncing start!");
        $output->writeln("");
        $page = 0;
        do {
            $offset = $page * self::PER_PAGE;
            $page++;
            $productUnitWarehouses = $this->getProductUnitWarehouse($offset);

            foreach($productUnitWarehouses as $productUnitWarehouse) {                
                $updateSql = "
                   UPDATE ProductUnitWarehouse SET quantity = :quantity WHERE product_unit_warehouse_id = :productUnitWarehouseId
                ";
                $stmt = $this->em->getConnection()->prepare($updateSql);
                $stmt->execute(array(
                    'quantity'               => $productUnitWarehouse['quantity'],
                    'productUnitWarehouseId' => $productUnitWarehouse['product_unit_warehouse_id']
                ));
                
                $output->writeln("Syncing ProductUnit: " . $productUnitWarehouse['product_unit_id']);
            }
        }
        while ($productUnitWarehouses);

        $output->writeln("Syncing complete!");
    }

    /**
     * Get ProductUnitWarehouse with zero quantity
     *
     * @param $page
     * @return mixed
     */
    private function getProductUnitWarehouse($offset = 0, $limit = self::PER_PAGE)
    {
        $sql = "
            SELECT 
                ProductUnitWarehouse.product_unit_warehouse_id, 
                ProductUnitWarehouse.product_unit_id,
                ProductUnit.quantity
            FROM ProductUnitWarehouse
            INNER JOIN ProductUnit ON ProductUnit.product_unit_id = ProductUnitWarehouse.product_unit_id
            ORDER BY ProductUnitWarehouse.product_unit_warehouse_id
            LIMIT ".$limit." OFFSET ".$offset;
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->bindValue(':quantity', 0);
        $stmt->execute();
        $productUnitWarehouses = $stmt->fetchAll();
        
        return $productUnitWarehouses;
    }

}
