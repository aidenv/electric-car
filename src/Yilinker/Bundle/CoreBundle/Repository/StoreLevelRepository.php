<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class StoreLevelRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class StoreLevelRepository extends EntityRepository
{

    /**
     * Get Store level with order by
     *
     * @param string $orderBy
     * @return array
     */
    public function getStoreLevelOrderBy ($orderBy = 'asc')
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("StoreLevel")
                     ->from("YilinkerCoreBundle:StoreLevel", "StoreLevel");

        return $queryBuilder->orderBy('StoreLevel.storeSpace', $orderBy)->getQuery()->getResult();
    }

}
