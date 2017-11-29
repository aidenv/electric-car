<?php

namespace Yilinker\Bundle\FrontendBundle\Services\UserPromo;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\UserDailyLogin;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserPoint;
use Yilinker\Bundle\CoreBundle\Entity\UserPointDailyLogin;

/**
 * Class DailyLoginManager
 *
 * @package Yilinker\Bundle\FrontendBundle\Services\UserPromo
 */
class DailyLoginManager
{

    /**
     * @var Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Add Daily login/Register User to daily login promo
     *
     * @param User $user
     * @return UserDailyLogin
     */
    public function registerDailyLoginPromo (User $user)
    {
        $user = $this->updateConsecutiveLoginCount($user);
        $userPointDailyLogin = $this->addDailyLoginPoints($user, UserPoint::POINTS_DAILY_LOGIN);

        if ( (int) $user->getConsecutiveLoginCount() % UserPoint::DAILY_LOGIN_CONSECUTIVE_LOGIN == 0) {
            $userPointDailyLogin = $this->addDailyLoginPoints($user, UserPoint::BONUS_POINTS_DAILY_LOGIN);
        }

        return $userPointDailyLogin;
    }

    /**
     * Checks if user is qualified in daily login
     *
     * @param User $user
     * @return bool
     */
    public function isUserQualified (User $user)
    {
        $args = array (
            'user'     => $user,
            'dateFrom' => Carbon::now()->startOfDay()->format('Y-m-d H:i:s'),
            'dateTo'   => Carbon::now()->endOfDay()->format('Y-m-d H:i:s'),
            'type'     => UserPoint::DAILY_LOGIN
        );
        $userPoint = $this->em->getRepository('YilinkerCoreBundle:UserPoint')
                              ->filterBy($args)->getResult();

        return (bool) !($userPoint);
    }

    /**
     * Update Consecutive login count
     *
     * @param User $user
     * @return User
     */
    public function updateConsecutiveLoginCount (User $user)
    {
        $hasEntry = false;
        $args = array (
            'user' => $user,
            'type' => UserPoint::DAILY_LOGIN,
        );
        $latestUserPoint = $this->em->getRepository('YilinkerCoreBundle:UserPoint')
                                    ->findOneBy($args, array('dateAdded' => 'DESC'));

        if ($latestUserPoint instanceof UserPoint) {
            $hasEntry = true;
            $yesterdayStartOfDay = strtotime(Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s'));
            $yesterdayEndOfDay = strtotime(Carbon::yesterday()->endOfDay()->format('Y-m-d H:i:s'));
            $lastLoginDate = strtotime($latestUserPoint->getDateAdded()->format('Y-m-d'));
        }

        if ($hasEntry === true && $lastLoginDate >= $yesterdayStartOfDay && $lastLoginDate < $yesterdayEndOfDay) {
            $user->setConsecutiveLoginCount( (int) $user->getConsecutiveLoginCount() + 1);
        }
        else {
            $user->setConsecutiveLoginCount(1);
        }

        $this->em->flush();

        return $user;
    }

    /**
     * Add Bonus Daily login point
     *
     * @param $user
     * @param int $points
     * @return UserPointDailyLogin
     */
    public function addDailyLoginPoints(User $user, $points)
    {
        $userPointDailyLogin = new UserPointDailyLogin();
        $userPointDailyLogin->setSource($user);
        $userPointDailyLogin->setUser($user);
        $userPointDailyLogin->setPoints($points);
        $userPointDailyLogin->setType(UserPoint::DAILY_LOGIN);
        $userPointDailyLogin->setDateAdded(Carbon::now());

        $this->em->persist($userPointDailyLogin);
        $this->em->flush();

        return $userPointDailyLogin;
    }

}
