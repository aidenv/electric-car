<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeactivateSkusCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('yilinker:product:deactivate-sku')
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
            $skus = $conn->fetchAll("SELECT p.product_id, pu.sku, p.status, p.name FROM Product p JOIN ProductUnit pu ON pu.product_id = p.product_id WHERE pu.sku = :sku", array('sku'=> $sku));

            foreach  ($skus as $row) {
        
                echo "productID =>". $row['product_id'] . "\n sku =>" . $row['sku'] . "\n name =>" . $row['name'] . "\n status =>" . $row['status'] . "\n";
                echo "====================\n"; 
                //product country
                $statement = $conn->prepare("UPDATE ProductCountry SET status = :status WHERE product_id = :product_id AND country_id = :country_id");
                $count = $statement->execute(array(
                    "status"     => "6",
                    "country_id" => "164",
                    "product_id" => $row['product_id'],
                ));
                echo $count . " = rows affected in product country \n";

                //product status
                $productstmt = $conn->prepare("UPDATE Product SET status = :status WHERE product_id = :product_id");
                $productcount = $productstmt->execute(array(
                    "status"     => "6",
                    "product_id" => $row['product_id'],
                ));

                echo $productcount . " = rows affected in product status \n";

                $product = $em->getRepository('YilinkerCoreBundle:Product')->find($row['product_id']);
                $container->get('fos_elastica.object_persister.yilinker_online.product')->deleteOne($product); // delete form elastic search

            }

        } catch (\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

       
        $output->writeln("<info>Completed ... [OK]</info>");        
    }

}