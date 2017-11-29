<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\VoucherCode;
use Yilinker\Bundle\CoreBundle\Entity\Voucher;
use Carbon\Carbon;

class VoucherCodeRepository extends EntityRepository
{

    private $generatedCodes = array();

    public function generateHash($prefix)
    {
        return hash(
            'adler32', 
            uniqid($prefix, true).$prefix.date('yyyy-MM-dd HH:mm:ss.SSS').'yi'
        );
    }

    public function generateCode($prefix = '')
    {
        do {
            $code = $this->generateHash($prefix);

            $generated = in_array($code, $this->generatedCodes);
            if (!$generated) {
                $this->generatedCodes[] = $code;
            }
            
        } while($generated);

        return $code;
    }

    public function batchVoucherCodes($voucher)
    {
        $quantity = $voucher->getQuantity();
        $nCodes = $voucher->getVoucherCodes()->count();
        $quantity -= $nCodes;
        while ($quantity--) {
            $code = $this->generateCode($quantity);
            $voucherCode = new VoucherCode;
            $voucherCode->setCode($code);
            $voucherCode->setVoucher($voucher);

            $voucher->addVoucherCode($voucherCode);
        }

        return $voucher;
    }

    public function getInactiveMessage($code)
    {
        $this
            ->qb()
            ->andWhere('this.code = :code')
            ->setParameter('code', $code)
        ;

        $msg = 'Invalid Code';
        $voucherCode = $this->getResult();
        $voucherCode = array_shift($voucherCode);
        if ($voucherCode) {
            $voucher = $voucherCode->getVoucher();
            if ($voucher) {
                $now = Carbon::now();
                $endDate = $voucher->getEndDate();
                if ($now > $endDate) {
                    $msg = 'Code is already Expired';
                }
            }
        }

        return $msg;
    }

    public function queryActiveVoucher($voucherCode, $user = null)
    {
        $now = Carbon::now();

        $this
            ->qb()
            ->addSelect('voucher.quantity as usageLimit')
            ->addSelect('voucher.usageType as usageType')
            ->innerJoin('this.voucher', 'voucher')
            ->leftJoin('this.orderVouchers', 'orderVouchers')
            ->andWhere('this.code = :code')
            ->andWhere('voucher.isActive = :active')
            ->andWhere('voucher.startDate < :now')
            ->andWhere('voucher.endDate > :now')
            ->andWhere('voucher.quantity > 0')
            ->andHaving('(count(orderVouchers) < 1 AND usageType = :usageOneTimeUse) OR (count(orderVouchers) < usageLimit AND usageType <> :usageOneTimeUse)')
            ->setParameter('usageOneTimeUse', Voucher::ONE_TIME_USE)
            ->setParameter('code', $voucherCode)
            ->setParameter('active', true)
            ->setParameter('now', $now)
        ;

        if ($user) {
            $this
                ->leftJoin('orderVouchers.order', 'order')
                ->leftJoin('order.buyer', 'buyer', 'WITH', 'buyer = :buyer')
                ->andHaving('usageType <> :usageOneTimeUsePerUser OR count(buyer) < 1')
                ->setParameter('usageOneTimeUsePerUser', Voucher::ONE_TIME_USE_PER_USER)
                ->setParameter('buyer', $user)
            ;
        }
    }

    public function getActiveVoucherCode($voucherCode, $user = null)
    {
        $this->queryActiveVoucher($voucherCode, $user);
        $vouchers = $this->getResult();
        $voucher = array_shift($vouchers);

        return is_array($voucher) ? array_shift($voucher): $voucher;
    }
}