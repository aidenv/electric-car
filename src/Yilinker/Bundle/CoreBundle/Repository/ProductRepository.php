<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\Cart;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;

/**
 * Class ProductRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ProductRepository extends EntityRepository
{
    /**
     * Order search relevance
     */
    const RELEVANCE = 'RELEVANCE';

    /**
     * Order alphabetically
     */
    const ALPHABETICAL = 'ALPHABETICAL';

    /**
     * Order by price
     */
    const BYPRICE = 'BYPRICE';

    /**
     * Order by date modified
     */
    const BYDATE = 'BYDATE';

    /**
     * Order by clickount
     */
    const BYPOPULARITY = 'BYPOPULARITY';

    /**
     * Sort direction: descending
     */
    const DIRECTION_DESC = 'DESC';

    /**
     * Sort direction: ascending
     */
    const DIRECTION_ASC = 'ASC';

    /**
     * Retrieves the product by id
     *
     * @param int $productId
     * @return Yilinker\Bundle\CoreBundle\Entity\Product
     */
    public function findProductByIdCached($productId)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p")
                     ->where("p.productId = :productId")
                     ->setParameter(":productId", $productId);

        return $queryBuilder->getQuery()
                            ->useResultCache(true, 3600)
                            ->getOneOrNullResult();
    }

    /**
     * Load products where in. index will be the product id
     *
     * @param int[] $productIds
     * @return Yilinker\Bundle\CoreBundle\Entity\Product[]
     */
    public function loadProductsIn($productIds, $quantityRequired = false, $status = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->where("p.productId IN (:productIds)")
                     ->setParameter(":productIds", $productIds);

        if($quantityRequired){

            $orx = $queryBuilder->expr()->orx();
            $andx = $queryBuilder->expr()->andx();

            $merchantProductExpr = $queryBuilder->expr()->gt("pu.quantity", 0);
            $manufacturerProduct = $queryBuilder->expr()->gt("mpu.quantity", 0);
            $activeManufacturerProduct = $queryBuilder->expr()->eq("mp.status", ManufacturerProduct::STATUS_ACTIVE);

            $andx->add($manufacturerProduct)->add($activeManufacturerProduct);

            $orx->add($merchantProductExpr);
            $orx->add($andx);

            $queryBuilder->innerJoin("YilinkerCoreBundle:ProductUnit", "pu", Join::WITH, "pu.product = p")
                         ->innerJoin("YilinkerCoreBundle:User", "u", Join::WITH, "u = p.user")
                         ->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u")
                         ->leftJoin("YilinkerCoreBundle:ManufacturerProductMap", "mpm", Join::WITH, "mpm.product = p")
                         ->leftJoin("YilinkerCoreBundle:ManufacturerProduct", "mp", Join::WITH, "mpm.manufacturerProduct = mp")
                         ->leftJoin("YilinkerCoreBundle:ManufacturerProductUnit", "mpu", Join::WITH, "mpu.manufacturerProduct = mp")
                         ->andWhere($orx)
                         ->groupBy("p");
        }

        if(!is_null($status)){
            $queryBuilder->andWhere("p.status = :status")
                         ->setParameter(":status", $status);
        }

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }

    public function searchUserProducts(
        User $user,
        $keyword,
        $status = null,
        $orderBy = array(),
        $offset = null,
        $limit = null,
        $country = null,
        $isCount = false
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        if($isCount){
            $queryBuilder->select("count(p)");
        }
        else{
            $queryBuilder->select("p");
        }

        $queryBuilder->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->where("p.name LIKE :name")
                     ->andWhere("p.user = :user");
        if ($orderBy) {
            foreach ($orderBy as $orderColumn => $orderDirection) {
                $queryBuilder->orderBy("p.$orderColumn" , $orderDirection);
            }
        }

        if(!is_null($status)){
            if(is_array($status) === false){
                $status = array($status);
            }

            if($country){

                $expr = $queryBuilder->expr()->andx();
                $expr->add($queryBuilder->expr()->eq("pc.country", ":country"))
                     ->add($queryBuilder->expr()->in("pc.status", ":status"));

                $queryBuilder->innerJoin(
                                "YilinkerCoreBundle:ProductCountry",
                                "pc",
                                Join::WITH,
                                "pc.product = p"
                             );

                $queryBuilder->andWhere($expr)
                             ->setParameter(":country", $country)
                             ->setParameter(":status", $status);
            }
            else{
                $queryBuilder->andWhere("p.status IN (:status)")
                             ->setParameter(":status", $status);
            }
        }

        $queryBuilder->setParameter(":name", "%".$keyword."%")->setParameter(":user", $user);

        if($offset !== null){
            $queryBuilder->setFirstResult($offset);
        }
        if($limit !== null){
            $queryBuilder->setMaxResults($limit);
        }

        if($isCount){
            return (int) $queryBuilder->getQuery()->getSingleScalarResult();
        }
        else{
            return $queryBuilder->getQuery()->getResult();
        }
    }

    public function loadUserProductsIn(User $user, array $productIds)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->where("p.productId IN (:productIds)")
                     ->setParameter(":productIds", $productIds);
        if ($user->isAffiliate(false)) {
            $queryBuilder
                ->innerJoin('p.inhouseProductUsers', 'inhouseProductUsers')
                ->andWhere('inhouseProductUsers.user = :user')
                ->setParameter('user', $user)
            ;
        }
        else {
            $queryBuilder
                ->andWhere("p.user = :user")
                ->setParameter(":user", $user)
            ;
        }

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }

    public function loadUserProducts(User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->andWhere("p.user = :user")
                     ->setParameter(":user", $user);

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }

    public function loadUserProductsByParentCategory(User $user, $status, $left, $right)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $dql = "
                SELECT
                    pc2
                FROM
                    YilinkerCoreBundle:CategoryNestedSet cns2
                INNER JOIN
                    YilinkerCoreBundle:ProductCategory pc2
                WITH
                    cns2.productCategory = pc2
                WHERE
                    cns2.left >= :left
                AND
                    cns2.right <= :right
        ";

        $inexpr = $queryBuilder->expr()->in("p.productCategory", $dql);

        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->innerJoin("YilinkerCoreBundle:ProductCategory", "pc", Join::WITH, "p.productCategory = pc")
                     ->innerJoin("YilinkerCoreBundle:CategoryNestedSet", "cns", Join::WITH, "cns.productCategory = pc")
                     ->andWhere("p.user = :user")
                     ->andWhere("p.status = :status")
                     ->andWhere($inexpr)
                     ->setParameter(":status", $status)
                     ->setParameter(":left", $left)
                     ->setParameter(":right", $right)
                     ->setParameter(":user", $user);

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }

    /**
     * @param CustomizedCategory $customizedCategory
     * @param User $user
     * @param int[] $productIds
     * @return Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup[]
     */
    public function loadProductsOfCustomCategoryIn(CustomizedCategory $customizedCategory, User $user, $productIds)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->innerJoin("YilinkerCoreBundle:CustomizedCategoryProductLookup", "ccpl", Join::WITH, "p = ccpl.product")
                     ->andWhere("ccpl.customizedCategory = :customizedCategory")
                     ->andWhere("p.productId IN (:productIds)")
                     ->andWhere("p.user = :user")
                     ->setParameter(":customizedCategory", $customizedCategory)
                     ->setParameter(":productIds", $productIds)
                     ->setParameter(":user", $user);

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }
    /**
     * @param CustomizedCategory $customizedCategory
     * @param User $user
     * @return Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup[]
     */
    public function loadAllProductsOfCustomCategory(CustomizedCategory $customizedCategory, User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p, p2")
            ->from("YilinkerCoreBundle:Product", "p")
            ->innerJoin("YilinkerCoreBundle:CustomizedCategoryProductLookup", "ccpl", Join::WITH, "p = ccpl.product")
            ->leftJoin("YilinkerCoreBundle:Product", "p2", Join::WITH, "p2.user = :user")
            ->andWhere("ccpl.customizedCategory = :customizedCategory")
            ->andWhere("p.user = :user")
            ->andWhere("p.status = :status")
            ->andWhere("p2.status = :status")
            ->setParameter(":customizedCategory", $customizedCategory)
            ->setParameter(":status", Product::ACTIVE)
            ->setParameter(":user", $user);

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }

    public function getProductUnit($productId, $unitId)
    {
        return $this->qb()
                    ->andWhere("this.productId = :productId")
                    ->innerJoin('this.units', 'units')
                    ->andWhere('units.productUnitId = :unitId')
                    ->setParameter('productId', $productId)
                    ->setParameter('unitId', $unitId)
                    ->getQB()
                    ->getQuery()
                    ->getSingleResult();
    }

    public function betweenPrice($priceFrom = '0', $priceTo = null)
    {
        $this->innerJoin('this.units', 'productUnit')
             ->andWhere('productUnit.discountedPrice >= :priceFrom')
             ->setParameter('priceFrom', $priceFrom);

        if($priceTo !== null){
            $this->andWhere('productUnit.discountedPrice <= :priceTo')
                 ->setParameter('priceTo', $priceTo);
        }

        return $this;
    }

    public function betweenDateLastModified($dateLastModifiedFrom = null, $dateLastModifiedTo = null)
    {
        $this->andWhere('this.dateLastModified >= :dateLastModifiedFrom')
             ->andWhere('this.dateLastModified <= :dateLastModifiedTo')
             ->setParameter('dateLastModifiedFrom', $dateLastModifiedFrom)
             ->setParameter('dateLastModifiedTo', $dateLastModifiedTo);

        return $this;
    }

    public function sortMethod($sortType, $sortDirection = self::DIRECTION_ASC)
    {
        if(in_array($sortDirection, array(self::DIRECTION_ASC, self::DIRECTION_DESC)) === false){
            $sortDirection = self::DIRECTION_ASC;
        }

        switch ($sortType) {
            case self::ALPHABETICAL:
                $this->orderBy('this.name', $sortDirection);
                break;
            case self::BYPRICE:
                $this->orderBy('productUnit.discountedPrice', $sortDirection);
                break;
            case self::BYDATE:
                $this->orderBy('this.dateLastModified', $sortDirection);
                break;
            case self::BYPOPULARITY:
                $this->orderBy('this.clickCount', $sortDirection);
                break;
            default:
                $this->orderBy('this.name', $sortDirection);
                break;
        }

        return $this;
    }

    /**
     * Retrieves the product list based on the given criteria
     *
     * @param mixed $params
     * @return mixed
     */
    public function getList($params)
    {
        extract($params);

        $this->qb();
        if (!is_null($categoryId)){

            $categoryIds = $categoryId;
            if(is_int($categoryId)){
                $categoryIds = array( $categoryId );
            }

            $this->andWhere("this.productCategory IN (:categoryIds)")
                 ->setParameter('categoryIds', $categoryIds);
        }
        if (!is_null($sellerId)){
            $this->andWhere('this.user = :user')
                 ->setParameter('user', $sellerId);
        }
        if (!is_null($sortType)){
            $this->sortMethod($sortType, $sortDirection);
        }
        if (!is_null($brandId)){
            $this->andWhere('this.brand = :brand')
                 ->setParameter('brand', $brandId);
        }
        if ($filters) {
            $this->innerJoin('this.attributes', 'attr')
                 ->innerJoin('attr.productAttributeValues', 'val');
            $filterWhere = '';
            foreach ($filters as $attrname => $attrval) {
                $filterWhere .= ($filterWhere ? ' OR ' : '')."attr.name = '$attrname' AND val.value = '$attrval'";
            }
            $this->andWhere($filterWhere)->having('count(attr) = '.count($filters))->distinct();
        }
        if(isset($queryString) && !is_null($queryString)){
            $this->andWhere('this.name LIKE :queryString')
                 ->setParameter('queryString', '%'.$queryString.'%');
        }

        if(isset($dateLastModifiedFrom, $dateLastModifiedTo) && !is_null($dateLastModifiedFrom) && !is_null($dateLastModifiedTo)){
            $this->betweenDateLastModified($dateLastModifiedFrom, $dateLastModifiedTo);
        }

        if(isset($status) && !is_null($status)){
            $this->andWhere('this.status = :status')
                 ->setParameter('status', $status);
        }

        if(isset($excludedStatus) && !is_null($excludedStatus)){
            $this->andWhere('NOT this.status = :excludedStatus')
                 ->setParameter('excludedStatus', $excludedStatus);
        }

        if(isset($perPage) && !is_null($perPage)){
            $this->setMaxResults($perPage);
        }

        $this->betweenPrice($priceFrom, $priceTo)
             ->groupBy('this.productId')
             ->page($page);

        $products = array();
        $productResults = $this->getQB()->getQuery()->getResult();

        foreach ($productResults as $product) {
            $image = $product->getPrimaryImages()->first();
            $imageUrl = $image ? $image->getImageLocation() : '';
            if (!$product) continue;

            if(isset($hydrateAsEntity) && $hydrateAsEntity === true){
                $products[] = $product;
            }
            else{
                $products[] = array(
                    'id'            => $product->getProductId(),
                    'productName'   => $product->getName(),
                    'originalPrice' => $product->getDefaultUnit()->getPrice(),
                    'newPrice'      => $product->getDefaultUnit()->getDiscountedPrice(),
                    'imageUrl'      => $imageUrl,
                    'discount'      => $product->getDefaultUnit()->getDiscount(),
                    'slug'          => $product->getSlug(),
                );
            }
        }

        $this->setFirstResult(null)
             ->setMaxResults(null)
             ->select('count(this)');

        $totalResultCount = $this->resetDQLPart('groupBy')
                                 ->getQB()
                                 ->getQuery()
                                 ->getSingleScalarResult();

        return compact('totalResultCount', 'products');
    }

    public function searchProductsBy($params = array())
    {
        extract($params);

        $this->qb();
        if (isset($categoryId) && !is_null($categoryId)){

            $categoryIds = $categoryId;
            if(is_int($categoryId)){
                $categoryIds = array( $categoryId );
            }

            $this->andWhere("this.productCategory IN (:categoryIds)")
                 ->setParameter('categoryIds', $categoryIds);
        }
        if (isset($sellerId) && !is_null($sellerId)){
            $this->andWhere('this.user = :user')
                 ->setParameter('user', $sellerId);
        }

        if (isset($keyword) && !is_null($keyword)){
            $this->andWhere('this.name LIKE :name')
                 ->setParameter('name', "%".$keyword."%");
        }

        if (isset($sortType) && !is_null($sortType)){
            $this->sortMethod($sortType, $sortDirection);
        }

        if(isset($dateLastModifiedFrom, $dateLastModifiedTo) && !is_null($dateLastModifiedFrom) && !is_null($dateLastModifiedTo)){
            $this->betweenDateLastModified($dateLastModifiedFrom, $dateLastModifiedTo);
        }

        if (isset($countryId) && $countryId) {
            $this->filterByProductCountry($countryId);
        }

        if (isset($status) && !is_null($status)) {
            $this->getProductFilterByStatus($status);
        }

        if(isset($excludedStatus) && !is_null($excludedStatus)){
            $this->getProductFilterByStatus($excludedStatus, true);
        }

        if(isset($perPage) && !is_null($perPage)){
            $this->setMaxResults($perPage);
        }

        $this->betweenPrice($priceFrom, $priceTo)
             ->distinct()
             ->page($page)
        ;

        $products = $this->getQB()->getQuery()->getResult();

        $totalResultCount = $this->getCount();
        $totalPages = ceil($totalResultCount/$perPage);

        return compact('totalResultCount', 'totalPages', 'products');
    }

    public function leftJoinProductCountry($alias = 'productCountry')
    {
        $this->leftJoin('this.productCountries', $alias);

        return $this;
    }

    public function filterByProductCountry($countries)
    {
        $this
            ->innerJoinWOCollision('this.productCountries', 'productCountries')
            ->andWhere('productCountries.country IN (:countries)')
            ->setParameter('countries', $countries)
        ;

        return $this;
    }

    public function getProductFilterByStatus($statuses, $exclude = false)
    {
        $this->innerJoinWOCollision('this.productCountries', 'productCountries');
        if ($exclude) {
            $paramName = 'excludeStatuses';
            $comparison = 'NOT IN';
        }
        else {
            $paramName = 'statuses';
            $comparison = 'IN';
        }
        $this
            ->andWhere("productCountries.status $comparison (:$paramName)")
            ->setParameter($paramName, $statuses)
        ;

        return $this;
    }

    public function getBySlug($slug)
    {
        $this
            ->andWhere('this.slug = :slug')
            ->setParameter('slug', $slug);
        ;

        return $this;
    }

    public function excludeOldManufacturerProducts(){
        $this
            ->leftJoin('this.manufacturerProductMap', 'manufacturerProductMap')
            ->andWhere('manufacturerProductMap IS NULL')
        ;

        return $this;
    }

    public function getByAffiliateSlug($slug, $activeSeller = true, $filterStatus = array(Product::ACTIVE))
    {
        if ($slug) {
            $this
                ->innerJoin('this.inhouseProductUsers', 'inhouseProductUsers')
                ->innerJoin('inhouseProductUsers.user', 'user')
                ->innerJoin('user.store', 'store')
                ->andWhere('store.storeSlug = :storeSlug')
                ->setParameter('storeSlug', $slug)
                ->andWhere('inhouseProductUsers.status IN (:filterStatus)')
                ->setParameter('filterStatus', $filterStatus)
            ;

            if ($activeSeller) {
                $this
                    ->andWhere('user.isActive = 1')
                ;
            }
        }

        return $this;
    }

    public function fromCart(Cart $cart, $forApi = false)
    {
        $products = array();
        $ids = array();
        foreach ($cart->getCartItems() as $item) {
            if (!$item->getProduct()) continue;
            if (in_array($item->getId(), $ids)) continue;

            $ids[] = $item->getId();
            $productId = $item->getProduct()->getProductId();
            $unitId = $item->getProductUnit() ? $item->getProductUnit()->getProductUnitId() : null;
            $this->qb()->andWhere("this.productId = ".$productId);

            if ($unitId) {
                $this->innerJoin('this.units', 'units')
                     ->andWhere('units.productUnitId = :unitId')
                     ->setParameter('unitId', $unitId);
            }

            $product = $this->getQB()->getQuery()->getSingleResult();
            // if (!$product->getCountry()) {
            //     $this->_em->detach($product);
            //     $product = $this->find($product->getProductId());
            // }

            if (!$product){
                continue;
            }
            if ($forApi) {
                $product = $product->getDetails();
                $product['unitId'] = $unitId;
                $product['itemId'] = $item->getId();
                $product['sellerId'] = $item->getSeller()->getUserId();
                $product['quantity'] = $item->getQuantity();
                $product['shippingCost'] = $product['quantity'] * $product['shippingCost'];
            }

            $products[] = $product;
        }

        return $products;
    }

    public function ofCategoryIds($categoryIds)
    {
        if (is_array($categoryIds)) {
            $this->andWhere('this.productCategory IN (:productCategory)');
        }
        else {
            $this->andWhere('this.productCategory = :productCategory');
        }

        $this->setParameter('productCategory', $categoryIds);

        return $this;
    }

    public function getCategorySiblingRelated($product, $limit = 5)
    {
        if (!($product instanceof Product)) {
            $product = $this->getEntityManager()->getReference('YilinkerCoreBundle:Product', $product);
        }

        try {
            $category = $product->getProductCategory();
            if (!$category) {
                return array();
            }

            $productCategoryRepo = $this->getEntityManager()->getRepository('YilinkerCoreBundle:ProductCategory');
            $relatedCategories = $productCategoryRepo->getRelated($category, 2);
            $categoryIds = array_map(function($relatedCategory) {
                return $relatedCategory['productCategoryId'];
            }, $relatedCategories);

            $products = $this->qb()
                          ->activeProduct()
                          ->andWhere('this.productId <> :productId')
                          ->ofCategoryIds($categoryIds)
                          ->setMaxResults($limit)
                          ->setParameter('productId', $product->getProductId())
                          ->getResult();

            return $products;
        } catch (Exception $e) {
            return array();
        }
    }

    public function activeProduct($countryCode = 'ph')
    {
        $this
            ->innerJoin('this.productCountries', 'productCountries')
            ->innerJoin('productCountries.country', 'country')
            ->andWhere('productCountries.status = :activeProduct')
            ->andWhere('country.code = :countryCode')
            ->setParameter('activeProduct', Product::ACTIVE)
            ->setParameter('countryCode', $countryCode)
        ;

        return $this;
    }

    public function getCategoryRelated($product, $limit = 5)
    {
        if (!($product instanceof Product)) {
            $product = $this->getEntityManager()->getReference('YilinkerCoreBundle:Product', $product);
        }

        try {
            $category = $product->getProductCategory();
            if (!$category) {
                return array();
            }

            $products = $this->qb()
                             ->activeProduct()
                             ->andWhere('this.productId <> :productId')
                             ->ofCategoryIds($category->getProductCategoryId())
                             ->setMaxResults($limit)
                             ->setParameter('productId', $product->getProductId())
                             ->getResult();

            return $products;
        }
        catch (\Exception $e) {
            return array();
        }
    }

    /**
     * gets related product based on category and sibling category
     */
    public function getRelated($product, $limit = 5)
    {
        $products = $this->getCategoryRelated($product, $limit);
        $remaining = $limit - count($products);
        if ($remaining > 0) {
            $siblingRelatedProducts = $this->getCategorySiblingRelated($product, $remaining);
            $products = array_merge($products, $siblingRelatedProducts);
        }

        return $products;
    }

    /**
     * gets related product based on category and seller
     */
    public function getSellerRelated($product, $limit = 5, $countryCode = 'ph')
    {
        if (!($product instanceof Product)) {
            $product = $this->getEntityManager()->getReference('YilinkerCoreBundle:Product', $product);
        }
        try {
            $seller = $product->getUser();
            if (!$seller) {
                return array();
            }

            $this->qb()
                 ->activeProduct($countryCode)
                 ->andWhere('this.productId <> :productId')
                 ->andWhere('this.user = :seller')
                 ->setMaxResults($limit)
                 ->setParameter('productId', $product->getProductId())
                 ->setParameter('seller', $seller->getId())
            ;

            // save query with no category;
            $query = $this->getQB()->getQuery();
            // get products with the same category
            $category = $product->getProductCategory();
            if ($category) {
                $this->ofCategoryIds($category->getProductCategoryId());
            }
            $products = $this->getResult();
            // if there are no products with the same category
            if (!count($products)) {
                $products = $query->getResult();
            }

            return $products;
        } catch (\Exception $e) {
            return array();
        }
    }

    /**
     * Retrieve multiple products by product slug and category slug
     *
     * @param string[] $productSlugs
     * @param string $categorySlug
     * @return Product[]
     */
    public function getProductsBySlug($productSlugs = array(), $categorySlug = null, $indexBySlug = false)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('product, field(product.slug, :productSlugs) as hidden field');

        if($indexBySlug){
            $qb->from('Yilinker\Bundle\CoreBundle\Entity\Product', 'product', 'product.slug');
        }
        else{
            $qb->from('Yilinker\Bundle\CoreBundle\Entity\Product', 'product');
        }

        $qb->where('product.slug in (:productSlugs)');

        if($categorySlug !== null){
            $qb->innerJoin(
                'Yilinker\Bundle\CoreBundle\Entity\ProductCategory',
                'category',
                'WITH',
                'category.slug = :categorySlug AND product.productCategory = category.productCategoryId'
            )->setParameter('categorySlug', $categorySlug);
        }

        $query = $qb->andWhere('product.status = :status')
                    ->orderBy('field')
                    ->setParameter('productSlugs', $productSlugs)
                    ->setParameter('status', Product::ACTIVE)
                    ->getQuery();
        return $qb->getQuery()
                  ->useResultCache(true, 86400)
	              ->getResult();
    }

    public function getBoughtWith($productId = null, $limit = 5)
    {
        $em = $this->getEntityManager();
        $tbOrderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct');
        $qb = $tbOrderProduct->createQueryBuilder('orderProductSub')
                             ->select('IDENTITY(orderProductSub.order)')
                             ->andWhere('orderProductSub.product = :productId')
                             ->setParameter('productId', $productId);
        $subQuery = $qb->getDQL();

        $boughtWithProducts =
            $this->qb()
                 ->distinct()
                 ->activeProduct()
                 ->setMaxResults($limit)
                 ->innerJoin('this.orderProducts', 'orderProducts', Join::INNER_JOIN)
                 ->andWhere('orderProducts.order IN ('.$subQuery.')')
                 ->andWhere('this.productId <> :productId')
                 ->setParameter('productId', $productId)
                 ->getResult()
            ;

        return $boughtWithProducts;
    }

    /**
     * Returns the number of active product
     *
     * @param int $status
     * @return int
     */
    public function getNumberOfProducts($status = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("count(p)")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId");

        if($status !== null){
            if(is_array($status)){
                $queryBuilder->where("p.status IN (:status)")
                             ->setParameter(":status", $status);
            }
            else{
                $queryBuilder->where("p.status = :status")
                             ->setParameter(":status", $status);
            }
        }

        $numberOfProducts = $queryBuilder->getQuery()->getSingleScalarResult();

        return (int) $numberOfProducts;
    }

    /**
     * retrieve product by status
     *
     * @param int[] $status
     * @param int $productId
     * @return Yilinker\Bundle\CoreBundle\Entity\Product
     */
    public function getProductsByStatus($status, $productId = null)
    {
        if(is_array($status) === false){
            $status = array($status);
        }

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Product", "p", "p.productId")
                     ->where('p.status IN (:statuses)')
                     ->setParameter('statuses', $status);

        if($productId !== null){
            $queryBuilder->andWhere("p.productId = :productId")
                         ->setParameter(":productId", $productId);
        }

        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }

    /**
     * Get Product Unit SKU by User
     *
     * @param int $userId
     * @param $sku
     * @param $excludeProductUnitId
     * @param $product
     * @return Product
     */
    public function getProductUnitSkuByUser ($userId, $sku, $excludeProductUnitId = null, Product $product = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("Product")
                     ->from("YilinkerCoreBundle:Product", "Product")
                     ->leftJoin('YilinkerCoreBundle:User', 'User', 'WITH', 'User.userId = Product.user')
                     ->leftJoin('YilinkerCoreBundle:ProductUnit', 'ProductUnit', 'WITH', 'Product.productId = ProductUnit.product')
                     ->where('User.userId = :userId')
                     ->andWhere('ProductUnit.sku = :sku')
                     ->andWhere('Product.status <> :status')
                     ->setParameter('userId', $userId)
                     ->setParameter('sku', $sku)
                     ->setParameter('status', Product::FULL_DELETE)
                     ->groupBy('Product.productId');

        if ($excludeProductUnitId !== null) {
            $queryBuilder->andWhere('ProductUnit.productUnitId != :productUnitId')
                         ->setParameter('productUnitId', $excludeProductUnitId);
        }

        if (!is_null($product)) {
            $queryBuilder->andWhere('Product.productId != :productId')
                         ->setParameter('productId', $product->getProductId());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retrieve all active product count
     *
     * @return int
     */
    public function getActiveProductCount()
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("count(p)")
                     ->from("YilinkerCoreBundle:Product", "p")
                     ->andWhere('p.status = :status')
                     ->setParameter('status', Product::ACTIVE);

        $result = $queryBuilder->getQuery()
                               ->useResultCache(true, 3600)
                               ->getSingleResult();

        return (int) reset($result);
    }

    /**
     * get products that have been out of stock for one week
     */
    public function meantForInactive()
    {
        $lastweek = Carbon::now()->subWeek();
        $this
            ->qb()
            ->leftJoin('this.manufacturerProductMap', 'manufacturerProductMap')
            ->leftJoin('this.units', 'units', 'WITH', 'units.quantity > 0')
            ->andWhere('this.status = :productStatus')
            ->andWhere('this.dateLastEmptied <= :dateLastEmptied')
            ->andWhere('units IS NULL')
            ->andWhere('manufacturerProductMap IS NULL')
            ->setParameter('productStatus', Product::ACTIVE)
            ->setParameter('dateLastEmptied', $lastweek)
        ;

        return $this->getResult();
    }

    /**
     * Get Commission by ProductUnit
     *
     * @param ProductUnit $productUnit
     * @return int
     */
    public function getCommissionByProductUnit (ProductUnit $productUnit)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("ManufacturerProductUnit.commission")
                     ->from("YilinkerCoreBundle:ManufacturerProductUnitMap", "ManufacturerProductUnitMap")
                     ->innerJoin('YilinkerCoreBundle:ManufacturerProductUnit', "ManufacturerProductUnit", "WITH", "ManufacturerProductUnitMap.manufacturerProductUnit = ManufacturerProductUnit.manufacturerProductUnitId")
                     ->where('ManufacturerProductUnitMap.productUnit = :productUnitId')
                     ->setParameter('productUnitId', $productUnit->getProductUnitId());

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getLanguages($product, $object = false)
    {
        $transRepository = $this->_em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $transRepository->findTranslations($product);
        $languages = array_keys($translations);
        $languages[] = $product->getDefaultLocale();

        if ($object) {
            $tbLanguage = $this->_em->getRepository('YilinkerCoreBundle:Language');

            return $tbLanguage->getByCodes($languages);
        }

        return $languages;
    }

    public function getCountriesByStatus($product, $status)
    {
        $countries = $this->getCountries($product, true);

        $productCountry = $this->_em->getRepository('YilinkerCoreBundle:ProductCountry')
                                    ->findBy(array(
                                        'product' => $product,
                                        'country' => $countries,
                                        'status' => $status,
                                    ));

        $data = array();
        foreach ($productCountry as $key => $value) {
            $data[] = $value->getCountry();
        }

        return $data;
    }

    public function getCountries($product, $object = false)
    {
        $transRepository = $this->_em->getRepository('Gedmo\Translatable\Entity\Translation');
        $countries = array();
        foreach ($product->getUnits() as $unit) {
            $translations = $transRepository->findTranslations($unit);
            $countries = array_merge($countries, array_keys($translations));
        }
        if ($object) {
            $tbCountry = $this->_em->getRepository('YilinkerCoreBundle:Country');

            return $tbCountry->getByCodes($countries);
        }

        return $countries;
    }

    public function hasValidUnitPrice($product)
    {
        $units = $product->getUnits();
        foreach ($units as $unit) {
            if(
                floatval($unit->getPrice()) > 0 &&
                floatval($unit->getDiscountedPrice()) > 0
            ){
                return true;
            }
        }

        return false;
    }

    public function allUnitsHavePrice($product)
    {
        $units = $product->getUnits();
        foreach ($units as $unit) {
            if(
                floatval($unit->getPrice()) <= 0 &&
                floatval($unit->getDiscountedPrice()) <= 0
            ){
                return false;
            }
        }

        return true;
    }

    /**
     * get Product either by Id or Slug
     */
    public function getOnebyIdOrSlug($id, $status=Product::ACTIVE, $user = null)
    {
        $this->qb();

        if(!is_null($status)){
            $orX = $this->getQB()->expr()->orX();
            $orX->add($this->getQB()->expr()->eq("this.productId", ':id' ));
            $orX->add($this->getQB()->expr()->eq("this.slug", ':id' ));
            $this->where($orX)->setParameter('id',$id);
        }
        else{
            $this->where('this.productId = :id')->setParameter('id', $id);
        }


        if(!is_null($status)){
            $this->getProductFilterByStatus($status);
        }

        if(!is_null($user)){
            $this->andWhere("this.user = :user")->setParameter(':user', $user);
        }

        return $this;
    }

    /**
     * @param $product array   taken from cart
     */
    public function getActualSeller($product)
    {
        $seller = null;
        if (isset($product['sellerId']) && $product['sellerId']) {
            $tbUser = $this->_em->getRepository('YilinkerCoreBundle:User');
            $seller = $tbUser->find($product['sellerId']);
        }

        if (!$seller) {
            $product = $this->find($product['id']);
            $seller = $product->getUser();
        }

        return $seller;
    }
}
