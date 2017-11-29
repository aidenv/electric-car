<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class DailyLoginRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class DailyLoginRepository extends EntityRepository
{

    /**
     * Get daily login registered user
     *
     * @param User|null $user
     * @param null $dateTimeFrom
     * @param null $dateTimeTo
     * @return array
     */
    public function getRegisteredUser (User $user = null, $dateTimeFrom = null, $dateTimeTo = null)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select(array(
                'User',
                'COUNT(UserDailyLogin.userDailyLoginId) AS cnt'
            ))
            ->from('YilinkerCoreBundle:User', 'User')
            ->leftJoin('YilinkerCoreBundle:UserDailyLogin', 'UserDailyLogin', 'WITH', 'User.userId = UserDailyLogin.user');

        if ($user !== null) {
            $queryBuilder->andWhere('User.userId = :userId')
                         ->setParameter(':userId', $user->getUserId());
        }

        if ($dateTimeFrom !== null) {
            $queryBuilder->andWhere('UserDailyLogin.dateCreated >= :dateTimeFrom')
                         ->setParameter(':dateTimeFrom', $dateTimeFrom);
        }

        if ($dateTimeTo !== null) {
            $queryBuilder->andWhere('UserDailyLogin.dateCreated < :dateTimeTo')
                         ->setParameter(':dateTimeTo', $dateTimeTo);
        }

        $qbResult = $queryBuilder->groupBy('User.userId')
                                 ->getQuery();

        return $qbResult->getResult();
    }

}
