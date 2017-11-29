<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\EarningUserRegistration;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Services\Earner\Earnings;

class RegistrationEarner extends AbstractEarner
{
    protected function createObject(&$earning)
    {
        $earningUserReference = new EarningUserRegistration;
        $earningUserReference->setEarning($earning)
                             ->setUser($this->secondaryEntity);

        $this->em->persist($earningUserReference);
    }

    private function earnBuyerRegistration()
    {
        $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::BUYER_REGISTRATION);

        $user = $this->secondaryEntity;
        $userReferral = $user->getUserReferral();
        $userType = (int) $user->getUserType();

        if ($userReferral
            && $userType === User::USER_TYPE_BUYER
            && (int) $userReferral->getReferrer()->getUserType() === User::USER_TYPE_SELLER) {
            $earning = $this->createEarningEntity();
            $earning->setAmount(Earnings::REGISTER_BUYER)
                    ->setStatus(Earning::COMPLETE)
                    ->setUser($userReferral->getReferrer())
                    ->setEarningType($earningType);

            $this->em->persist($earning);

            return $earning;
        }
        return false;
    }

    private function earnAffiliateRegistration()
    {
        $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::AFFILIATE_REGISTRATION);

        $user = $this->secondaryEntity;
        $userReferral = $user->getUserReferral();
        $userType = (int) $user->getUserType();

        if ($userReferral
            && $userType === User::USER_TYPE_SELLER
            && $user->getStore()->isAffiliate()
            && (int) $userReferral->getReferrer()->getUserType() === User::USER_TYPE_SELLER
            && $userReferral->getReferrer()->getStore()->isAffiliate()) {

            $earning = $this->createEarningEntity();
            $earning->setAmount(Earnings::REGISTER_AFFILIATE)
                    ->setStatus(Earning::COMPLETE)
                    ->setUser($userReferral->getReferrer())
                    ->setEarningType($earningType);

            $this->em->persist($earning);

            return $earning;
        }
        return false;
    }

    public function earn()
    {
        $this->earnBuyerRegistration();
        $this->earnAffiliateRegistration();
    }
}
