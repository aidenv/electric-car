<?php
namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Doctrine\ORM\Query;
use Carbon\Carbon;

class PayoutRepository extends EntityRepository
{
    const PAYOUT_LIMIT = 30;

    const PAYOUT_OFFSET = 0;

    public function getPayouts($keyword = null, $dateFrom = null, $dateTo = null, $storeType = null, $limit = self::PAYOUT_LIMIT, $offset = self::PAYOUT_OFFSET)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("p")
            ->from("YilinkerCoreBundle:Payout", "p")
            ->innerJoin("YilinkerCoreBundle:User", "u", Join::WITH, "p.user = u")
            ->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u.userId");

        if(!is_null($keyword)){
            $like = $queryBuilder->expr()->like("s.storeName", ":storeName");
            $queryBuilder->andWhere($like)->setParameter(":storeName", "%".$keyword."%");
        }

        if(!is_null($dateFrom)){
            $gte = $queryBuilder->expr()->gte("p.dateCreated", ":dateFrom");
            $queryBuilder->andWhere($gte)->setParameter(":dateFrom", $dateFrom);
        }


        if(!is_null($dateTo)){
            $lte = $queryBuilder->expr()->lte("p.dateCreated", ":dateTo");
            $queryBuilder->andWhere($lte)->setParameter(":dateTo", $dateTo);
        }

        if (!is_null($storeType)) {
            $storeTypeCond = $queryBuilder->expr()->eq("s.storeType", ":storeType");
            $queryBuilder->andWhere($storeTypeCond)->setParameter(":storeType", $storeType);
        }

        $payoutCount = count($queryBuilder->getQuery()->getResult());
        $payouts = $queryBuilder->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();

        return compact("payouts", "payoutCount");
    }

    /**
     * Get payout group by seller
     *
     * @param null $storeType
     * @param int $limit
     * @param int $offset
     * @return array [Total Payout Rows, Payouts]
     */
    public function getPayoutBySeller ($storeType = null, $limit = self::PAYOUT_LIMIT, $offset = self::PAYOUT_OFFSET)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array(
                            'User.userId as userId',
                            'SUM(Payout.amount) as totalAmount'
                        ))
                     ->from('YilinkerCoreBundle:Payout', 'Payout')
                     ->leftJoin('YilinkerCoreBundle:User', 'User', 'WITH', 'User.userId = Payout.user')
                     ->leftJoin('YilinkerCoreBundle:Store', 'Store', 'WITH', 'Store.user = User.userId');

        if (!is_null($storeType) && ($storeType !== Store::STORE_TYPE_MERCHANT || $storeType !== Store::STORE_TYPE_RESELLER)) {
            $queryBuilder->andWhere('Store.storeType = :storeType')
                         ->setParameter('storeType', $storeType);
        }

        $queryBuilder->groupBy('User.userId');

        $payoutCount = count($queryBuilder->getQuery()->getResult());
        $payouts = $queryBuilder->setMaxResults($limit)
                                ->setFirstResult($offset)
                                ->getQuery()
                                ->getResult();

        return compact('payoutCount', 'payouts');
    }

    public function filterQB($filter, $page = 1, $perPage = 10)
    {
        $this
            ->qb()
            ->setMaxResults($perPage)
            ->page($page)
        ;
        if (array_key_exists('dateFrom', $filter) && $filter['dateFrom']) {
            $dateFrom = Carbon::createFromFormat('m/d/Y', $filter['dateFrom']);
            $this
                ->andWhere('this.dateCreated >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom)
            ;
        }
        if (array_key_exists('dateTo', $filter) && $filter['dateTo']) {
            $dateTo = Carbon::createFromFormat('m/d/Y', $filter['dateTo']);
            $this
                ->andWhere('this.dateCreated <= :dateTo')
                ->setParameter('dateTo', $dateTo)
            ;
        }
        if (array_key_exists('q', $filter) && $filter['q']) {
            $this
                ->innerJoin('this.user', 'user')
                ->andWhere('user.email LIKE :q OR user.firstName LIKE :q OR user.lastName LIKE :q')
                ->setParameter('q', $filter['q'])
            ;
        }

        return $this;
    }
}
