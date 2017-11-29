<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserIdentificationCardRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserIdentificationCardRepository extends EntityRepository
{
    /**
     * Retrieve the most recent user ID
     *
     * @param int $userId
     * @return Yilinker\CoreBundle\Entity\UserIdentificationCard
     */
    public function getMostRecentId($userId)
    {
        $queryBuilder = $this->_em->createQueryBuilder()
                             ->select("id")
                             ->from("YilinkerCoreBundle:UserIdentificationCard", "id")
                             ->andWhere("id.user = :user")
                             ->orderBy("id.dateAdded", "DESC")
                             ->setMaxResults(1)
                             ->setParameter('user', $userId);
        
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

}

