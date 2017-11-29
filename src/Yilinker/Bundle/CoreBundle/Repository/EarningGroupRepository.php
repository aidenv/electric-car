<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query;
use Yilinker\Bundle\CoreBundle\Entity\Earning;

class EarningGroupRepository extends EntityRepository
{
    public function getEarningTypesByPrivilegeLevel(
        $earningPrivilege = array()
    ){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("et")
                     ->from("YilinkerCoreBundle:EarningType", "et")
                     ->innerJoin("YilinkerCoreBundle:EarningGroupMap", "egm", Join::WITH, "egm.earningType = et")
                     ->innerJoin("YilinkerCoreBundle:EarningGroup", "eg", Join::WITH, "egm.earningGroup = eg")
                     ->where("et.privilegeLevel IN (:earningPrivilege)")
                     ->setParameter(":earningPrivilege", $earningPrivilege);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getUserEarningGroupsDetails(
        $user,
        $earningPrivilege = array(),
        $excludedStatus = Earning::INVALID
    ){
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult("name", "name");
        $rsm->addScalarResult("earning_group_id", "earningGroupId");
        $rsm->addScalarResult("image_location", "imageLocation");
        $rsm->addScalarResult("totalAmount", "totalAmount");

        $sql = "
            SELECT
                eg.earning_group_id,
                eg.name,
                eg.image_location,
                SUM(e.amount) as totalAmount
            FROM
                EarningType et
            INNER JOIN
                EarningGroupMap egm
            ON
                egm.earning_type_id = et.earning_type_id
            INNER JOIN
                EarningGroup eg
            ON
                egm.earning_group_id = eg.earning_group_id
            LEFT JOIN
                Earning e
            ON
                e.earning_type_id = et.earning_type_id 
            AND 
                e.user_id = :userId
            AND
                e.status != :excludedStatus
            WHERE
                et.privilege_level IN (:earningPrivilege)
            GROUP BY
                eg.earning_group_id
        ";

        $query = $this->_em->createNativeQuery($sql, $rsm);

        $query->setParameter(":excludedStatus", $excludedStatus);
        $query->setParameter(":earningPrivilege", $earningPrivilege);
        $query->setParameter(":userId", $user? $user->getUserId() : null);

        return $query->execute();
    }
}
