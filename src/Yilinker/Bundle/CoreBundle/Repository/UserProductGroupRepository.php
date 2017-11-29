<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Brand;

/**
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserProductGroupRepository extends EntityRepository
{
    public function getFindByName($keyword = null, $user)
    {
        $queryBuilder = $this->createQueryBuilder("upg")
                             ->where("upg.user = :user")
                             ->setParameter(":user", $user);

        if(!is_null($keyword)){
            $queryBuilder->andWhere("upg.name LIKE :keyword")
                         ->setParameter(":keyword", "%".$keyword."%");
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByNamesIn(array $userGroups, $user)
    {
        return $this->createQueryBuilder("upg")
                    ->where("upg.user = :user")
                    ->andWhere("upg.name IN (:userGroups)")
                    ->setParameter(":userGroups", $userGroups)
                    ->setParameter(":user", $user)
                    ->getQuery()
                    ->getResult();
    }
}
