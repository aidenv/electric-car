<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Language;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class ProductCategoryRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ProductCategoryRepository extends EntityRepository
{
    const CATEGORY_IMAGE_DIR = 'images/uploads/category/';

    /**
     * Find category by slug
     *
     * @param string $slug
     * @return Yilinker\Bundle\Core
     */
    public function findCategoryBySlug($slug, $isDelete = null)
    {
        $qb = $this->_em
                   ->createQueryBuilder()
                   ->select("pc")
                   ->from("YilinkerCoreBundle:ProductCategory", "pc")
                   ->where('pc.slug = :slug')
                   ->setParameter('slug', $slug);

        if(!is_null($isDelete)){
            $qb->andWhere("pc.isDelete = :isDelete")->setParameter(":isDelete", $isDelete);
        }

        $result = $qb
            ->getQuery()
            ->useResultCache(true, 3600)
            ->getResult()
        ;

        return array_shift($result);
    }

    /**
     * Load product categories where in. index will be the product category id
     * @param $productCategoryIds
     * @return array
     */
    public function loadProductCategoriesIn($productCategoryIds, $isDelete = null)
    {
        $queryBuilder = $this->_em
                             ->createQueryBuilder()
                             ->select("pc")
                             ->from("YilinkerCoreBundle:ProductCategory", "pc", "pc.productCategoryId")
                             ->where("pc.productCategoryId IN (:productCategoryIds)")
                             ->setParameter(":productCategoryIds", $productCategoryIds);

        if(!is_null($isDelete)){
            $queryBuilder->andWhere("pc.isDelete = :isDelete")->setParameter(":isDelete", $isDelete);
        }

        $productCategories = $queryBuilder->getQuery()->getResult();

        return $productCategories;
    }

    /**
     * Get Child Category. 1 level only
     * @param int $parentId
     * @param int $limit
     * @param int $offset
     * @param string $queryString
     * @param DateTime $beginDate
     * @param DateTime $endDate
     * @param boolean $getAsEntity
     * @param boolean $activeOnly
     * @param Language $language
     * @return array
     */
    public function searchCategory(
        $parentId = null, $limit = null, $offset = null, $queryString = null,
        $beginDate = null, $endDate = null, $getAsEntity = false, $activeOnly = true,
        Language $language = null
    )
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        if($getAsEntity === false){
            $qb->select('productCategory.productCategoryId')
               ->addSelect('productCategory.name')
               ->addSelect('productCategory.image')
               ->addSelect('CASE WHEN COUNT(productCategoryChild.productCategoryId) > 0 THEN 1 ELSE 0 END AS hasChildren');
        }
        else{
            $qb->select('productCategory');
        }

        $qb->from('Yilinker\Bundle\CoreBundle\Entity\ProductCategory', 'productCategory')
           ->leftJoin(
               'Yilinker\Bundle\CoreBundle\Entity\ProductCategory',
               'productCategoryChild',
               'WITH',
               'productCategoryChild.parent = productCategory.productCategoryId'
           )
           ->groupBy('productCategory.productCategoryId');

        if($parentId !== null){
            $qb->where('productCategory.parent = :productCategoryId')
               ->andWhere('productCategory.productCategoryId != :parentProductCategoryId')
               ->setParameter('productCategoryId', $parentId, \PDO::PARAM_INT)
               ->setParameter('parentProductCategoryId', ProductCategory::ROOT_CATEGORY_ID, \PDO::PARAM_INT);
        }


        if($beginDate !== null){
            $qb->andWhere('productCategory.dateAdded >= :dateFrom')
               ->setParameter('dateFrom', $beginDate->format('Y-m-d H:i:s'));
        }

        if($endDate !== null){
            $qb->andWhere('productCategory.dateAdded < :dateTo')
               ->setParameter('dateTo', $endDate->format('Y-m-d H:i:s'));
        }

        if($limit !== null){
            $qb->setMaxResults($limit);
        }

        if($offset !== null){
            $qb->setFirstResult($offset);
        }

        if($queryString !== null && strlen($queryString)){
            $qb->andWhere("match_against (productCategory.name) against (:searchString BOOLEAN) > 0")
                         ->setParameter('searchString', $queryString.'*');
        }

        if($activeOnly){
            $qb->andWhere("productCategory.isDelete = false");
        }

        if($language){
            $qb->innerJoin(
                'Yilinker\Bundle\CoreBundle\Entity\ProductCategoryTranslation',
                'translation',
                'WITH',
                'productCategory = translation.productCategory AND translation.language = :language'
            )
                ->setParameter('language', $language);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get number of categories
     *
     * @param int $parentId
     * @param string $queryString
     * @param DateTime $beginDate
     * @param DateTime $endDate
     * @param Language $language
     * @return array
     */
    public function getCategoryCountBy($parentId = null, $queryString = null, $beginDate = null, $endDate = null, Language $language = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(productCategory.productCategoryId) as categoryCount')
           ->from('Yilinker\Bundle\CoreBundle\Entity\ProductCategory', 'productCategory');

        if($parentId !== null){
            $qb->where('productCategory.parent = :productCategoryId')
               ->andWhere('productCategory.productCategoryId != :parentProductCategoryId')
               ->setParameter('productCategoryId', $parentId, \PDO::PARAM_INT)
               ->setParameter('parentProductCategoryId', ProductCategory::ROOT_CATEGORY_ID, \PDO::PARAM_INT);
        }


        if($beginDate !== null){
            $qb->andWhere('productCategory.dateAdded >= :dateFrom')
               ->setParameter('dateFrom', $beginDate->format('Y-m-d H:i:s'));
        }

        if($endDate !== null){
            $qb->andWhere('productCategory.dateAdded < :dateTo')
               ->setParameter('dateTo', $endDate->format('Y-m-d H:i:s'));
        }

        if($queryString !== null && strlen($queryString)){
            $qb->andWhere("match_against (productCategory.name) against (:searchString BOOLEAN) > 0")
                         ->setParameter('searchString', $queryString.'*');
        }

        if($language){
            $qb->innerJoin(
                'Yilinker\Bundle\CoreBundle\Entity\ProductCategoryTranslation',
                'translation',
                'WITH',
                'productCategory = translation.productCategory AND translation.language = :language'
            )
                ->setParameter('language', $language);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get Categories by keyword
     * @param $categoryKeyword
     * @param int $limit
     * @param boolean $returnQB
     * @param boolean $entity
     * @param boolean $includeParent
     * @return ProductCategory
     */
    public function getCategoriesByKeyword (
        $categoryKeyword,
        $limit = PHP_INT_MAX,
        $returnQB = false,
        $entity = false,
        $includeParent = false
    )
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->select('productCategory')
                    ->from('Yilinker\Bundle\CoreBundle\Entity\ProductCategory', 'productCategory')
                    ->leftJoin(
                        'Yilinker\Bundle\CoreBundle\Entity\ProductCategory',
                        'productCategoryChild',
                        'WITH',
                        'productCategoryChild.parent = productCategory.productCategoryId'
                    )
                    ->where('productCategory.name LIKE :categoryKeyword')
                    ->andWhere('productCategory.isDelete = :isDelete')
                    ->setParameter(':categoryKeyword', '%' . $categoryKeyword . '%', \PDO::PARAM_STR)
                    ->setParameter(':isDelete', false)
                    ->groupBy('productCategory.productCategoryId')
                    ->setMaxResults($limit);

        if($includeParent === false){
            $qb->andWhere('productCategory.productCategoryId != :productCategoryId')
               ->setParameter(':productCategoryId', ProductCategory::ROOT_CATEGORY_ID, \PDO::PARAM_INT);
        }

        if (!$entity) {
            $query->addSelect('CASE WHEN COUNT(productCategoryChild.productCategoryId) > 0 THEN 1 ELSE 0 END AS hasChildren');
        }

        if ($returnQB) {
            return $query;
        }
        $query = $query->getQuery();

        return $query->getResult();
    }

    public function searchCategoryByKeyword($keyword = "", $isDelete = null, $limit = null, $offset = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("pc")
                     ->from("YilinkerCoreBundle:ProductCategory", "pc")
                     ->where($queryBuilder->expr()->like("pc.name", ":keyword"))
                     ->andWhere($queryBuilder->expr()->neq("pc.productCategoryId", ProductCategory::ROOT_CATEGORY_ID))
                     ->setParameter(":keyword", "%".$keyword."%");

        if(!is_null($isDelete)){
            $queryBuilder->andWhere($queryBuilder->expr()->eq("pc.isDelete", ":isDelete"))->setParameter(":isDelete", $isDelete);
        }

        if(!is_null($limit) && !is_null($offset)){
            $queryBuilder->setMaxResults($limit)->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get categories that are children of the root category
     *
     * @param string $orderBy
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductCategory[]
     */
    public function getMainCategories($orderBy = 'DESC', $orderType = 'sortOrder')
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('ProductCategory')
           ->from('Yilinker\Bundle\CoreBundle\Entity\ProductCategory', 'ProductCategory')
           ->where('ProductCategory.parent = :rootCategory')
           ->andWhere('ProductCategory.productCategoryId <> :rootCategory')
           ->andWhere('ProductCategory.isDelete = false')
           ->setParameter(':rootCategory', ProductCategory::ROOT_CATEGORY_ID)
           ->orderBy("ProductCategory.{$orderType}", $orderBy);

        return $qb->getQuery()
                  ->useResultCache(true, 86400)
	              ->getResult();
    }

    /**
     * Find cateory by parent id
     *
     * @param int $parentId
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductCategory[]
     */
    public function findCategoryByParentId(
        $parentId,
        $isDelete = null,
        $excludeRoot = false,
        $queryString = null,
        $crawlParent = true
    ){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('ProductCategory')
           ->from('Yilinker\Bundle\CoreBundle\Entity\ProductCategory', 'ProductCategory')
           ->orderBy('ProductCategory.sortOrder', 'ASC');

        if($crawlParent){
            $qb->where('ProductCategory.parent = :parentId')
               ->setParameter('parentId', $parentId);
        }

       if(!is_null($isDelete)){
            $qb->andWhere("ProductCategory.isDelete = :isDelete")
               ->setParameter(":isDelete", $isDelete);
       }

       if($excludeRoot){
            $qb->andWhere("ProductCategory.productCategoryId <> :rootCategory")
               ->setParameter(":rootCategory", ProductCategory::ROOT_CATEGORY_ID);
       }

       if($queryString){
           $like = $qb->expr()->like("ProductCategory.name", ":queryString");
           $qb->andWhere($like)->setParameter(":queryString", "%{$queryString}%");
       }

        return $qb->getQuery()
                  ->useResultCache(true, 86400)
	              ->getResult();
    }


    /**
     * Get User specialty category
     *
     * @param int[] $userIds
     * @return mixed
     */
    public function getUserSpecialtyIn($userIds)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("userId", "userId")
            ->addScalarResult("productCategoryId", "productCategoryId")
            ->addScalarResult("name", "name")
            ->addScalarResult("slug", "slug")
            ->addScalarResult("productCount", "productCount");

        $sql = "
            SELECT
                s.*
            FROM
            (
                SELECT
                    p.user_id as userId,
                    pc.product_category_id as productCategoryId,
                    pc.name as name,
                    pc.slug as slug,
                    COUNT(p.product_category_id) as productCount
                FROM
                    Product p
                INNER JOIN
                    ProductCategory pc
                ON
                    p.product_category_id = pc.product_category_id
                WHERE
                    p.user_id IN (:userIds)
                    AND pc.product_category_id <> :rootCategory
                AND NOT
                    p.status = :status
                GROUP BY
                    p.user_id,
                    p.product_category_id
                ORDER BY
                    productCount DESC, p.date_last_modified DESC
            ) s
            GROUP BY
                s.userId
        ";

        $query = $this->_em->createNativeQuery($sql, $rsm);

        $query->setParameter(":userIds", $userIds);
        $query->setParameter(":rootCategory", ProductCategory::ROOT_CATEGORY_ID);
        $query->setParameter(":status", Product::FULL_DELETE);

        $productCategories = $query->execute(array(), 'GroupHydrator');

        return $productCategories;
    }

    /**
     * Get the specialty user
     *
     * @param $user
     * @return mixed
     * @internal param $userId
     */
    public function getUserSpecialty($user)
    {
        $query = $this->_em
                      ->createQueryBuilder()
                      ->select("pc")
                      ->addSelect("COUNT(p.productCategory) as productCount")
                      ->from("YilinkerCoreBundle:Product", "p")
                      ->innerJoin("YilinkerCoreBundle:ProductCategory", "pc", Join::WITH, "p.productCategory = pc")
                      ->where("p.user = :user")
                      ->andWhere("pc.productCategoryId <> :rootCategory")
                      ->andWhere("p.status <> :status")
                      ->groupBy("p.user, p.productCategory")
                      ->orderBy("productCount", "DESC")
                      ->addOrderBy("p.dateLastModified", "DESC");

        $query->setParameter(":user", $user)
              ->setParameter(":rootCategory", ProductCategory::ROOT_CATEGORY_ID)
              ->setParameter(":status", Product::FULL_DELETE)
              ->setFirstResult(0)
              ->setMaxResults(1);

        $specialty = $query->getQuery()->getOneOrNullResult();

        return $specialty[0];
    }

    /**
     * Retrieve sibling categories
     *
     * @param int|ProductCategory $category
     * @param int $hydration
     * @return array
     */
    public function getRelated($category, $hydration = 1)
    {
        if (!($category instanceof ProductCategory)) {
            $category = $this->getEntityManager()->getReference('YilinkerCoreBundle:ProductCategory', $category);
        }
        $parent = $category->getParent() ? $category->getParent()->getProductCategoryId() : null;
        if (!$parent) {
            return array();
        }
        $query = $this->qb()
                      ->andWhere('this.parent = :parent')
                      ->andWhere('this.productCategoryId <> :productCategoryId')
                      ->setParameter('parent', $parent)
                      ->setParameter('productCategoryId', $category->getProductCategoryId())
                      ->getQB()
                      ->getQuery();

        return $query->getResult($hydration);
    }

    /**
     * Retrieve categories by slug
     *
     * @param string[] $slugs
     * @return ProductCategory[]
     */
    public function getCategoriesBySlug($slugs, $parentSlug = null)
    {
        $queryBuilder  = $this->_em
                              ->createQueryBuilder()
                              ->select("pc")
                              ->from("YilinkerCoreBundle:ProductCategory", "pc", "pc.productCategoryId")
                              ->where("pc.slug IN (:slugs)")
                              ->setParameter(":slugs", $slugs);

        if($parentSlug !== null){
            $queryBuilder->innerJoin(
                'Yilinker\Bundle\CoreBundle\Entity\ProductCategory',
                'parent',
                'WITH',
                'parent.slug = :parentSlug AND pc.parent = parent.productCategoryId'
            )->setParameter("parentSlug", $parentSlug);
        }

        $productCategories = $queryBuilder->getQuery()
                                          ->getResult();

        return $productCategories;
    }

    /**
     * Get Parent Category
     * @param  integer $categoryId
     * @return array
     */
    public function getParentCategory ($categoryId = ProductCategory::ROOT_CATEGORY_ID)
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('product_category_id', 'product_category_id');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('slug', 'slug');
        $query = $em->createNativeQuery("
            SELECT T2.product_category_id, T2.name, T2.slug
            FROM (
                   SELECT
                     @r AS _id,
                     (SELECT @r := parent_id FROM ProductCategory WHERE product_category_id = _id) AS parent_id,
                     @l := @l + 1 AS lvl
                   FROM
                     (SELECT @r := :categoryId, @l := 0) vars,
                     ProductCategory h
                   WHERE @r != :rootCategory) T1
              JOIN ProductCategory T2
                ON T1._id = T2.product_category_id
            ORDER BY T1.lvl DESC
        ", $rsm);
        $query->setParameter('categoryId', $categoryId);
        $query->setParameter('rootCategory', ProductCategory::ROOT_CATEGORY_ID);
        $results = $query->getArrayResult();

        return $results;
    }

    /**
     * @param $store Yilinker\Bundle\CoreBundle\Entity\Store
     *
     * @return $result[] Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    public function getCategoriesOfStore($store)
    {
        $user = $store->getUser();

        $result = $this->qb()
             ->leftJoin('this.products', 'products')
             ->andWhere('products.user = :user')
             ->setParameter('user', $user)
             ->getQB()
             ->getQuery()
             ->getResult()
        ;

        return $result;
    }

    /**
     * Load categories where in. index will be the id
     * @param ProductCategory $parent
     * @return array
     */
    public function loadParentProductCategories($parent, $queryString = "", $isDelete = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pc")
                     ->from("YilinkerCoreBundle:ProductCategory", "pc", "pc.productCategoryId")
                     ->where("pc.parent = :parent")
                     ->andWhere("NOT pc.productCategoryId = 1")
                     ->setParameter(":parent", $parent);

        if($queryString != "" && !is_null($queryString)){
            $likeExpr = $queryBuilder->expr()->like("pc.name", ":name");
            $queryBuilder->andWhere($likeExpr)
                         ->setParameter(":name", "%".$queryString."%");
        }

        if(!is_null($isDelete)){
            $queryBuilder->andWhere("pc.isDelete = :isDelete")->setParameter(":isDelete", $isDelete);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getProductCategoryByName($productCategoryId, $name)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pc")
                     ->from("YilinkerCoreBundle:ProductCategory", "pc")
                     ->where("pc.name = :name")
                     ->andWhere("NOT pc.productCategoryId = :productCategoryId")
                     ->setParameter(":name", $name)
                     ->setParameter(":productCategoryId", $productCategoryId);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retrieve manufacturer categories
     *
     * @param int $supplierId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param int $limit
     * @param int $offset
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    public function getManufacturerCategories($supplierId, \DateTime $dateFrom = null, \DateTime $dateTo = null, $limit = 10, $offset = 0)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $whereJoinQuery = 'mp.productCategory = pc.productCategoryId AND mp.manufacturer = :supplierId';
        if($dateFrom){
            $whereJoinQuery .= " AND mp.dateAdded >= :dateFrom";
        }
        if($dateTo){
            $whereJoinQuery .= " AND mp.dateAdded < :dateTo";
        }

        $queryBuilder->select("pc")
                     ->from("YilinkerCoreBundle:ProductCategory", "pc")
                     ->innerJoin(
                         'YilinkerCoreBundle:ManufacturerProduct',
                         'mp',
                         'WITH',
                         $whereJoinQuery
                     )
                     ->setParameter(":supplierId", $supplierId)
                     ->groupBy("pc.productCategoryId");

        if($dateFrom){
            $queryBuilder->setParameter(":dateFrom", $dateFrom);
        }
        if($dateTo){
            $queryBuilder->setParameter(":dateTo", $dateTo);
        }
        $queryBuilder->setMaxResults($limit)
                     ->setFirstResult($offset);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Count manufacturer categories
     *
     * @param int $supplierId
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @return int
     */
    public function countManufacturerCategories($supplierId, \DateTime $dateFrom = null, \DateTime $dateTo = null)
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('count', 'count');

        $subQuery = "
            SELECT
                ProductCategory.product_category_id
            FROM ProductCategory
            INNER JOIN
                ManufacturerProduct ON ManufacturerProduct.product_category_id = ProductCategory.product_category_id
                AND ManufacturerProduct.manufacturer_id = :supplierId
        ";

        if($dateFrom){
            $subQuery .= " AND ManufacturerProduct.date_added >= :dateFrom";
        }
        if($dateTo){
            $subQuery .= " AND ManufacturerProduct.date_added < :dateTo";
        }

        $query = $em->createNativeQuery("
            SELECT
                count(x.product_category_id) as count
            FROM (".$subQuery." GROUP BY ProductCategory.product_category_id) x
        ", $rsm);

        $query->setParameter("supplierId", $supplierId);
        if($dateFrom){
            $query->setParameter("dateFrom", $dateFrom->format('Y-m-d H:i:s'));
        }
        if($dateTo){
            $query->setParameter("dateTo", $dateTo->format('Y-m-d H:i:s'));
        }

        $result = $query->getResult();

        return (int) (isset($result[0]['count']) ? $result[0]['count'] : 0);
    }

    public function getParentCategoriesWithProductsByUser($userId, $queryString = "")
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('product_category_id', 'productCategoryId');
        $rsm->addScalarResult('name', 'name');

        $sql = "
            SELECT
                pc2.product_category_id,
                pc2.name
            FROM
            (
                SELECT
                    cns1.left,
                    cns1.right
                FROM
                    ProductCategory pc1
                INNER JOIN
                    Product p
                ON
                    pc1.product_category_id = p.product_category_id
                INNER JOIN
                    CategoryNestedSet cns1
                ON
                    cns1.product_category_id = pc1.product_category_id
                WHERE
                    p.user_id = :userId
                AND
                    p.status = 2
                GROUP BY
                    p.product_category_id
            ) r
            LEFT JOIN
                ProductCategory pc2
            ON
                1 = 1
            INNER JOIN
                CategoryNestedSet cns2
            ON
                pc2.product_category_id = cns2.product_category_id
            WHERE
                cns2.left <= r.left
            AND
                cns2.right >= r.right
            AND
                parent_id = :rootCategory
            AND NOT
                pc2.product_category_id = :rootCategory
        ";

        if($queryString != "" && !is_null($queryString)){
            $sql .= " AND pc2.name LIKE :queryString ";
        }

        $sql .= " GROUP BY pc2.product_category_id ";

        $query = $em->createNativeQuery($sql, $rsm);


        if($queryString != "" && !is_null($queryString)){
            $query->setParameter(":queryString", "%".$queryString."%");
        }

        $query->setParameter(":userId", $userId);
        $query->setParameter(":rootCategory", ProductCategory::ROOT_CATEGORY_ID);

        $result = $query->getResult();

        return $result;
    }

    public function getOneProductCategoryById($productCategoryId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('category')
           ->from("YilinkerCoreBundle:ProductCategory", 'category')
           ->where('category.productCategoryId = :id')
           ->setParameter('id', $productCategoryId);

        return $qb->getQuery()
                  ->useResultCache(true, 86400)
                  ->getOneOrNullResult();
    }

    public function getCategoriesOfUserProducts($user, $status = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pc")
                     ->from("YilinkerCoreBundle:ProductCategory", "pc")
                     ->innerJoin("YilinkerCoreBundle:Product", "p", Join::WITH, "p.productCategory = pc")
                     ->where("p.user = :user")
                     ->setParameter(":user", $user);

        if(!is_null($status)){
            if(is_array($status)){
                $queryBuilder->andWhere($queryBuilder->expr()->in("p.status", ":status"));
            }
            else{
                $queryBuilder->andWhere($queryBuilder->expr()->eq("p.status", ":status"));
            }
            $queryBuilder->setParameter(":status", $status);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getNativeNameById($categoryIds)
    {
        $categoryIds = implode(',', $categoryIds);

        $data = array();
        $conn = $this->_em->getConnection();

        $rs = $conn->fetchAll("
            SELECT pc.name
            FROM ProductCategory pc 
            WHERE pc.product_category_id IN ( $categoryIds )"
        );

        foreach  ($rs as $row) {
            $data[] = $row['name'];
        }

        return $data;
    }
}
