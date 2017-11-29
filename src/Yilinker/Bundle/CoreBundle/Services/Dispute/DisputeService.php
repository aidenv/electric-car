<?php

namespace Yilinker\Bundle\CoreBundle\Services\Dispute;

use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\Voucher;
use Yilinker\Bundle\CoreBundle\Entity\DisputeDetailVoucher;
use Yilinker\Bundle\CoreBundle\Entity\Dispute;
use Yilinker\Bundle\CoreBundle\Entity\DisputeHistory;
use Yilinker\Bundle\CoreBundle\Entity\DisputeDetail;
use Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType;
use Carbon\Carbon;

class DisputeService
{
    private $container;
    private $em;
    private $mailer;

    public function setContainer($container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->mailer = $this->container->get('yilinker_core.service.user.mailer');
    }

    public function updateDisputeDetail($disputeDetail)
    {
        $disputeDetailClosed = DisputeDetail::DETAIL_STATUS_CLOSE;
        $disputeDetail->setStatus($disputeDetailClosed);
        $disputeClosed = true;
        foreach ($disputeDetail->getDispute()->getDisputeDetails() as $disputeDetail) {
            if ($disputeDetail->getStatus() != $disputeDetailClosed) {
                $disputeClosed = false;
                break;
            }
        }

        if ($disputeClosed) {
            $disputeStatusClose = $this->em->getReference('YilinkerCoreBundle:DisputeStatusType', DisputeStatusType::STATUS_TYPE_CLOSE);
            $disputeDetail->getDispute()->setDisputeStatusType($disputeStatusClose);

            $disputeHistory = new DisputeHistory();
            $disputeHistory->setDispute($disputeDetail->getDispute());
            $disputeHistory->setDisputeStatusType($disputeStatusClose);
            $this->em->persist($disputeHistory);
        }
    }

    public function approveDisputeDetails($ids, $approveAction)
    {
        switch ($approveAction) {
            case Dispute::APPROVE_REFUND:
                $this->approveRefund($ids);
                return true;
            case Dispute::APPROVE_REPLACE_DIFF_ITEM:
                $this->replaceVoucherDispute($ids);
                return true;
        }

        return false;
    }

    public function approveRefund($ids)
    {
        $refundApprovedStatus = $this->em->getReference(
            'YilinkerCoreBundle:OrderProductStatus',
            OrderProduct::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED
        );

        $tbDisputeDetail = $this->em->getRepository('YilinkerCoreBundle:DisputeDetail');
        $disputeDetails = $tbDisputeDetail->findByDisputeDetailId($ids);
        if (!$disputeDetails) {
            return false;
        }
        foreach ($disputeDetails as $disputeDetail) {
            $disputeDetail->getOrderProduct()->setOrderProductStatus($refundApprovedStatus);
            $this->updateDisputeDetail($disputeDetail);
        }
        $this->em->flush();
    }

    /**
     * must be of the same dispute head
     *
     * @param $ids  array of DisputeDetail.disputeDetailId
     */
    public function replaceVoucherDispute($ids)
    {
        $replacementApprovedStatus = $this->em->getReference(
            'YilinkerCoreBundle:OrderProductStatus',
            OrderProduct::STATUS_REPLACEMENT_PRODUCT_INSPECTION_APPROVED
        );

        $tbDisputeDetail = $this->em->getRepository('YilinkerCoreBundle:DisputeDetail');
        $disputeDetails = $tbDisputeDetail->findByDisputeDetailId($ids);
        if (!$disputeDetails) {
            return false;
        }
        $voucher = $this->createVoucher($disputeDetails);

        $this->em->persist($voucher);
        $disputeDetailVoucher = null;
        foreach ($disputeDetails as $disputeDetail) {
            $disputeDetail->getOrderProduct()->setOrderProductStatus($replacementApprovedStatus);
            $this->updateDisputeDetail($disputeDetail);

            $disputeDetailVoucher = new DisputeDetailVoucher;
            $disputeDetailVoucher->setDisputeDetail($disputeDetail);
            $disputeDetailVoucher->setVoucherCode($voucher->getVoucherCodes()->first());
            $this->em->persist($disputeDetailVoucher);
        }
        $this->em->flush();
        $this->sendSMSVOUCher($disputeDetails, $voucher);
        $this->mailer->sendEmailVoucher($disputeDetails, $voucher);
    }

    public function createVoucher($disputeDetails)
    {
        $dispute = reset($disputeDetails)->getDispute();
        $tbVoucherCode = $this->em->getRepository('YilinkerCoreBundle:VoucherCode');
        $value = 0;

        $orderProductMsg = array();
        foreach ($disputeDetails as $disputeDetail) {
            $orderProduct = $disputeDetail->getOrderProduct();
            $value += ($orderProduct->getUnitPrice() * $orderProduct->getQuantity());
            $orderProductMsg[] = 'x'.$orderProduct->getQuantity().' '.$orderProduct->getProductName();
        }

        $voucher = new Voucher;
        $voucher->setName(
            'Transaction Dispute w/ Case ID: '.
            $dispute->getTicket().' on products '.
            implode(', ', $orderProductMsg)
        );
        $voucher->setUsageType(Voucher::ONE_TIME_USE);
        $voucher->setQuantity(1);
        $voucher->setDiscountType(Voucher::FIXED_AMOUNT);
        $voucher->setValue($value);
        $voucher->setIsActive(true);
        $voucher->setStartDate(Carbon::now());
        $voucher->setEndDate(Carbon::now()->addMonth());
        $voucher = $tbVoucherCode->batchVoucherCodes($voucher);

        return $voucher;
    }

    public function sendSMSVoucher($disputeDetails, $voucher)
    {
        $voucherCode = $voucher->getVoucherCodes()->first();
        $dispute = reset($disputeDetails)->getDispute();
        $disputer = $dispute->getDisputer();

        if ($disputer->getIsMobileVerified() && $disputer->getContactNumber()) {
            $semaphore = $this->container->get('yilinker_core.service.sms.semaphore_sms');
            $contactNumber = $disputer->getContactNumber();

            $msgProducts = '';
            foreach ($disputeDetails as $disputeDetail) {
                $orderProduct = $disputeDetail->getOrderProduct();
                $msgProducts .= '    x'.$orderProduct->getQuantity().' '.$orderProduct->getProductName().'\n';
            }

            $semaphore->setMobileNumber($contactNumber);
            $semaphore->setMessage(
                'Your Dispute Case ID: '.$dispute->getTicket().' has been replaced by Voucher Code: '.$voucherCode->getCode().' worth '.$voucher->getValue(true).
                ' valid until'.$voucher->getEndDate('m/d/Y').
                '\n\n Affected product'.(count($disputeDetails) == 1 ? '': 's').': \n\n'.$msgProducts.
                'You can use this code for purchasing new items.'
            );
            $semaphore->sendSMS();
        }
    }
}