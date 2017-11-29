<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;

/**
 * Class ManufacturerProductAttributeValueRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ManufacturerProductAttributeValueRepository extends EntityRepository
{
    /**
     * Get manufacturer product attribute values
     *
     * @param int $offset
     * @param int $limit
     * @return Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue
     */
    public function getNullAttributeNames($offset = null, $limit = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("v")
            ->from("YilinkerCoreBundle:ManufacturerProductAttributeValue", "v")
            ->where("v.manufacturerProductAttributeName IS NULL");

        if($offset !== null){
            $queryBuilder->setFirstResult($offset);
        }

        if($limit !== null){
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()                             
                            ->getResult();        
    }

    
}