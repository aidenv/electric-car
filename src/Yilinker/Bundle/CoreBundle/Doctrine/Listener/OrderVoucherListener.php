<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\OrderVoucher;

class OrderVoucherListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $orderVoucher = $args->getEntity();
        if ($orderVoucher instanceof OrderVoucher) {
            $userOrder = $orderVoucher->getOrder();
            $voucherCode = $orderVoucher->getVoucherCode();
            $voucher = $voucherCode->getVoucher();

            $origPrice = $userOrder->getTotalPrice();
            $less = 0;
            if ($voucher->isDiscountFixedAmount()) {
                $less = $voucher->getValue();
            }
            elseif ($voucher->isDiscountPercentage()) {
                $less = $origPrice * ($voucher->getValue() / 100);
            }
            $totalPrice = bcsub($origPrice, $less, 4);
            if ($totalPrice < 1) {
                $totalPrice = 1;
                if ($less) {
                    $less--;
                }
            }
            
            $orderVoucher->setValue($less);
            $userOrder->setTotalPrice($totalPrice);
            $userOrder->addOrderVoucher($orderVoucher);

            $allocatedPrice = 0;
            foreach ($userOrder->getOrderProducts() as $orderProduct) {
                $price = $orderProduct->getTotalPrice();
                $deductPercentage = bcdiv($price, $origPrice, 4);
                $deduction = bcmul($deductPercentage, $less, 4);
                $price = bcsub($price, $deduction, 4);
                $allocatedPrice = bcadd($allocatedPrice, $price, 4);
                $orderProduct->setTotalPrice($price);
                $orderProduct->recalculateNet();
            }

            $remaining = bcsub($totalPrice, $allocatedPrice, 4);
            if (isset($orderProduct) && $remaining) {
                $price = bcadd($price, $remaining, 4);
                $orderProduct->setTotalPrice($price);
                $orderProduct->recalculateNet();
            }
        }
    }
}
