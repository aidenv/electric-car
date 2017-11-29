<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;

/**
 * Class CategoryAttributeName
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class CategoryAttributeNameRepository extends EntityRepository
{

    /**
     * Get Category Attribute name with value
     * @param ProductCategory $category
     * @return array
     */
    public function getCategoryAttributeNameWithValue (ProductCategory $category)
    {
        $em = $this->_em;
        $categoryAttributeEntities = $em->getRepository('YilinkerCoreBundle:CategoryAttributeName')->findByProductCategory($category);
        $categoryAttributes = array();

        if ($categoryAttributeEntities) {

            foreach ($categoryAttributeEntities as $categoryAttributeEntity) {
                $categoryAttributeValueEntities = $em->getRepository('YilinkerCoreBundle:CategoryAttributeValue')->findByCategoryAttributeName($categoryAttributeEntity);
                $categoryAttributeValue = array();

                foreach ($categoryAttributeValueEntities as $categoryAttributeValueEntity) {
                    $categoryAttributeValue[] = $categoryAttributeValueEntity->getValue();
                }

                $categoryAttributes[] = array(
                    'name' => $categoryAttributeEntity->getName(),
                    'values' => $categoryAttributeValue
                );

            }

        }

        return $categoryAttributes;
    }

}
