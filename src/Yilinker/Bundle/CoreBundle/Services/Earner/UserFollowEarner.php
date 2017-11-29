<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Services\Earner\Earnings;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\EarningFollow;

class UserFollowEarner extends AbstractEarner
{
    protected function createObject(&$earning)
    {
        $earningFollow = new EarningFollow;
        $earningFollow->setEarning($earning)
                      ->setUserFollowHistory($this->secondaryEntity);

        $this->em->persist($earningFollow);
    }

    public function earn()
    {
        $this->earnFollowee();
    }

    private function earnFollowee()
    {
        $userFollowHistory = $this->secondaryEntity;

        $existingFollowHistory = $this->em->getRepository('YilinkerCoreBundle:UserFollowHistory')
                                          ->findBy(array(
                                              'follower' => $userFollowHistory->getFollower(),
                                              'followee' => $userFollowHistory->getFollowee(),
                                              'isFollow' => true,
                                          ));

        $earningFollow = $this->em->getRepository('YilinkerCoreBundle:EarningFollow')
                                  ->findByUserFollowHistory($existingFollowHistory);

        if (count($earningFollow) === 0) {
            $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::FOLLOW);
            $earning = $this->createEarningEntity();
            $earning->setStatus(Earning::COMPLETE)
                    ->setUser($userFollowHistory->getFollowee())
                    ->setAmount($this->computeByBracket())
                    ->setEarningType($earningType);

            $this->em->persist($earning);
            return $earning;
        }

        return false;
    }

    private function computeByBracket()
    {
        $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::FOLLOW);
        $userFollowHistory = $this->secondaryEntity;

        $follows = $this->em->getRepository('YilinkerCoreBundle:Earning')
                            ->findBy(array(
                                'user' => $userFollowHistory->getFollowee(),
                                'status' => Earning::COMPLETE,
                                'earningType' => $earningType
                            ));

        $followCount = count($follows) + 1;

        $earnings = $this->em->getRepository('YilinkerCoreBundle:EarningTypeRange')
                             ->getEarningByRange($earningType, $followCount);

        $amount = 0;
        if ($earnings) {
            $amount += $earnings->getEarning();
            if ($earnings->getTo() && $followCount === $earnings->getTo()) {
                $amount += $earnings->getBonus();
            }
        }
        else {
            $earnings = $this->em->getRepository('YilinkerCoreBundle:EarningTypeRange')
                                 ->getEarningWithToIsNull($earningType, $followCount);
            if ($earnings) {
                $amount += $earnings->getEarning();
                if ($followCount === $earnings->getTo()) {
                    $amount += $earnings->getBonus();
                }
            }
        }

        return $amount;
    }
}
