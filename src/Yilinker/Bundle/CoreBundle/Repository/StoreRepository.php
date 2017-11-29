<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Doctrine\ORM\Query\ResultSetMapping;

class StoreRepository extends EntityRepository
{
    /**
     * Order alphabetically
     */
    const ALPHABETICAL = 'ALPHABETICAL';

    /**
     * Order by date modified
     */
    const BYDATE = 'BYDATE'; 

    /**
     * Sort direction: descending
     */
    const DIRECTION_DESC = 'DESC';

    /**
     * Sort direction: ascending
     */
    const DIRECTION_ASC = 'ASC';
    
    public function getStoreByStoreName($storeName, User $excludedUser = null, $isSearch = false, $limit = 10)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("s")
                     ->from("YilinkerCoreBundle:Store", "s");

        if ($isSearch == false) {
            $queryBuilder->where("s.storeName = :storeName");
        }
        else {
            $storeName = '%' . $storeName . '%';
            $queryBuilder->where("s.storeName = :storeName");
        }

        if (!is_null($excludedUser)) {
            $queryBuilder->andWhere("NOT s.user = :user")
                         ->setParameter(":user", $excludedUser);
        }

        return $queryBuilder->setParameter(":storeName", $storeName)->setMaxResults($limit)->getQuery()->getResult();
    }

    /**
     * Get store by store name
     *
     * @param $storeName
     * @param array $excludedStore
     * @param int $limit
     * @return array
     */
    public function searchStoreByStoreName($storeName, array $excludedStore = array(), $limit = 10)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("s")
            ->from("YilinkerCoreBundle:Store", "s")
            ->where("s.storeName LIKE :storeName");

        if (!is_null($excludedStore) && count($excludedStore) > 0) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('s.storeId', implode(',', $excludedStore)));
        }

        return $queryBuilder->setParameter(":storeName", '%' . $storeName . '%')
                            ->setMaxResults($limit)
                            ->getQuery()
                            ->getResult();
    }
    
    public function getStoreByStoreSlug($storeSlug, User $excludedUser = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("s")
                     ->from("YilinkerCoreBundle:Store", "s")
                     ->where("s.storeSlug = :storeSlug");

        if(!is_null($excludedUser)){
            $queryBuilder->andWhere("NOT s.user = :user")
                         ->setParameter(":user", $excludedUser);
        }

        return $queryBuilder->setParameter(":storeSlug", $storeSlug)->getQuery()->getResult();
    }
    
    public function getStoreByStoreSlugIn($storeSlugs, User $excludedUser = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("s")
                     ->from("YilinkerCoreBundle:Store", "s")
                     ->where("s.storeSlug IN (:storeSlugs)");

        if(!is_null($excludedUser)){
            $queryBuilder->andWhere("NOT s.user = :user")
                         ->setParameter(":user", $excludedUser);
        }

        return $queryBuilder->setParameter(":storeSlugs", $storeSlugs)->getQuery()->getResult();
    }

    public function getOneStoreByStoreId($storeId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('store')
           ->from('YilinkerCoreBundle:Store', 'store')
           ->where('store.storeId = :storeId')
           ->setParameter('storeId', $storeId);

        return $qb->getQuery()
                  ->useResultCache(true, 86400)
                  ->getOneOrNullResult();
    }

    /**
     * Retrieve the number of accredited stores
     *
     * @return int
     */
    public function getNumberOfAccreditedStores()
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("count(s)")
                     ->from("YilinkerCoreBundle:Store", "s")
                     ->where("s.accreditationLevel IS NOT NULL");

        return (int) $queryBuilder->getQuery()
                                  ->useResultCache(true, 3600)
                                  ->getSingleScalarResult();
    }

    /**
     * Retrieve active store
     *
     * @param string $slug
     * @return Yilinker\Bundle\CoreBundle\Entity\Store
     */
    public function getActiveStore($slug)
    {
        $affiliate = Store::STORE_TYPE_RESELLER;
        $seller = Store::STORE_TYPE_MERCHANT;

        $queryBuilder = $this->_em->createQueryBuilder();


        $queryBuilder->select("s")
                     ->from("YilinkerCoreBundle:Store", "s")
                     ->innerJoin("s.user", "user")
                     ->where("s.storeSlug = :slug")
                     ->andWhere("s.storeName IS NOT NULL")
                     ->andWhere("s.storeName <> ''")
                     ->andWhere("user.isActive = :activeUser")
                     ->andWhere("user.isActive = :activeUser")
                     ->setParameter("activeUser", true)
                     ->setParameter("slug", $slug);

        $store = $queryBuilder->getQuery()->getOneOrNullResult();

        if($store && $store->getStoreType() == Store::STORE_TYPE_MERCHANT){
            $accreditationApplication = $store->getUser()->getAccreditationApplication();
            return ($accreditationApplication && $accreditationApplication->getAccreditationLevel())? $store : null;
        }
        else{
            return $store;
        }
    }

    /**
     * Retrieve product count of a store
     * 
     * @param int $storeId
     * @param int[] $statuses
     * @return int
     */
    public function getStoreProductCount($storeId, $statuses = null)
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('count', 'count');

        $sql = "SELECT
                count(p.product_id) as `count`
            FROM Store s
            INNER JOIN
                 Product p ON p.user_id = s.user_id
            WHERE
                s.store_id = :storeId            
        ";
        
        if($statuses !== null){
            $sql .= " AND p.status IN (:statuses)";
        }

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter('storeId', $storeId);

        if($statuses !== null){
            if(is_array($statuses) === false){
                $statuses = array($statuses);
            }
            $query->setParameter('statuses', $statuses);
        }

        $results = $query->getSingleScalarResult();

        return (int) $results;        
    }

    /**
     * Get store by store id
     *
     * @param int $storeId
     * @return Yilinker\Bundle\CoreBundle\Entity\Store
     */
    public function getStoreByStoreId($storeId)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("s")
                     ->from("YilinkerCoreBundle:Store", "s")
                     ->where('s.storeId = :storeId')
                     ->setParameter('storeId', $storeId);

        return $queryBuilder->getQuery()
                            ->useResultCache(true, 3600)
                            ->getOneOrNullResult();
    }


    public function criteriaForActiveStoreList($params=array())
    {
        $queryBuilder = $this->_em->createQueryBuilder();
           
        $queryBuilder->select("s")
             ->from("YilinkerCoreBundle:Store", "s")
             ->innerJoin("s.user", "user")
             ->where("s.storeName IS NOT NULL")
             ->andWhere("s.storeName <> ''")
             ->andWhere("user.isActive = :activeUser")
             ->setParameter("activeUser", true);
        
        return $queryBuilder;
    }


    public function getActiveStoreList()
    {
        $qb = $this->criteriaForActiveStoreList();
        
        return $qb->getQuery()->getResult();
    }

}
