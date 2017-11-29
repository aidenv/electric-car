<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserPoint;
use Yilinker\Bundle\CoreBundle\Entity\UserPointReferral;
use Yilinker\Bundle\CoreBundle\Entity\UserReferral;

/**
 * Class UserPointReferralManager
 *
 * @package Yilinker\Bundle\CoreBundle\Services\User
 */
class UserPointReferralManager
{

    private $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct (EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Earn Referral Points
     *
     * @param User $user
     * @param UserReferral $userReferral
     * @param $points
     * @return UserPointReferral
     */
    public function earn (User $user, UserReferral $userReferral, $points)
    {
        $userPoints = new UserPointReferral();
        $userPoints->setUser($user);
        $userPoints->setSource($userReferral);
        $userPoints->setPoints($points);
        $userPoints->setType(UserPoint::REFERRAL);
        $userPoints->setDateAdded(Carbon::now());

        $this->em->persist($userPoints);
        $this->em->flush();

        return $userPoints;
    }

}
