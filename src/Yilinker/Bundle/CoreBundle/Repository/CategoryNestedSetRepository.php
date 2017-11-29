<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\CategoryNestedSet;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Class CategoryNestedSetRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class CategoryNestedSetRepository extends EntityRepository
{
    /**
     * Retrieve children categories via nested set
     *
     * @param int $categoryId
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductCategory[]
     */
    public function getChildrenCategories($categoryId = ProductCategory::ROOT_CATEGORY_ID, $raw = false)
    {
        $cacheDriver = $this->_em->getConfiguration()->getResultCacheImpl();
        $queryBuilder  = $this->_em
                              ->createQueryBuilder()
                              ->select("t1")
                              ->from("YilinkerCoreBundle:CategoryNestedSet", "t1")
                              ->leftJoin("YilinkerCoreBundle:CategoryNestedSet", "t2", "WITH", "t2.productCategory = :category_id")
                              ->where("t1.left > t2.left")
                              ->andWhere("t1.right < t2.right")
                              ->setParameter(":category_id", $categoryId);

        $nestedSetCategories = $queryBuilder->getQuery()
                                            ->useResultCache(true, 7200)
                                            ->getResult();
       
        if(!$raw){
            $key = "CategoryNestedSetRepository::getChildrenCategories-".$categoryId;
            $productCategories = $cacheDriver->fetch($key); 
            if(!$productCategories){
                $productCategories = array();
                foreach($nestedSetCategories as $nestedSetCategory){
                    array_push($productCategories, $nestedSetCategory->getProductCategory());
                }

                $cacheDriver->save($key, $productCategories, 3600); 
            }
            
            return $productCategories;
        }

        return $nestedSetCategories;
    }

    /**
     * Retrieve children categories id via nested set (uses native sql for speed)
     *
     * @param int $categoryId
     * @return int[]
     */
    public function getChildrenCategoryIds($categoryId)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('product_category_id', 'product_category_id');

        $sql = "SELECT
                t1.product_category_id
            FROM CategoryNestedSet t1
            LEFT JOIN
                 CategoryNestedSet t2 ON t2.product_category_id = :category_id
            WHERE
                t1.left > t2.left AND t1.right < t2.right
        ";

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter('category_id', $categoryId);
        $results = $query->getResult();

        $categoryIds = array();
        foreach($results as $result){
            $categoryIds[] = $result['product_category_id'];
        }

        return $categoryIds;
    }
   
    /**
     * Retrieve parent and children categories via nested set
     *
     * @param int $categoryId
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductCategory[]
     */
    public function getAllCategoriesByCategoryId($categoryId = ProductCategory::ROOT_CATEGORY_ID, $raw = false)
    {
        $queryBuilder  = $this->_em
                              ->createQueryBuilder()
                              ->select("t1")
                              ->from("YilinkerCoreBundle:CategoryNestedSet", "t1")
                              ->leftJoin("YilinkerCoreBundle:CategoryNestedSet", "t2", "WITH", "t2.productCategory = :category_id")
                              ->where("t1.left >= t2.left")
                              ->andWhere("t1.right <= t2.right")
                              ->setParameter(":category_id", $categoryId);

        $nestedSetCategories = $queryBuilder->getQuery()
                                            ->getResult();

        if(!$raw){
            $categories = array();
            foreach($nestedSetCategories as $nestedSetCategory){
                array_push($categories, $nestedSetCategory->getProductCategory());
            }

            return $categories;
        }

        return $nestedSetCategories;
    }

    /**
     * Get ancestor categories
     *
     * @param int $categoryId
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductCategory[]
     */
    public function getAncestorCategories($categoryId = ProductCategory::ROOT_CATEGORY_ID)
    {
        $queryBuilder  = $this->_em
                              ->createQueryBuilder()
                              ->select("t1")
                              ->from("YilinkerCoreBundle:CategoryNestedSet", "t0")
                              ->leftJoin("YilinkerCoreBundle:CategoryNestedSet", "t1", "WITH", "t1.left < t0.left AND t1.right > t0.right")
                              ->where("t0.productCategory = :categoryId")
                              ->andWhere("t1.productCategory != :rootCategory")
                              ->setParameter(":categoryId", $categoryId)
                              ->setParameter(":rootCategory", ProductCategory::ROOT_CATEGORY_ID);

        $nestedSetCategories = $queryBuilder->getQuery()
                                            ->useResultCache(true, 7200)
                                            ->getResult();

        $categories = array();
        foreach($nestedSetCategories as $nestedSetCategory){
            array_push($categories, $nestedSetCategory->getProductCategory());
        }

        return $categories; 
    }
}

