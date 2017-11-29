<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query\Expr\Join;
use DateTime;

/**
 * Class ManufacturerProductRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ManufacturerProductRepository extends EntityRepository
{
    const SORT_DIRECTION_ASC = 'asc';

    const SORT_DIRECTION_DESC = 'desc';

    const SORT_BY_NAME = 0;

    const SORT_BY_NEW_TO_OLD = 1;

    const SORT_BY_OLD_TO_NEW = 2;

    const SORT_BY_RELEVANCE = 3;

    /**
     * Get an active manufacturer product by ID
     *
     * @param int[] $manufacturerProductId
     * @param int[] $productCategoryIds
     * @return ManufacturerProduct
     */
    public function getActiveManufacturerProductsByIds(
        $manufacturerProductIds,
        $productCategoryIds = null,
        $country = null
    ){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $query = $qb->select('mp')
                    ->from('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct', 'mp')
                    ->where('mp.status = :activeStatus')
                    ->andWhere('mp.manufacturerProductId IN (:manufacturerProductIds)')
                    ->setParameter('activeStatus', ManufacturerProduct::STATUS_ACTIVE)
                    ->setParameter('manufacturerProductIds', $manufacturerProductIds);

        if($productCategoryIds !== null){
            if(is_array($productCategoryIds) === false){
                $productCategoryIds = array($productCategoryIds);
            }

            $qb->andWhere('mp.productCategory IN (:productCategories)')
               ->setParameter('productCategories', $productCategoryIds);
        }

        if($country){
            $qb->innerJoin(
                    'Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductCountry',
                    'mpc',
                    'WITH',
                    'mpc.country = :country'
                )
               ->setParameter(':country', $country);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    private $country = null;

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get manufacturer product
     *
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string $query
     * @param int[]|int $categoryId
     * @param int[]|int $excludeManufacturerProductIds
     * @param int $offset
     * @param int $limit
     * @param string $unitDateFrom
     * @param string $unitDateTo
     * @param boolean $countOnly
     * @param boolean $allowUnreviewed
     * @param int[] $manufacturerProductIds
     * @param mixed $orderby
     * @param int $affiliateUserId
     * @param bool $availableOnly
     * @return ManufacturerProduct[] | int
     */
    public function getActiveManufacturerProducts(
        $dateFrom = null,
        $dateTo = null,
        $queryString = null,
        $categoryId = null,
        $excludeManufacturerProductIds = null,
        $offset = 0,
        $limit = 15,
        $unitDateFrom = null,
        $unitDateTo = null,
        $countOnly = false,
        $allowUnreviewed = false,
        $manufacturerProductIds = null,
        $orderby = null,
        $affiliateUserId = null,
        $availableOnly = false
    ){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        if($countOnly){
            $qb->select('count(DISTINCT mp.manufacturerProductId)');
        }
        else{
            $qb->select('mp');
        }

        $manufacturerProductUnitDateFilter = "";
        if($unitDateFrom !== null){
            $manufacturerProductUnitDateFilter .= " AND mpu.dateLastModified >= :unitDateFrom";
        }
        if($unitDateTo !== null){
            $manufacturerProductUnitDateFilter .= " AND mpu.dateLastModified <= :unitDateTo";
        }

        $qb->from('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct', 'mp')
           ->innerJoin('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit', 'mpu', 'WITH',
                       'mpu.manufacturerProduct = mp.manufacturerProductId '.$manufacturerProductUnitDateFilter
           )
           ->innerJoin('Yilinker\Bundle\CoreBundle\Entity\Brand', 'brand', 'WITH',
                       'brand.brandId = mp.brand'
           )
           ->innerJoin('Yilinker\Bundle\CoreBundle\Entity\ProductCategory', 'category', 'WITH',
                       'category.productCategoryId = mp.productCategory'
           )
           ->where('mp.status = :activeStatus')
           ->andWhere('mpu.status = :activeManufacturerProductUnit')
           ->setParameter('activeStatus', ManufacturerProduct::STATUS_ACTIVE)
           ->setParameter('activeManufacturerProductUnit', ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ACTIVE);

        if($allowUnreviewed === false){
            $qb->innerJoin('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage', 'image', 'WITH',
                            'image.manufacturerProduct = mp.manufacturerProductId AND image.isDelete = false'
            )
                ->andWhere('mpu.quantity > 0')
                ->andWhere('mpu.retailPrice IS NOT NULL AND mpu.retailPrice > 0')
                ->andWhere('mpu.commission IS NOT NULL AND mpu.commission > 0');
        }
        else{
            $qb->leftJoin('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage', 'image', 'WITH',
                           'image.manufacturerProduct = mp.manufacturerProductId AND image.isDelete = false'
            );
        }

        /**
         * Retrieve only manufacturer products that have been selected by affiliate
         */
        if (is_null($affiliateUserId) === false){
            $qb->innerJoin('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap', 'map', 'WITH', 'mp.manufacturerProductId = map.manufacturerProduct')
               ->innerJoin('Yilinker\Bundle\CoreBundle\Entity\Product', 'p', 'WITH', 'map.product = p.productId AND p.user = :userId')
               ->setParameter('userId', $affiliateUserId);
        }

        /**
         * Only retrieve manufacturer products with quantity
         */
        if ($availableOnly){
            $qb->andWhere('mpu.quantity > 0');
        }

        if($manufacturerProductIds !== null && count($manufacturerProductIds)){
            $qb->andWhere('mp.manufacturerProductId IN (:manufacturerProductIds)')
                ->setParameter('manufacturerProductIds', $manufacturerProductIds);
        }

        if($unitDateFrom !== null){
            $qb->setParameter('unitDateFrom', $unitDateFrom);
        }
        if($unitDateTo !== null){
            $qb->setParameter('unitDateTo', $unitDateTo);
        }

        if($dateFrom !== null){
            $qb->andWhere('mp.dateAdded >= :dateFrom')
                  ->setParameter('dateFrom', $dateFrom);
        }

        if($dateTo !== null){
            $qb->andWhere('mp.dateAdded <= :dateTo')
                  ->setParameter('dateTo', $dateTo);
        }

        if($queryString !== null){

            if(!$countOnly){
                $qb->addSelect("match_against (mp.name) against (:queryString) as HIDDEN score");
                $qb->add('orderBy','score DESC');
            }
            $qb->andWhere("match_against (mp.name) against (:queryString) > 0")
               ->setParameter('queryString', $queryString.'*');
        }

        if($categoryId !== null){
            $categoryId = is_array($categoryId) ? $categoryId : array($categoryId);
            $qb->andWhere("mp.productCategory IN (:categoryId)")
               ->setParameter('categoryId', $categoryId);
        }

        if($this->country !== null){
            $qb->leftJoin('mp.manufacturerProductCountries', 'mpc')
                ->andWhere('mpc.country = :country')
                ->setParameter('country', $this->country);
        }

        if($excludeManufacturerProductIds){
            if(is_array($excludeManufacturerProductIds) === false){
                $excludeManufacturerProductIds = array($excludeManufacturerProductIds);
            }
            $qb->andWhere($qb->expr()->notIn('mp.manufacturerProductId', $excludeManufacturerProductIds));
        }

        $orderby = is_null($orderby) ? array('mp.dateAdded','ASC') : $orderby;

        $qb->setMaxResults($limit)
           ->setFirstResult($offset)
           ->addOrderBy($orderby[0], $orderby[1]);

        if($countOnly){
            $result = (int) $qb->getQuery()
                               ->getSingleScalarResult();
        }
        else{
            $result = $qb->groupBy("mp.manufacturerProductId")
                         ->getQuery()
                         ->getResult();
        }

        return  $result;
    }

    /**
     * Get manufacturer products by user id
     *
     * @param int $userId
     * @param int $manufacturerProductId
     * @return ManufacturerProduct[]
     */
    public function getManufacturerProductsByUser(
        $userId,
        $manufacturerProductId = null,
        $excludedStatus = null,
        $selectClause = null,
        $country = null
    ){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $sc = is_null($selectClause) ? 'mp' : $selectClause;

        $qb->select($sc)
           ->from('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct', 'mp')
           ->innerJoin('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductMap', 'map', 'WITH', 'mp.manufacturerProductId = map.manufacturerProduct')
           ->innerJoin('Yilinker\Bundle\CoreBundle\Entity\Product', 'p', 'WITH', 'map.product = p.productId AND p.user = :userId')
           ->setParameter('userId', $userId)
           ->groupBy('mp.manufacturerProductId');

        if(!is_null($excludedStatus)){
            if($country){
                $neq = $qb->expr()->neq("pc.status", ":status");
                $eq = $qb->expr()->eq("pc.country", ":country");
                $andx = $qb->expr()->andx()->add($neq)->add($eq);

                $qb->innerJoin(
                        'Yilinker\Bundle\CoreBundle\Entity\ProductCountry',
                        'pc',
                        Join::WITH,
                        'pc.product = p'
                    )
                   ->andWhere($andx)
                   ->setParameter(':status', $excludedStatus)
                   ->setParameter(':country', $country);
            }
            else{
                $neq = $qb->expr()->neq("p.status", ":status");
                $qb->andWhere($neq)
                   ->setParameter(':status', $excludedStatus);
            }
        }

        if(null !== $manufacturerProductId){
            $qb->andWhere('mp.manufacturerProductId = :manufacturerProductId')
               ->setParameter('manufacturerProductId', $manufacturerProductId);
        }

        return $qb->getQuery()
                  ->getResult();
    }

    /**
     * Retrieve manyfcaturer products by reference numbers
     *
     * @param string[] $referenceNumbers
     * @param boolean $status
     * @return ManufacturerProduct
     */
    public function getManufacturerProductsByReferenceNumbers($referenceNumbers, $status = ManufacturerProduct::STATUS_ACTIVE)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('mp')
            ->from('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct', 'mp')
            ->andWhere('mp.referenceNumber IN (:referenceNumbers)')
            ->setParameter('referenceNumbers', $referenceNumbers);

        if($status !== null){
            $qb->where('mp.status = :activeStatus')
               ->setParameter('activeStatus', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count manufacturer product
     *
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     * @param string $query
     * @param int $categoryId
     * @param int[]|int $excludeManufacturerProductIds
     * @return count
     */
    public function getCountManufacturerProducts(
        $dateFrom = null,
        $dateTo = null,
        $queryString = null,
        $categoryId = null,
        $excludeManufacturerProductIds = null
    ){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(mp)')
           ->from('Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct', 'mp')
           ->where('mp.status = :activeStatus')
           ->setParameter('activeStatus', ManufacturerProduct::STATUS_ACTIVE);

        if($dateFrom !== null){
            $qb->andWhere('mp.dateAdded >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if($dateTo !== null){
            $qb->andWhere('mp.dateAdded <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        if($queryString !== null){
            $qb->andWhere("match_against (mp.name) against (:queryString BOOLEAN) > 0")
               ->setParameter('queryString', $queryString.'*');
        }

        if($categoryId !== null){
            $qb->andWhere("mp.productCategory = :categoryId")
               ->setParameter('categoryId', $categoryId);
        }

        if($excludeManufacturerProductIds){
            if(is_array($excludeManufacturerProductIds) === false){
                $excludeManufacturerProductIds = array($excludeManufacturerProductIds);
            }
            $qb->andWhere($qb->expr()->notIn('mp.manufacturerProductId', $excludeManufacturerProductIds));
        }

        return  $qb->getQuery()
                   ->getSingleScalarResult();
    }

     /**
     * get manufacturer products that have been out of stock for one week
     */
    public function meantForInactive()
    {
        $lastweek = Carbon::now()->subWeek();
        $this
            ->qb()
            ->leftJoin('this.units', 'units', 'WITH', 'units.quantity > 0')
            ->andWhere('units IS NULL')
            ->andWhere('this.status = :manufacturerProductStatus')
            ->andWhere('this.dateLastEmptied <= :dateLastEmptied')
            ->setParameter('manufacturerProductStatus', ManufacturerProduct::STATUS_ACTIVE)
            ->setParameter('dateLastEmptied', $lastweek)
        ;

        return $this->getResult();
    }

    public function getManufacturerProducts($excludedStatus = array(), $offset = 0, $limit = 30)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("mp")
                     ->from("YilinkerCoreBundle:ManufacturerProduct", "mp")
                     ->where("NOT mp.status IN (:status)")
                     ->setParameter(":status", $excludedStatus)
                     ->orderBy("mp.name", "ASC");

        $paginator = new Paginator($queryBuilder->getQuery());
        $totalResultCount = $paginator->count();
        $totalPages = ceil($totalResultCount/$limit);

        $products = $queryBuilder->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return compact('totalResultCount', 'totalPages', 'products');
    }
}
