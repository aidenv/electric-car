<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class UserAddressRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserAddressRepository extends EntityRepository
{
    public function getUserAddresses(User $user, $orderBy = "ASC")
    {
        $queryBuilder = $this->createQueryBuilder("ua");

        return $queryBuilder->where("ua.user = :user")
                            ->andWhere("ua.isDelete = :isDelete")
                            ->setParameter(":user", $user)
                            ->setParameter(":isDelete", false)
                            ->orderBy("ua.dateAdded", $orderBy)
                            ->getQuery()
                            ->execute();
    }

    /**
     * Bulk reset User address
     *
     * @param User $user
     */
    public function resetDefaultUserAddress(User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->update("YilinkerCoreBundle:UserAddress", "ua")
                     ->set("ua.isDefault", "false")
                     ->where("ua.user = :user")
                     ->andWhere("ua.isDefault = true")
                     ->setParameter(":user", $user)
                     ->getQuery()
                     ->execute();
    }

    /**
     * Get most recent user default addresss
     *
     * @param integer $userId
     * @return Yilinker\Bundle\CoreBundle\Entity\UserAddress
     */
    public function getUserDefaultAddress($userId, $all = false)
    {
        $userAddress = $this->createQueryBuilder('a')
                            ->where('a.user = :userId')
                            ->andWhere('a.isDefault = :isDefault')
                            ->andWhere('a.isDelete = :isDelete')
                            ->setParameter('userId', $userId)
                            ->setParameter('isDefault', true)
                            ->setParameter('isDelete', false)
                            ->orderBy('a.dateAdded', 'DESC')
                            ->getQuery()
                            ->getResult();

        if ($all) {
            return $userAddress;
        }

        if (is_array($userAddress)) {
            $userAddress = array_shift($userAddress);
        }
        else {
            $userAddress = null;
        }

        return $userAddress;
    }

    public function getAddressOfUser($userId, $addresssId)
    {
        $addresses = $this->qb()
                          ->andWhere('this.userAddressId = :userAddressId')
                          ->andWhere('this.user = :user')
                          ->setParameter('userAddressId', $addresssId)
                          ->setParameter('user', $userId)
                          ->getQB()
                          ->getQuery()
                          ->getResult()
        ;

        return array_shift($addresses);
    }

    public function clearDefaults($user, $exclude = array())
    {
        $this
            ->qb()
            ->update()
            ->set('this.isDefault', ':isDefault')
            ->setParameter('isDefault', false)
            ->andWhere('this.user = :user')
            ->setParameter('user', $user)
        ;

        if ($exclude) {
            $this
                ->andWhere('this.userAddressId NOT IN (:exclude)')
                ->setParameter('exclude', $exclude)
            ;
        }

        $affected = $this
            ->getQB()
            ->getQuery()
            ->execute()
        ;

        return $affected;
    }
}
