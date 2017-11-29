<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand AS Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;

/**
 * Recalculate additional charge, freight charge and marketing charge based on original product category
 */
class FixTransactionChargesCommand extends Command
{

    const ENTRY_PER_ITERATION = 15;
    
    protected function configure()
    {
        $this
            ->setName('yilinker:transaction:recalculate-charges')
            ->setDescription('Re-calculate additional charge, freight charge and marketing charge of each order product based on PRODUCT category')
            ->addOption(
                'fromOrderId',
                null,
                InputOption::VALUE_REQUIRED,
                'Order Id to set as the first result'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();
        $fromOrderId = $input->getOption('fromOrderId', null);
                    
        $resultCount = 0;
        $runningOffset = 0;
        do{
            $queryBuilder = $em->createQueryBuilder();       
            $queryBuilder->select("o")
                ->from("YilinkerCoreBundle:UserOrder", "o")
                ->setFirstResult($runningOffset)
                ->setMaxResults(self::ENTRY_PER_ITERATION);

            if($fromOrderId !== null){
                $queryBuilder->andWhere("o.orderId >= :fromOrderId")
                             ->setParameter("fromOrderId", $fromOrderId);
            }

            $userOrders = $queryBuilder->getQuery()->getResult();
            $resultCount = count($userOrders);
            $runningOffset += $resultCount;
            
            foreach($userOrders as $order){

                $orderTotalHandlingFee = "0.00";
                $orderTotalYilinkerCharge = "0.00";
                $orderTotalAdditionalCharge = "0.00";
                $orderProducts = $order->getOrderProducts();
                foreach($orderProducts as $orderProduct){
                    $quantity = $orderProduct->getQuantity();
                    $unitPrice = $orderProduct->getUnitPrice();                    
                    $totalPrice = bcmul($quantity, $unitPrice, 8);
                    $productCategory = $orderProduct->getProductCategory();

                    $handlingFee = ProductCategory::SHIPPING_FEE_COMPUTE_AS_PERCENTAGE
                                 ? bcmul($totalPrice, bcdiv($productCategory->getHandlingFee(), "100.00", 8), 8)
                                 : bcmul($productCategory->getHandlingFee(), $quantity, 8);

                    $yilinkerCharge = ProductCategory::YILINKER_CHARGE_COMPUTE_AS_PERCENTAGE
                                    ? bcmul($totalPrice, bcdiv($productCategory->getYilinkerCharge(),"100.00", 8), 8)
                                    : bcmul($productCategory->getYilinkerCharge(), $quantity, 8);

                    $additionalCharge = ProductCategory::ADDITIONAL_COST_COMPUTE_AS_PERCENTAGE
                                      ? bcmul($totalPrice, bcdiv($productCategory->getAdditionalCost(), "100.00", 8), 8)
                                      : bcmul($productCategory->getAdditionalCost(), $quantity, 8);
                    
                    /**
                     * Set order product charges
                     */
                    $orderProduct->setHandlingFee($handlingFee);
                    $orderProduct->setYilinkerCharge($yilinkerCharge);
                    $orderProduct->setAdditionalCost($additionalCharge);
                    $orderProduct->recalculateNet();
                    $orderTotalHandlingFee = bcadd($orderTotalHandlingFee, $handlingFee, 10);
                    $orderTotalYilinkerCharge = bcadd($orderTotalYilinkerCharge, $yilinkerCharge, 10);
                    $orderTotalAdditionalCharge = bcadd($orderTotalAdditionalCharge, $additionalCharge, 10);

                    /**
                     * Set Reset EarningTransaction amount (for sales only)
                     */
                    $earningTransactions = $orderProduct->getEarningTransactions();
                    foreach($earningTransactions as $earningTransaction){
                        $earning = $earningTransaction->getEarning();
                        $earningTypeId = $earning->getEarningType()->getEarningTypeId();
                        if($earningTypeId === EarningType::SALE){
                            $earning->setAmount($orderProduct->getNet());
                        }
                    }
                    
                    $output->writeln("Update net amount and charges for order id: ".$order->getOrderId().", order product id: ".$orderProduct->getOrderProductId());
                    $em->flush();
                }

                $order->setHandlingFee($orderTotalHandlingFee)
                    ->setYilinkerCharge($orderTotalYilinkerCharge)
                    ->setAdditionalCost($orderTotalAdditionalCharge);

                $runningNet = bcsub($order->getOrderTotalBasedOnOrderProducts(), $order->getHandlingFee(), 8);
                $runningNet = bcsub($runningNet, $order->getYilinkerCharge(), 8);
                $runningNet = bcsub($runningNet, $order->getAdditionalCost(), 8);
                $order->setNet($runningNet);

                $em->flush();
            }
        }
        while($resultCount > 0);

        $output->writeln("Completed ... [OK]");
    }

}
