<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class ProductImageRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ProductImageRepository extends EntityRepository
{
    public function getImageLocationsByProduct($product)
    {
        $sql = "
            SELECT
                pi.imageLocation
            FROM
                ProductImage pi
            WHERE
                pi.product_id = :product_id
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        $params = array(
            ":product_id" => $product->getProductId()
        );

        $stmt->execute($params);
        
        $results = $stmt->fetchAll();

        $images = array();
        foreach($results as $result){
            array_push($images, $result["imageLocation"]);
        }

        return $images;

    }

    public function filterImages(
        $imageLocations = array(),
        $product = null,
        $locale = null,
        $indexByLocation = false,
        $excludeImageLocations = false,
        $productUnit = null,
        $isDeleted = null
    ){
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pi");

        if($indexByLocation){
            $queryBuilder->from("YilinkerCoreBundle:ProductImage", "pi", "pi.imageLocation");
        }
        else{
            $queryBuilder->from("YilinkerCoreBundle:ProductImage", "pi");
        }

        if(!empty($imageLocations)){
            if(!$excludeImageLocations){
                $queryBuilder->andWhere("pi.imageLocation IN (:imageLocations)")
                             ->setParameter(":imageLocations", $imageLocations);
            }
            else{
                $queryBuilder->andWhere("pi.imageLocation NOT IN (:imageLocations)")
                             ->setParameter(":imageLocations", $imageLocations);
            }
        }

        if($product){
            $queryBuilder->andWhere("pi.product = :product")
                         ->setParameter(":product", $product);
        }

        if($productUnit){
            $queryBuilder
                ->innerJoin("YilinkerCoreBundle:ProductUnitImage", "pui", Join::WITH, "pui.productImage = pi")
                ->andWhere("pui.productUnit = :productUnit")
                ->setParameter(":productUnit", $productUnit);
        }

        if($locale){
            $queryBuilder->andWhere("pi.defaultLocale = :locale")
                         ->setParameter(":locale", $locale);
        }

        if(!is_null($isDeleted)){
            $queryBuilder->andWhere("pi.isDeleted = :isDeleted")
                         ->setParameter(":isDeleted", $isDeleted);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
