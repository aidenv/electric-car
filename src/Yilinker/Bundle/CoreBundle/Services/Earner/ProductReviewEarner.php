<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Services\Earner\Earnings;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\EarningReview;

class ProductReviewEarner extends AbstractEarner
{
    protected function createObject(&$earning)
    {
        $earningReview = new EarningReview;
        $earningReview->setEarning($earning)
                      ->setProductReview($this->secondaryEntity);

        $this->em->persist($earningReview);
    }

    public function earn()
    {
        $this->earnProductOwner();
    }

    private function earnProductOwner()
    {
        $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::COMMENT);
        $productReview = $this->secondaryEntity;

        $existingProductReview = $this->em->getRepository('YilinkerCoreBundle:ProductReview')
                                          ->findOneBy(array(
                                              'reviewer' => $productReview->getReviewer(),
                                              'product' => $productReview->getProduct(),
                                              'orderProduct' => $productReview->getOrderProduct(),
                                          ));

        if ($existingProductReview === null) {
            $earning = $this->createEarningEntity();
            $earning->setStatus(Earning::COMPLETE)
                    ->setUser($productReview->getProduct()->getUser())
                    ->setAmount($this->computeByBracket())
                    ->setEarningType($earningType);

            $this->em->persist($earning);

            return $earning;
        }

        return false;
    }

    private function computeByBracket()
    {
        $earningType = $this->em->find('YilinkerCoreBundle:EarningType', EarningType::COMMENT);
        $productReview = $this->secondaryEntity;
        $reviews = $this->em->getRepository('YilinkerCoreBundle:Earning')
                            ->findBy(array(
                                'user' => $productReview->getProduct()->getUser(),
                                'status' => Earning::COMPLETE,
                                'earningType' => $earningType
                            ));

        $reviewCount = count($reviews) + 1;

        $earnings = $this->em->getRepository('YilinkerCoreBundle:EarningTypeRange')
                             ->getEarningByRange($earningType, $reviewCount);

        $amount = 0;

        if ($earnings) {
            $amount += $earnings->getEarning();
            if ($earnings->getTo() && $reviewCount === $earnings->getTo()) {
                $amount += $earnings->getBonus();
            }
        }
        else {
            $earnings = $this->em->getRepository('YilinkerCoreBundle:EarningTypeRange')
                                 ->getEarningWithToIsNull($earningType, $reviewCount);
            if ($earnings) {
                $amount += $earnings->getEarning();
                if ($reviewCount === $earnings->getTo()) {
                    $amount += $earnings->getBonus();
                }
            }
        }

        return $amount;
    }
}
