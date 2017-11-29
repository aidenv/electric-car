<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;

class FixDuplicateInhouseUnitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker:fix:duplicate-inhouse')
            ->setDescription('Fix duplicate sku manufacturer product unit')
        ;
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();        
        $em = $container->get('doctrine')->getManager();
        
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('mpu.sku')
                     ->addSelect('COUNT(mpu) as HIDDEN mpuCount')
                     ->from('YilinkerCoreBundle:ManufacturerProductUnit', 'mpu')
                     ->having('mpuCount > 1')
                     ->groupBy('mpu.sku');

        $results = $queryBuilder->getQuery()->getResult();
        foreach($results as $result){
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select('mpu')
                         ->from('YilinkerCoreBundle:ManufacturerProductUnit', 'mpu')
                         ->where('mpu.sku = :sku')
                         ->setParameter('sku', $result['sku']);
            $mpus = $queryBuilder->getQuery()->getResult();

            $validMPU = null;
            $invalidMPU = null;

            foreach($mpus as $mpu){
                if($mpu->getRetailPrice()){
                    $validMPU = $mpu;
                }
                else if($mpu->getRetailPrice() === null && $mpu->getReferenceId() !== ""){
                    $invalidMPU = $mpu;
                }                
            }

            if($validMPU and $invalidMPU){
                $validMPU->setReferenceId($invalidMPU->getReferenceId());
                $id = $invalidMPU->getManufacturerProductUnitId();
                try{
                    $em->remove($invalidMPU);                        
                    $em->flush();
                    echo "Removing ".$id." \n";
                }
                catch(\Exception $e){
                    echo "Failed to remove ".$id." due to FK error\n";
                }
            }
        }

    }

   
}

