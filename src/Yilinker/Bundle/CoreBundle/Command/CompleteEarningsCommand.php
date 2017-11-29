<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\PackageStatus;

class CompleteEarningsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yilinker:earnings:complete')
            ->setDescription('Complete Earnings from Transaction if n days have passed after the user Recieved the item')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'days',
                7
            )
            ->addOption(
                'invoices',
                'i',
                InputOption::VALUE_IS_ARRAY + InputOption::VALUE_OPTIONAL,
                'invoices'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $days = $input->getOption('days');
        $invoiceNumbers = $input->getOption('invoices');
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();

        $tbEarning = $em->getRepository('YilinkerCoreBundle:Earning');
        if ($invoiceNumbers) {
            $packageReceived = $em->getReference('YilinkerCoreBundle:PackageStatus', PackageStatus::STATUS_RECEIVED_BY_RECIPIENT);
            $earnings = $tbEarning->getForCompletionInvoiceNumbers($invoiceNumbers);
            foreach ($earnings as $earning) {
                if ($this->releasePaymentOfOrderProduct($earning)) {
                    $orderProduct = $earning->getEarningTransaction()->getOrderProduct();
                    if ($orderProduct) {
                        $packageDetails = $orderProduct->getPackageDetails();
                        foreach ($packageDetails as $packageDetail) {
                            $package = $packageDetail->getPackage();
                            $package->setPackageStatus($packageReceived);
                        }
                    }

                    $earning->setStatus(Earning::COMPLETE);
                    $msg = "Set seller/affiliate tentative earning [Earning Id: ".$earning->getEarningId()."] to complete";
                }
                else {
                    $msg = "Invalid OrderProduct in Earning Transaction, Unable change earning [Earning Id: ".$earning->getEarningId()."] to complete";
                }
                $output->writeln($msg);
            }

            $em->flush();
        }
        else {
            /**
             * Complete seller/affiliate related earning
             */
            while ($earnings = $tbEarning->getSellerAffiliateEarningForCompletion($days)) {

                foreach ($earnings as $earning) {

                    if ($this->releasePaymentOfOrderProduct($earning)) {
                        $earning->setStatus(Earning::COMPLETE);
                        $msg = "Set seller/affiliate tentative earning [Earning Id: ".$earning->getEarningId()."] to complete";
                    }
                    else {
                        $msg = "Invalid OrderProduct in Earning Transaction, Unable change earning [Earning Id: ".$earning->getEarningId()."] to complete";
                    }

                    $output->writeln($msg);
                }

                $em->flush();
            }

            /**
             * Complete buyer network earning
             */
            while ($earnings = $tbEarning->getBuyerNetworkEarningForCompletion($days)) {

                foreach ($earnings as $earning) {

                    if ($this->releasePaymentOfOrderProduct($earning)) {
                        $earning->setStatus(Earning::COMPLETE);
                        $msg = "Set buyer tentative earning [Earning Id: ".$earning->getEarningId()."] to complete";
                    }
                    else {
                        $msg = "Invalid OrderProduct in Earning Transaction, Unable change earning [Earning Id: ".$earning->getEarningId()."] to complete";
                    }

                    $output->writeln($msg);
                }

                $em->flush();
            }
        }
    }

    private function releasePaymentOfOrderProduct($earning)
    {
        $isPaymentReleased = false;
        $earningTransaction = $earning->getEarningTransaction();
        if ($earningTransaction) {
            $container = $this->getContainer();
            $em = $container->get('doctrine')->getEntityManager();
            $paymentReleased = $em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProduct::STATUS_SELLER_PAYMENT_RELEASED);
            $paymentReleased->__load();
            
            $orderProduct = $earningTransaction->getOrderProduct();
            if ($orderProduct) {
                $orderProduct->setOrderProductStatus($paymentReleased);
                $isPaymentReleased = true;
            }
            elseif ($earning->getEarningType()->getEarningTypeId() == EarningType::BUYER_TRANSACTION) {
                $isPaymentReleased = true;
            }
        }

        return $isPaymentReleased;
    }
}