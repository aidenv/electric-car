<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOrderProductCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('yilinker:orderproduct:remove')
            ->setDescription('Deactivate Sku\'s')
            ->addOption('orderproductId',null,InputOption::VALUE_OPTIONAL,'orderproductId',null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();

        $orderproductId = $input->getOption("orderproductId");

        if (is_null($orderproductId)) {
            $output->writeln("<error>Required orderproductId parameter.</error>");
            exit;
        }

        $conn = $em->getConnection();

        try {

            $conn->exec('DELETE FROM `OrderProductHistory` WHERE `order_product_id` = '.$orderproductId);

            $conn->exec('DELETE FROM `EarningTransaction` WHERE `order_product_id` = '.$orderproductId);

            $conn->exec('DELETE FROM `OrderProduct` WHERE `order_product_id` = '.$orderproductId);


        } catch (\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

       
        $output->writeln("<info>Completed ... [OK]</info>");        
    }

}