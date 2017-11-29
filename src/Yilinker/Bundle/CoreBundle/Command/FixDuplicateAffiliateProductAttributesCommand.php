<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;

class FixDuplicateAffiliateProductAttributesCommand extends ContainerAwareCommand
{
    const PER_ITERATION = 30;
    
    protected function configure()
    {
        $this
            ->setName('yilinker:fix:duplicate-affiliate-attribute')
            ->setDescription('Fix duplicate affiliate product attributes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();        
        $em = $container->get('doctrine')->getManager();

        $numberOfCleanedAttributes = 0;
        do{        
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select('v.productAttributeValueId')
                ->addSelect('v.value')
                ->addSelect('pu.productUnitId')
                ->addSelect('p.productId')
                ->addSelect('COUNT(v) as HIDDEN duplicateCount')
                ->from('YilinkerCoreBundle:ProductAttributeValue', 'v')
                ->innerJoin('YilinkerCoreBundle:ManufacturerProductUnitMap', 'm', 'WITH', 'm.productUnit = v.productUnit')
                ->innerJoin('YilinkerCoreBundle:ProductUnit', 'pu', 'WITH', 'pu = v.productUnit')
                ->innerJoin('YilinkerCoreBundle:Product', 'p', 'WITH', 'p = pu.product')
                ->addGroupBy('v.value')
                ->addGroupBy('v.productUnit')
                ->setMaxResults(self::PER_ITERATION)
                ->andWhere("v.value IS NOT NULL AND v.value != ''")
                ->having('duplicateCount > 1');
            $results = $queryBuilder->getQuery()->getResult();
            $resultCount = count($results);

            foreach($results as $result){

                $attributeValueSql = "
                    DELETE FROM ProductAttributeValue
                    WHERE product_unit_id = :productUnitId AND value = :value AND
                    product_attribute_value_id != :validProductAttributeValueId
                ";
                $params = array(
                    'productUnitId'                => $result['productUnitId'],
                    'value'                        => $result['value'],
                    'validProductAttributeValueId' => $result['productAttributeValueId'],
                );

                $stmt = $em->getConnection()->prepare($attributeValueSql);
                if($stmt->execute($params)){

                    $attributeNameQueryBuilder = $em->createQueryBuilder();
                    $attributeNameQueryBuilder->select('n')
                        ->addSelect('COUNT(v) as HIDDEN valueCount')
                        ->from('YilinkerCoreBundle:ProductAttributeName', 'n')
                        ->leftJoin('YilinkerCoreBundle:ProductAttributeValue', 'v', 'WITH', 'v.productAttributeName = n')
                        ->andWhere('n.product = :productId')
                        ->addGroupBy('n.productAttributeNameId')
                        ->setParameter('productId', $result['productId'])
                        ->having('valueCount = 0');
                    $attributeValues = $attributeNameQueryBuilder->getQuery()->getResult();

                    if(count($attributeValues) > 0){
                        foreach($attributeValues as $attributeValue){
                            $em->remove($attributeValue);
                        }
                        $em->flush();
                    }

                    $output->writeln("Successfully cleaned-up value: ".$result['value']." - product unit id : ".$result['productUnitId']);
                }
                else{
                    $output->writeln("Failed to clean-up value: ".$result['value']." - product unit id : ".$result['productUnitId']);
                }
                $numberOfCleanedAttributes++;
            }
        }
        while($resultCount > 0);

        $output->writeln("Completed data clean-up. Total of duplicated instances fixed: ".$numberOfCleanedAttributes);
    }

   
}

