<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\PackageStatus;

/**
 * Class PackageRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class PackageRepository extends EntityRepository
{
    /**
     * Get packages by order product ids
     *
     * @param int[] $orderProductIds
     * @return Yilinker\Bundle\CoreBundle\Entity\Package[]
     */
    public function getPackagesByOrderProducts($orderProductIds, $hasBeenReceived = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select("p")
                     ->from("YilinkerCoreBundle:Package", "p")
                     ->innerJoin("YilinkerCoreBundle:PackageDetail","pd","WITH","p.packageId = pd.package")
                     ->where("pd.orderProduct IN (:orderProductIds)")
                     ->setParameter("orderProductIds", $orderProductIds)
                     ->groupBy("p.packageId");

        if($hasBeenReceived !== null){
            $queryBuilder->leftJoin('YilinkerCoreBundle:PackageHistory', 'ph', 'WITH', 'ph.package = p AND ph.packageStatus = :receivedStatus')
                         ->setParameter('receivedStatus', PackageStatus::STATUS_RECEIVED_BY_RECIPIENT)                
                         ->addSelect('count(ph.packageHistoryId) as HIDDEN receivedPackageCount')
                         ->groupBy("p.packageId");
            if($hasBeenReceived){
                $queryBuilder->having('receivedPackageCount > :receivedCount');
            }
            else{
                $queryBuilder->having('receivedPackageCount = :receivedCount');
            }
            $queryBuilder->setParameter('receivedCount', 0);
        }

        return $queryBuilder->getQuery()->getResult();
    }

}
