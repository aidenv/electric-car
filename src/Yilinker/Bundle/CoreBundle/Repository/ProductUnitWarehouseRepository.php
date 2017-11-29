<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

use Doctrine\ORM\Query\Expr\Join;

class ProductUnitWarehouseRepository extends EntityRepository
{
    /**
     * Returns the number of product unit warehouse by product id
     *
     * @param int $status
     * @return int
     */
    public function getUnitWarehousesByProduct($productId = null, $isCount = false)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        if($isCount){
            $queryBuilder->select("count(puw)");
        }
        else{
            $queryBuilder->select("puw");
        }

        $queryBuilder->from("YilinkerCoreBundle:ProductUnitWarehouse", "puw")
                     ->innerJoin(
                        "YilinkerCoreBundle:ProductUnit",
                        "pu",
                        Join::WITH,
                        "puw.productUnit = pu"
                     )
                     ->innerJoin(
                        "YilinkerCoreBundle:Product",
                        "p",
                        Join::WITH,
                        "pu.product = p"
                     )
                     ->where("p.productId = :productId")
                     ->setParameter(":productId", $productId);

        if($isCount){
            return (int)$queryBuilder->getQuery()->getSingleScalarResult();
        }
        else{
            return $queryBuilder->getQuery()->getResult();
        }
    }
    
    public function countUnitWarehousesByProduct($productId)
    {
        $sql = "
            SELECT count(*) as cnt
            FROM ProductUnitWarehouse puw 
            INNER JOIN ProductUnit pu on puw.product_unit_id = pu.product_unit_id
            INNER JOIN Product p on pu.product_id = p.product_id
            WHERE p.product_id = :productId
            ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute(array(
            ":productId" => $productId
        ));
        $cnt = $stmt->fetch();
        
        return (int)$cnt['cnt'];
    }
}
