<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\EarningTransaction;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Carbon\Carbon;

class CreateEarningRecordCommand extends Command
{
    const ORDERPRODUCT_PER_ITERATION = 100;
    
    protected function configure()
    {
        $this
            ->setName('yilinker:earning:affiliate-seller-transactions')
            ->setDescription('Record earnings for old transaction of sellers and affiliates');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();

        $transactionService = $container->get('yilinker_core.service.transaction');
        $earneableOrderProductStatuses = $transactionService->getOrderProductSalesStatuses();
        $earneableOrderProductStatuses[] = OrderProduct::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY;
        /**
         * Include buyer cancellation request. If the cancellation is approved there is a listener to handle setting the tentative earning to invalid
         */
        $earneableOrderProductStatuses[] = OrderProduct::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY;


        $limit = self::ORDERPRODUCT_PER_ITERATION;
        $orderProductCount = 0;
        $offset = 0;
        do{        
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                           ->getSellerAffiliateOrderProductsWithNoEarning($earneableOrderProductStatuses, array(
                               EarningType::SALE,
                               EarningType::AFFILIATE_COMMISSION
                           ), $limit, $offset);
            $orderProductCount = count($orderProducts);
            $offset += $orderProductCount;

            $dateNow = Carbon::now();
            foreach($orderProducts as $orderProduct){
                $earning = new Earning();
                $seller = $orderProduct->getSeller();
                $amount = null;
                if($orderProduct->getProduct()->getIsResold()){
                    $amount = $orderProduct->getCommission();
                    $type = EarningType::AFFILIATE_COMMISSION;
                }
                else{
                    $amount = $orderProduct->getNet();
                    $type = EarningType::SALE;
                }

                if($amount !== null){

                    $earningType = $em->getReference("YilinkerCoreBundle:EarningType", $type);
                    $earning->setUser($seller);
                    $earning->setAmount($amount);
                    $earning->setEarningType($earningType);
                    $earning->setStatus(Earning::TENTATIVE);
                    $earning->setDateAdded($orderProduct->getDateAdded());
                    $earning->setDateLastModified($dateNow);
                    $em->persist($earning);

                    $earningTransaction = new EarningTransaction();
                    $earningTransaction->setEarning($earning);
                    $earningTransaction->setOrderProduct($orderProduct);
                    $earningTransaction->setOrder($orderProduct->getOrder());
                    $em->persist($earningTransaction);
            
                    $em->flush();
                    $messageString = "Add tentative earning (orderproductid # ".$orderProduct->getOrderProductId().") PHP " . number_format($amount, 2, ".", ","). " for " .
                                   ($type !== EarningType::SALE ? "affiliate (commission): " : "seller (sale): ") . $seller->getUserId();
                    $output->writeln($messageString);
                }
            }
        }
        while($orderProductCount > 0);
        
        $output->writeln("Completed ... [OK]");        
    }

}