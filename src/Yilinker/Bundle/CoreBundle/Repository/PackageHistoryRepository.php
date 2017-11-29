<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * PackageHistoryRepository
 */
class PackageHistoryRepository extends EntityRepository
{
    /**
     * Find Package person in charge
     * 
     * @param string $queryString
     * @param int $limit
     * @param int $offset
     * @param int $seller
     * @param int $buyer
     * @return Yilinker\Bundle\CoreBundle\Entity\PackageHistory[]
     */
    public function findPackagePersonInCharge($queryString, $limit = null, $offset = null, $seller = null, $buyer = null)
    {
        $queryBuilder = $this->createQueryBuilder("ph")
                             ->select("DISTINCT ph.personInCharge")
                             ->andWhere("ph.personInCharge <> ''");

        if(strlen($queryString) > 0){
            $queryBuilder->andWhere("match_against (ph.personInCharge) against (:personInCharge BOOLEAN) > 0")
                         ->setParameter('personInCharge', $queryString.'*');
        }

        if($limit !== null){
            $queryBuilder->setMaxResults($limit);
        }
        
        if($offset !== null){
            $queryBuilder->setFirstResult($offset);
        }

        if($seller || $buyer){
            $queryBuilder->leftJoin("YilinkerCoreBundle:Package", "p", "WITH", "ph.package = p.packageId")
                         ->leftJoin("YilinkerCoreBundle:UserOrder", "o", "WITH", "p.userOrder = o.orderId");
            if($seller){
                $queryBuilder->innerJoin("YilinkerCoreBundle:OrderProduct", "op", "WITH", "op.order = o.orderId AND op.seller = :seller")
                             ->setParameter("seller", $seller);
            }
            if($buyer){
                $queryBuilder->andWhere("o.buyer = :buyer")
                             ->setParameter("buyer", $buyer);
            }
        }

        return $queryBuilder->getQuery()
                            ->getResult();
    }

    /**
     * Get Package history by status
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\Package $package
     * @param int $status
     * @return Yilinker\Bundle\CoreBundle\Entity\PackageHistory
     */
    public function getPackageHistoryByStatus($package, $status)
    {
        $packageStatus = $this->_em->getReference('YilinkerCoreBundle:PackageStatus', $status);

        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("packageStatus", $packageStatus));
        $packageHistory = $package->getPackageHistory()->matching($criteria)->first();

        return $packageHistory ? $packageHistory : null;
    }

}
