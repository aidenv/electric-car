<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class UserReferralRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserReferralRepository extends EntityRepository
{
    public function filterByQuery(array $query)
    {
        if (isset($query['referrer']) && $query['referrer']) {
            $this->getQB()
                 ->andWhere('this.referrer = :referrer')
                 ->setParameter('referrer', $query['referrer']);
        }

        return $this;
    }

    public function joinUser()
    {
        if (!$this->aliasExist('user')) {
            $this->getQB()
                 ->leftJoin('this.user', 'user');
        }


        if (!$this->aliasExist('referrer')) {
            $this->getQB()
                 ->leftJoin('this.referrer', 'referrer');
        }

        return $this;
    }

    public function filterByUserType($filterByReferral = true, $userType = User::USER_TYPE_BUYER, $storeType = null)
    {
        $filterBy = $filterByReferral ? 'user' : 'referrer';
        $this->joinUser();
        if ($userType === User::USER_TYPE_BUYER) {
            $this->getQB()
                 ->andWhere("$filterBy.userType = :userType")
                 ->setParameter('userType', $userType);
        }
        else {
            $this->getQB()
                 ->andWhere("$filterBy.userType = :userType")
                 ->setParameter('userType', $userType);

            if ($storeType !== null) {
                $this->getQB()
                     ->innerJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = $filterBy")
                     ->andWhere("s.storeType = :storeType")
                     ->setParameter(":storeType", (int) $storeType === Store::STORE_TYPE_RESELLER 
                                                  ? Store::STORE_TYPE_RESELLER
                                                  : Store::STORE_TYPE_MERCHANT);
            }
        }

        return $this;
    }
}
