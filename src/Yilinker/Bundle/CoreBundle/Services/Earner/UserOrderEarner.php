<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningTransaction;

class UserOrderEarner extends AbstractEarner
{
    protected  function createObject(&$earning)
    {
        $earningTransaction = new EarningTransaction;
        $earningTransaction->setOrder($this->secondaryEntity);
        $earning->setEarningTransaction($earningTransaction);
    }

    public function earn()
    {
        $this->earnReferrer();
    }

    private function earnReferrer()
    {
        $userOrder = $this->secondaryEntity;
        $userReferral = $userOrder->getBuyer()->getUserReferral();

        if ($userReferral) {
            $referrer = $userReferral->getReferrer();

            $earning = $this->createEarningEntity();
            $earning->setEarningType($this->em->find('YilinkerCoreBundle:EarningType', EarningType::BUYER_TRANSACTION))
                    ->setStatus(Earning::TENTATIVE)
                    ->setUser($referrer)
                    ->setAmount(Earnings::BUYER_TRANSACTION);

            $this->em->persist($earning);

            return $earning;
        }

        return false;
    }
}
