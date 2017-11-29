<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Doctrine\ORM\Query\ResultSetMapping;

use Yilinker\Bundle\CoreBundle\Services\Product\ProductService;

/**
 * Class ProductUnitRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ProductUnitRepository extends EntityRepository
{
    /**
     * Load product units where in. index will be the product id
     *
     * @param int[] $productUnitIds
     * @param null $limit
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductUnit[]
     */
    public function loadProductUnitsIn($productUnitIds, $limit = null, $quantityRequired = false, $status = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pu")
                     ->from("YilinkerCoreBundle:ProductUnit", "pu", "pu.productUnitId")
                     ->where("pu.productUnitId IN (:productUnitIds)")
                     ->setParameter(":productUnitIds", $productUnitIds);

        if($quantityRequired){
            $gtExpr = $queryBuilder->expr()->gt("pu.quantity", 0);
            $queryBuilder->andWhere($gtExpr);
        }

        if(!is_null($status)){
            $queryBuilder->innerJoin("YilinkerCoreBundle:Product", "p", Join::WITH, "pu.product = p")
                         ->andWhere("p.status = :status")
                         ->setParameter(":status", $status);
        }

        if(!is_null($limit)){
            $queryBuilder->setMaxResults($limit);
        }

        $productUnits = $queryBuilder->getQuery()->getResult();

        return $productUnits;
    }

    /**
     * get product units with promo today
     * @return object
     */
    public function getPromoProductUnits($limit = 10, $offset = 0)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $dateNow        = $queryBuilder->expr()->literal(Carbon::now()->format("Y-m-d H:i:s"));
        $dateTomorrow   = $queryBuilder->expr()->literal(Carbon::now()->format("Y-m-d H:i:s"));

        $andX = $queryBuilder->expr()->andX();
        $andX->add($queryBuilder->expr()->lte("pi.dateStart", $dateNow));
        $andX->add($queryBuilder->expr()->gte("pi.dateEnd", $dateTomorrow));
        $andX->add($queryBuilder->expr()->eq("pi.isEnabled", $queryBuilder->expr()->literal(true)));
        $andX->add($queryBuilder->expr()->eq("p.status", Product::ACTIVE));

        $queryBuilder->select("pu")
                     ->from("YilinkerCoreBundle:Product", "p")
                     ->innerJoin("YilinkerCoreBundle:ProductUnit", "pu", Join::WITH, "pu.product = p")
                     ->innerJoin("YilinkerCoreBundle:ProductPromoMap", "ppm", Join::WITH, "ppm.productUnit = pu")
                     ->innerJoin("YilinkerCoreBundle:PromoInstance", "pi", Join::WITH, "ppm.promoInstance = pi")
                     ->where($andX)
                     ->groupBy("p");

        $productUnitCollection["productUnits"] = $queryBuilder->setFirstResult($offset)
                                                              ->setMaxResults($limit)
                                                              ->getQuery()
                                                              ->useResultCache(true, 86400)
                                                              ->getResult();


        $productUnitCollection["totalResults"] = count($queryBuilder->setFirstResult(null)
                                                                    ->setMaxResults(null)
                                                                    ->getQuery()
                                                                    ->useResultCache(true, 86400)
                                                                    ->getResult());

        return $productUnitCollection;
    }

    public function getPromoProductUnitsIn($productIds, $excludedInstance = null, $dateStart = null, $dateEnd = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pu")
                     ->from("YilinkerCoreBundle:ProductUnit", "pu")
                     ->innerJoin("YilinkerCoreBundle:Product", "p", Join::WITH, "pu.product = p")
                     ->innerJoin("YilinkerCoreBundle:ProductPromoMap", "ppm", Join::WITH, "ppm.productUnit = pu")
                     ->innerJoin("YilinkerCoreBundle:PromoInstance", "pi", Join::WITH, "ppm.promoInstance = pi")
                     ->where($queryBuilder->expr()->in("p.productId", ":productIds"))
                     ->andWhere("pi.isEnabled = :isEnabled")
                     ->setParameter(":isEnabled", true);

        if(!is_null($excludedInstance)){
            $queryBuilder->andWhere("NOT ppm.promoInstance = :excludedInstance")
                         ->setParameter(":excludedInstance", $excludedInstance);
        }

        if(!is_null($dateStart) && !is_null($dateEnd)){
            $dateStartFilter = $queryBuilder->expr()->between("pi.dateStart", ":dateStart", ":dateEnd");
            $dateEndFilter = $queryBuilder->expr()->between("pi.dateEnd", ":dateStart", ":dateEnd");

            $orx = $queryBuilder->expr()->orx();
            $orx->add($dateStartFilter)->add($dateEndFilter);

            $queryBuilder->andWhere($orx)
                         ->setParameter(":dateStart", $dateStart)
                         ->setParameter(":dateEnd", $dateEnd);
        }

        $queryBuilder->setParameter(":productIds", $productIds);

        return $queryBuilder->getQuery()
                            ->useResultCache(true, 86400)
	                          ->getResult();
    }

    public function getPromoUnitsIn($productUnitIds, $excludedInstance = null, $dateStart = null, $dateEnd = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pu")
                     ->from("YilinkerCoreBundle:ProductUnit", "pu")
                     ->innerJoin("YilinkerCoreBundle:ProductPromoMap", "ppm", Join::WITH, "ppm.productUnit = pu")
                     ->innerJoin("YilinkerCoreBundle:PromoInstance", "pi", Join::WITH, "ppm.promoInstance = pi")
                     ->where($queryBuilder->expr()->in("pu.productUnitId", ":productUnitIds"))
                     ->andWhere("pi.isEnabled = :isEnabled")
                     ->setParameter(":isEnabled", true);

        if(!is_null($excludedInstance)){
            $queryBuilder->andWhere("NOT ppm.promoInstance = :excludedInstance")
                         ->setParameter(":excludedInstance", $excludedInstance);
        }

        if(!is_null($dateStart) && !is_null($dateEnd)){

            $gteDateStartOnDateStart = $queryBuilder->expr()->gte("pi.dateStart", ":dateStart");
            $lteDateStartOnDateEnd = $queryBuilder->expr()->lte("pi.dateStart", ":dateEnd");

            $gteDateEndOnDateStart = $queryBuilder->expr()->gte("pi.dateEnd", ":dateStart");
            $lteDateEndOnDateEnd = $queryBuilder->expr()->lte("pi.dateEnd", ":dateEnd");

            $dateStartFilter = $queryBuilder->expr()
                                            ->andx()
                                            ->add($gteDateStartOnDateStart)
                                            ->add($lteDateStartOnDateEnd);

            $dateEndFilter = $queryBuilder->expr()
                                          ->andx()
                                          ->add($gteDateEndOnDateStart)
                                          ->add($lteDateEndOnDateEnd);

            $orx = $queryBuilder->expr()->orx();
            $orx->add($dateStartFilter)->add($dateEndFilter);

            $queryBuilder->andWhere($orx)
                         ->setParameter(":dateStart", $dateStart)
                         ->setParameter(":dateEnd", $dateEnd);
        }

        $queryBuilder->setParameter(":productUnitIds", $productUnitIds);

        return $queryBuilder->getQuery()
                            ->useResultCache(true, 86400)
                            ->getResult();
    }

    public function getPromoInstanceProductUnits(
        array $promoInstances,
        $limit = null,
        $offset = null,
        $dateNow = null,
        $isOrdered = false
    ){
        $dql = "
            SELECT
                SUM(op.quantity)
            FROM
                YilinkerCoreBundle:OrderProductHistory oph
            INNER JOIN
                YilinkerCoreBundle:OrderProduct op
            WITH
                oph.orderProduct = op
            WHERE
                oph.dateAdded BETWEEN pi.dateStart AND pi.dateEnd AND
                op.product = p AND
                (
                    oph.orderProductStatus = :paymentConfirmed OR
                    oph.orderProductStatus = :codTransactionConfirmed
                )
        ";

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pu as productUnit,pi.promoInstanceId as promoInstanceId")
                     ->addSelect("({$dql}) as productsSold")
                     ->addSelect("(SUM(ppm.maxQuantity)) as maxQuantity")
                     ->from("YilinkerCoreBundle:ProductUnit", "pu")
                     ->innerJoin("YilinkerCoreBundle:Product", "p", Join::WITH, "pu.product = p")
                     ->innerJoin("YilinkerCoreBundle:ProductPromoMap", "ppm", Join::WITH, "ppm.productUnit = pu")
                     ->innerJoin("YilinkerCoreBundle:PromoInstance", "pi", Join::WITH, "ppm.promoInstance = pi")
                     ->where("ppm.promoInstance IN (:promoInstances)")
                     ->andWhere("p.status = :status")
                     ->andWhere("pi.isEnabled = :isEnabled")
                     ->groupBy("p")
                     ->orderBy("ppm.sortOrder", "ASC")
                     ->setParameter(":promoInstances", $promoInstances)
                     ->setParameter(":status", Product::ACTIVE)
                     ->setParameter(":isEnabled", true)
                     ->setParameter(":paymentConfirmed", OrderProductStatus::PAYMENT_CONFIRMED)
                     ->setParameter(":codTransactionConfirmed", OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED);

        if(!is_null($dateNow)){
            $queryBuilder->andWhere("pi.dateEnd <= :dateNow")->setParameter(":dateNow", $dateNow);
        }

        if(!is_null($limit) && !is_null($offset)){
            $queryBuilder->setFirstResult($offset)->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getLatestUploadedProducts($user = null, $status = null, $limit = null, $offset = null)
    {
        $queryBuilder = $this->createQueryBuilder("pu");

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("productUnitId", "productUnitId");
        
        $sql = " 
            SELECT  pu.product_unit_id as productUnitId
            FROM ProductUnit pu JOIN Product p on pu.product_id = p.product_id
            WHERE 1
        ";

        $sql .= " GROUP BY p.product_id ";

        if(!is_null($user)){
            $sql .= " AND p.user_id = {$user->getUserId()}";
        }

        if(!is_null($status)){
            $sql .= " AND p.status = {$status}";
        }

        if(!is_null($limit)){
            $sql .= " LIMIT {$limit}";
        }

        if(!is_null($offset)){
            //$queryBuilder->setFirstResult($offset);
        }

        $query = $this->_em->createNativeQuery($sql, $rsm);

        $results = $query->getResult();

        $productUnitIds = array_map(function($result) {
            return $result['productUnitId'];
        }, $results);

        $qb = $this->createQueryBuilder("pu");
        $qb->andWhere(
            $qb->expr()->in('pu.productUnitId', $productUnitIds)
        );

        $rs = $qb->getQuery()
            ->useResultCache(true, 3600)
            ->getResult();                                  

        return $rs;
    }

    public function trueQuantity($unitId, $quantity)
    {
        $productUnit = $this->find($unitId);

        if ($productUnit) {
            $supplyQuantity = $productUnit->getQuantity();
            if ($quantity > $supplyQuantity) {
                $quantity = $supplyQuantity;
            }
        }

        return $quantity;
    }

    public function getPromoProducts($promoInstanceId, $limit = 3)
    {
        $paymentConfirmed = OrderProductStatus::PAYMENT_CONFIRMED;
        $codConfirmed = OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED;

        $sql = "
            SELECT
                p.product_id as productId,
                pu.product_unit_id as productUnitId,
                pu.price as price,
                ppm.discounted_price as discountedPrice,
                p.name as name,
                p.slug,
                pi.date_start as dateStart,
                pi.date_end as dateEnd,
                SUM(ppm.max_quantity) as maxQuantity,
                (
                    SELECT
                        SUM(op.quantity)
                    FROM
                        OrderProductHistory oph
                    INNER JOIN
                        OrderProduct op
                    ON
                        oph.order_product_id = op.order_product_id
                    WHERE
                        oph.date_added
                    BETWEEN
                        pi.date_start
                    AND
                        pi.date_end
                    AND
                        op.product_id = p.product_id AND
                        (
                            oph.order_product_status_id = {$paymentConfirmed} OR
                            oph.order_product_status_id = {$codConfirmed}
                        )
                ) as productsSold
            FROM
                ProductUnit pu
            INNER JOIN
                Product p
            ON
                pu.product_id = p.product_id
            INNER JOIN
                ProductPromoMap ppm
            ON
                ppm.product_unit_id = pu.product_unit_id
            INNER JOIN
                PromoInstance pi
            ON
                pi.promo_instance_id = ppm.promo_instance_id
            WHERE
                pi.promo_instance_id = :promoInstanceId
            AND
                pi.is_enabled = 1
            GROUP BY
                p.product_id
            ORDER BY
                ppm.sort_order ASC
            LIMIT {$limit}
        ";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute(array(
            ":promoInstanceId" => $promoInstanceId
        ));

        return $stmt->fetchAll();
    }

    public function getProductUnitByInhouseAffiliates($sku)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pu")
            ->from("YilinkerCoreBundle:ProductUnit", "pu")
            ->innerJoin("YilinkerCoreBundle:Product", "p",  Join::WITH, "pu.product = p")
            ->innerJoin(
                "YilinkerCoreBundle:Store", "s",
                Join::WITH, "s.user = p.user AND s.isInhouse = :inhouse AND s.storeType = :affiliate"
            )
            ->andWhere("pu.sku = :sku")
            ->setParameter("sku", $sku)
            ->setParameter("affiliate", Store::STORE_TYPE_RESELLER)
            ->setParameter("inhouse", true);

        return $queryBuilder->getQuery()
                            ->getResult();
    }

    public function getProductUnitWarehouseByUser($user, $userWarehouse)
    {
        $this->qb()
             ->leftJoin('this.product', 'product')
             ->andWhere('product.user = :user')
             ->setParameter('user', $user)
             ->leftJoin(
                 'YilinkerCoreBundle:ProductUnitWarehouse',
                 'productUnitWarehouse',
                 Join::WITH,
                 'productUnitWarehouse.productUnit = this AND productUnitWarehouse.userWarehouse = :userWarehouse'
             )
             ->setParameter('userWarehouse', $userWarehouse);

        return $this;
    }

    public function getProductUnitByUserSkus($user, $skus)
    {
        $this
            ->qb()
            ->innerJoin('this.product', 'product')
            ->andWhere('product.user = :user')
            ->setParameter('user', $user)
            ->andWhere('this.sku IN (:skus)')
            ->setParameter('skus', $skus)
        ;

        return $this;
    }

    public function filterByProduct(array $filter)
    {
        $perProdctStatus = ProductService::statusPerProduct();

        if (isset($filter['status']) && strlen($filter['status'])) {

            $this->getQB()->leftJoin('product.productCountries', 'productCountry');

            if (in_array($filter['status'], $perProdctStatus)) {
                $this->getQB()->andWhere("productCountry IS NULL")
                              ->andWhere("product.status = :productStatus");
            }
            else {
                $this->getQB()->andWhere('productCountry.status = :productStatus');
            }

            $this->getQB()->setParameter('productStatus', $filter['status']);
        }

        if (isset($filter['productCategory']) && !empty($filter['productCategory'])) {
            $this->getQB()->andWhere('product.productCategory IN (:productCategory)')
                          ->setParameter('productCategory', $filter['productCategory']);
        }

        if (isset($filter['name']) && strlen($filter['name'])) {
            $this->getQB()->andWhere('product.name LIKE :productName')
                          ->setParameter('productName', '%'.$filter['name'].'%');
        }

        if (isset($filter['productGroup']) && strlen($filter['productGroup'])) {
            $this->getQB()
                 ->innerJoin('product.productGroups', 'productGroup', Join::WITH, 'productGroup.product = product')
                 ->andWhere('productGroup.userProductGroup = :productGroup')
                 ->setParameter('productGroup', $filter['productGroup']);
        }

        $this->getQB()->addOrderBy('product.dateLastModified', 'DESC');

        return $this;
    }

    public function filterByProductIn(array $filter)
    {
        $this->filterByProduct($filter);

         if (isset($filter['statusIn']) && !empty($filter['statusIn'])) {
            $perProdctStatus = ProductService::statusPerProduct();
            $this->getQB()
                 ->leftJoin('product.productCountries', 'productCountry')
                 ->andWhere('productCountry.status IN (:productStatus)')
                 ->setParameter('productStatus', $filter['statusIn']);;
        }

        if (isset($filter['productGroupIn']) && !empty($filter['productGroupIn'])) {
            $this->getQB()
                 ->innerJoin('product.productGroups', 'productGroup', Join::WITH, 'productGroup.product = product')
                 ->andWhere('productGroup.userProductGroup IN (:productGroup)')
                 ->setParameter('productGroup', $filter['productGroupIn']);
        }

        return $this;
    }

    public function getAllSkuByUser($user, $product = null)
    {
        $sql = "
            SELECT
                pu.sku
            FROM
                ProductUnit pu
            INNER JOIN
                Product p
            ON
                pu.product_id = p.product_id
            WHERE
                p.user_id = :user_id
        ";

        if(!is_null($product)){
            $sql .= " AND NOT p.product_id = :product_id";
        }

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        $params = array(
            ":user_id" => $user->getUserId()
        );

        if(!is_null($product)){
            $params[":product_id"] = $product->getProductId();
        }

        $stmt->execute($params);

        $results = $stmt->fetchAll();

        $skus = array();
        foreach($results as $result){
            array_push($skus, $result["sku"]);
        }

        return $skus;
    }

    /**
     * Retrieves the product unit by id
     *
     * @param int $productUnitId
     * @return Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    public function findProductUnitByIdCached($productUnitId)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("pu")
                     ->from("YilinkerCoreBundle:ProductUnit", "pu")
                     ->where("pu.productUnitId = :productUnitId")
                     ->setParameter(":productUnitId", $productUnitId);

        return $queryBuilder->getQuery()
                            ->useResultCache(true, 3600)
                            ->getOneOrNullResult();
    }

    /**
     * Same as NOT IN in mysql
     *
     * @param Product $product
     * @param $field
     * @param $values
     * @return array
     */
    public function findByNot (Product $product, $field, $values)
    {
        if (!is_array($values)) {
            $values[] = $values;
        }

        $qb = $this->createQueryBuilder('this');
        $qb->where('this.' . $field . ' NOT IN (:values)')
            ->andWhere('this.product = :productId')
            ->setParameter('productId', $product)
            ->setParameter('values', $values);

        return $qb->getQuery()->getResult();
    }

}
