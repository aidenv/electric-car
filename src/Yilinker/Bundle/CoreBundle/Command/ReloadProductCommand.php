<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Exception;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ReloadProductCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('yilinker:product:reload')
             ->setDescription('Add quantity to product')
             ->addOption(
                  'minimumQuantity',
                  null,
                  InputArgument::OPTIONAL,
                  'Minimum Quantity'
             )
             ->addOption(
                  'unit',
                  null,
                  InputArgument::OPTIONAL,
                  'Product Unit ID'
              )
             ->addOption(
                  'quantity',
                  null,
                  InputArgument::OPTIONAL,
                  'Quantity to be added'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $unit = $input->getOption('unit');
        $quantity = $input->getOption('quantity');
        $minimumQuantity = $input->getOption('minimumQuantity');

        $em = $this->getContainer()->get('doctrine')->getManager();
          
        $productRepository = $em->getRepository("YilinkerCoreBundle:Product");
        $productUnitRepository = $em->getRepository("YilinkerCoreBundle:ProductUnit");


        $em->beginTransaction();

        try{
            if(!is_null($unit) && !is_null($quantity)){
                
                $productUnitRepository = $em->getRepository("YilinkerCoreBundle:ProductUnit");

                $productUnit = $productUnitRepository->find((int)$unit);

                if(!is_null($minimumQuantity)){
                    if($productUnit->getQuantity() > (int)$minimumQuantity){
                        throw new Exception("Product is not yet depleting.");
                    }
                }

                if(!$productUnit){
                    throw new Exception("Product unit not found.");
                }

                if(!(int)$quantity){
                    throw new Exception("Invalid quantity.");
                }

                $productUnit->setQuantity((int)$quantity);
               
                $em->flush();
                $em->commit();
                $output->writeln("Quantity Added.");
            }
            else{

                $helper = $this->getHelper('question');
                $p = new Question('Product ID : ');

                $productId = $helper->ask($input, $output, $p);

                if(is_null($productId)){
                    throw new Exception("Invalid Product ID.");
                }

                $product = $productRepository->find((int)$productId);

                if(!$product){
                    throw new Exception("Product not found.");
                }

                $productUnitIds = array();

                $productUnits = $product->getUnits();

                foreach($productUnits as $productUnit){
                  array_push($productUnitIds, $productUnit->getProductUnitId());
                }

                $pu = new Question('Product Unit ID ('.implode('/', $productUnitIds).') : ');
                $productUnitId = $helper->ask($input, $output, $pu);

                if(is_null($productUnitId) || !in_array($productUnitId, $productUnitIds)){
                    throw new Exception("Invalid Product Unit ID.");
                }

                $productUnit = $productUnitRepository->findOneBy(array(
                                  "product" => $product,
                                  "productUnitId" => $productUnitId
                               ));

                if(!$productUnit){
                    throw new Exception("Product unit not found.");
                }

                $qty = new Question('Quantity : ');
                $unitQuantity = $helper->ask($input, $output, $qty);

                if(is_null($unitQuantity) || !(int)$unitQuantity){
                    throw new Exception("Invalid Quantity.");
                }

                if(!is_null($minimumQuantity)){
                    if($productUnit->getQuantity() > (int)$minimumQuantity){
                        throw new Exception("Product is not yet depleting.");
                    }
                }

                $productUnit->setQuantity((int)$unitQuantity);

                $question = new ConfirmationQuestion("Confirm (y/n) [n]? ", false, "/^((Y|y|yes|Yes|YES))/i");

                if (!$helper->ask($input, $output, $question)) {
                    //do nothing
                    throw new Exception("Canceled.");
                }
                else{
                    //proceed
                    $em->flush();
                    $em->commit();
                    $output->writeln("Quantity Added.");
                }
            }
        }
        catch(Exception $e){
            $em->rollback();
            $output->writeln($e->getMessage());
        }
    }
}
