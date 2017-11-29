<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName;
use Doctrine\Common\Collections\Criteria;

class FixMissingAttributeNameCommand extends Command
{

    const PER_PAGE = 20;

    const DEFAULT_ATTRIBUTE_NAME = "Custom";
    
    protected function configure()
    {
        $this
            ->setName('yilinker:fix:missing-attribute-name')
            ->setDescription('Fix missing attribute name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();                
        $manufacturerProductAttributeRepo = $em->getRepository('YilinkerCoreBundle:ManufacturerProductAttributeValue');

        $perIteration = self::PER_PAGE;
        $resultCount = 0;
        do{
            $manufacturerProductAttributeValues = $manufacturerProductAttributeRepo->getNullAttributeNames(null, $perIteration);
            $resultCount = count($manufacturerProductAttributeValues);
            foreach($manufacturerProductAttributeValues as $attributeValue){
                $manufacturerProduct = $attributeValue->getManufacturerProductUnit()
                                                      ->getManufacturerProduct();
                
                $attributeNames = $manufacturerProduct->getManufacturerProductAttributeNames();
                $criteria = Criteria::create()
                                    ->andWhere(Criteria::expr()->eq("name", self::DEFAULT_ATTRIBUTE_NAME));
                $attributeName = $attributeNames->matching($criteria)->first();

                if($attributeName instanceof ManufacturerProductAttributeName === false){
                    $attributeName = new ManufacturerProductAttributeName;
                    $attributeName->setName(self::DEFAULT_ATTRIBUTE_NAME);
                    $attributeName->setManufacturerProduct($manufacturerProduct);

                    $em->persist($attributeName);
                }

                if($attributeName){
                    $attributeValue->setManufacturerProductAttributeName($attributeName);
                    $output->writeln("Fixed missing attribute name for attribute value id: ".$attributeValue->getManufacturerProductAttributeValueId());
                    $em->flush();
                }
                else{
                    $output->writeln("Failed to create attribute name for attribute value id: ".$attributeValue->getManufacturerProductAttributeValueId());
                }
            }
        }
        while($resultCount > 0);

    }

}