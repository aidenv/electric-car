<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeactivateSkusManufacturerProductCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('yilinker:product:deactivate-sku-manufacturer-product')
            ->setDescription('Deactivate Sku\'s')
            ->addOption('sku',null,InputOption::VALUE_OPTIONAL,'sku',null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();

        $sku = $input->getOption("sku");

        if (is_null($sku)) {
            $output->writeln("<error>Required sku parameter.</error>");
            exit;
        }

        $conn = $em->getConnection();

        try {
            foreach($conn->fetchAll("SELECT mp.manufacturer_product_id, mpu.sku, mp.status, mp.name FROM ManufacturerProduct mp JOIN ManufacturerProductUnit mpu ON mpu.manufacturer_product_id = mp.manufacturer_product_id WHERE mpu.sku = :sku", array('sku'=> $sku)) as $row) {
                
                echo "manufacturer_product_id =>". $row['manufacturer_product_id'] . "\n sku =>" . $row['sku'] . "\n name =>" . $row['name'] . "\n status =>" . $row['status'] . "\n";
                echo "====================\n"; 

                //product status
                $productstmt = $conn->prepare("UPDATE ManufacturerProduct SET status = :status WHERE manufacturer_product_id = :manufacturer_product_id");
                $productcount = $productstmt->execute(array(
                    "status"     => "2",
                    "manufacturer_product_id" => $row['manufacturer_product_id'],
                ));

                echo $productcount . " = rows affected in manufacturer product status \n";
                
                $manufacturer = $em->getRepository('YilinkerCoreBundle:ManufacturerProduct')->find($row['manufacturer_product_id']);
                $container->get('fos_elastica.object_persister.yilinker_online.manufacturerproduct')->replaceOne($manufacturer); // delete form elastic search
            }

            $conn = null;
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }


       
        $output->writeln("<info>Completed ... [OK]</info>");        
    }

}