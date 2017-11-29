<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class ProductAttributeValueRepository extends EntityRepository
{

    /**
     * Get grouped product attributes grouped and translate based on locale
     *
     * @param int $productId
     * @param string $languageCode
     */
    public function getGroupedProductAttributesByProduct($productId, $languageCode = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("v")
            ->from("YilinkerCoreBundle:ProductAttributeValue", "v")
            ->innerJoin("YilinkerCoreBundle:ProductAttributeName", "n", Join::WITH, "v.productAttributeName = n AND n.product = :productId")            
            ->setParameter('productId', $productId)
            ->groupBy("v.value");

        $query = $queryBuilder->getQuery();
        if($languageCode){
            $query->setHint(
                \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );
            $query->setHint(
                \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
                $languageCode
            );
        }

        $productAttributeValues = $query->getResult();
        $groupedAttributeValues = array();
        foreach($productAttributeValues as $value){
            $attributeName = $value->getProductAttributeName()->getName();
            $groupedAttributeValues[$attributeName][] = array(
                "id"    => $value->getProductAttributeValueId(),
                "value" => $value->getValue(),
            );
        }


        return $groupedAttributeValues;

        
    }
    
}
