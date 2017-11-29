<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\EarningTransaction;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;

class OrderProductEarner extends AbstractEarner
{
    protected function createObject(&$earning)
    {
        $earningTransaction = new EarningTransaction;
        $earningTransaction->setOrderProduct($this->secondaryEntity)
                           ->setOrder($this->secondaryEntity->getOrder());
        $earning->setEarningTransaction($earningTransaction);
    }

    public function earn()
    {
        $this->earnAffiliateReferrer();
        $this->earnMerchant();
    }

    private function earnAffiliateReferrer()
    {
        $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::AFFILIATE_TRANSACTION);

        $orderProduct = $this->secondaryEntity;

        if ($orderProduct->getSeller()->getUserReferral()) {
            $existingEarning = $this->em->getRepository('YilinkerCoreBundle:EarningTransaction')
                                        ->qb()
                                        ->whereQuery(array(
                                            'orderProduct' => $orderProduct,
                                            'user' => $orderProduct->getSeller()->getUserReferral()->getReferrer(),
                                            'earningType' => EarningType::AFFILIATE_TRANSACTION
                                        ))
                                        ->getCount();

            if ($orderProduct->isBought()
                && $existingEarning <= 0
                && $orderProduct->getProduct()->getIsResold()
                && $orderProduct->getSeller()->getUserReferral()) {

                $earning = $this->createEarningEntity();
                $earning->setAmount((Earnings::AFFILIATE_COMMISION_PERCENTAGE / 100) * $orderProduct->getCommission())
                        ->setStatus(Earning::TENTATIVE)
                        ->setUser($orderProduct->getSeller()->getUserReferral()->getReferrer())
                        ->setEarningType($earningType);

                $this->em->persist($earning);

                return $earning;
            }
        }

        return false;
    }

    public function earnMerchant()
    {
        $orderProduct = $this->secondaryEntity;

        if ($orderProduct->isBought() || $orderProduct->itemReceivedByBuyer()) {
            $tbEarningTransaction = $this->em->getRepository('YilinkerCoreBundle:EarningTransaction');
            $user = $orderProduct->getSeller();

            $earning = $this->createEarningEntity();
            $earning
                ->setStatus(Earning::TENTATIVE)
                ->setUser($user)
            ;

            if ($orderProduct->getProduct()->getIsResold()) {
                $earningType = $this->em->getReference('YilinkerCoreBundle:EarningType', EarningType::AFFILIATE_COMMISSION);
                $amount = (float) $orderProduct->getCommission();
            }
            else {
                $earningType = $this->em->getReference('YilinkerCoreBundle:EarningType', EarningType::SALE);
                $amount = isset($this->parameter['net'])
                          ? (float) $this->parameter['net']
                          : 0;
            }

            $earning
                ->setAmount($amount)
                ->setEarningType($earningType)
            ;

            $alreadyExists = $tbEarningTransaction
                ->qb()
                ->whereQuery(compact('orderProduct', 'user', 'earningType'))
                ->getCount()
            ;

            if (!$alreadyExists) {
                $this->em->persist($earning);
            }
        }
    }
}
