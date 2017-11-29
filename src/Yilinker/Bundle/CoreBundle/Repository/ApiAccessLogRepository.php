<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ApiAccessLogRepository
 */
class ApiAccessLogRepository extends EntityRepository
{
    /**
     * Retrieve the latest access log by type
     *
     * @param string $accessType
     */
    public function getLastAccessLogByType($accessType)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("a")
                     ->from("YilinkerCoreBundle:ApiAccessLog", "a", "a.apiAccessLogId")
                     ->where("a.apiType = :apiType")
                     ->orderBy("a.dateAdded", "DESC")
                     ->setMaxResults(1)
                     ->setParameter("apiType", $accessType);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
