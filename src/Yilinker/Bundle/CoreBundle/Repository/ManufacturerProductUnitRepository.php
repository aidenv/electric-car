<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class ManufacturerProductUnitRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ManufacturerProductUnitRepository extends EntityRepository
{
    const SORT_DIRECTION_ASC = 'asc';

    const SORT_DIRECTION_DESC = 'desc';

    const SORT_BY_DATE_ADDED = 'added';

    const SORT_BY_DATE_MODIFIED = 'modified';
    
    public function getProductUnitBySkuManufacturer($sku, $manufacturerReference = null)
    {
        $queryBuilder = $this->createQueryBuilder("mpu");
        $queryBuilder->where("mpu.sku = :sku")
                     ->setParameter("sku", $sku);
        
        if($manufacturerReference !== null){
            $queryBuilder->innerJoin("YilinkerCoreBundle:ManufacturerProduct", "mp", "WITH", "mpu.manufacturerProduct = mp")
                         ->innerJoin("YilinkerCoreBundle:Manufacturer", "m", "WITH", 
                                     "mp.manufacturer = m.manufacturerId AND m.referenceId = :manufacturerReference")
                         ->setParameter(":manufacturerReference", $manufacturerReference);
        }

        return $queryBuilder->getQuery()
                            ->getOneOrNullResult();
    }

    /**
     * Retrieve manufacturer product unit review count  
     *
     * @param int $manufacturerProductId
     * @return int
     */
    public function getManufacturerProductUnitReviewCount($manufacturerProductUnitId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(review)')
           ->from('Yilinker\Bundle\CoreBundle\Entity\ProductReview', 'review')
           ->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH",
                       "op.orderProductId = review.orderProduct AND op.manufacturerProductUnit = :manufacturerProductUnitId")
           ->where('review.isHidden = :hidden')
           ->setParameter('manufacturerProductUnitId', $manufacturerProductUnitId)
           ->setParameter('hidden', false);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getManufacturerProductUnitsIn($manufacturerProductUnitIds)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("mpu")
                     ->from("YilinkerCoreBundle:manufacturerProductUnit", "mpu")
                     ->where("mpu.manufacturerProductUnitId IN (:unitIds)")
                     ->setParameter(":unitIds", $manufacturerProductUnitIds);

        return $queryBuilder->getQuery()->getResult();
    }
}


