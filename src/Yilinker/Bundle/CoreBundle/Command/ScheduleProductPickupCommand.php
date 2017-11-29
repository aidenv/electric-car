<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections;
use Swift_Mailer;
use Swift_Message;
use Yilinker\Bundle\CoreBundle\Entity\Logistics;

use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;

/**
 * Class for schedluing pick-up of internal products
 */
class ScheduleProductPickupCommand extends ContainerAwareCommand
{
    const ORDER_PRODUCT_PER_ITERATION = 99;
    
    /**
     * Configure step
     */
    protected function configure()
    {
        $this
            ->setName('yilinker:product-package:schedule')
            ->setDescription('Schedules pick-up of internal products')
            ->addOption(
                'invoice_number',
                null,
                InputOption::VALUE_REQUIRED,
                'Invoice number of package to schedule shipment'
            )
            ->addOption(
                'order_product_id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Order Product Id'
            )
            ->addOption(
                'send_email_to',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Email recipient'
            )
            ->addOption(
                'date_from',
                null,
                InputOption::VALUE_REQUIRED,
                'Order products date_added date from filter YYYY-MM-DD H:i:s'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $invoiceNumber = $input->getOption('invoice_number', null);
        $orderProductIds = $input->getOption('order_product_id');
        $emailRecipients = $input->getOption('send_email_to', null);
        $dateFrom = $input->getOption('date_from', null);
        if($dateFrom !== null){
            $dateFrom = \DateTime::createFromFormat('Y-m-d H:i:s', $dateFrom);
        }

        $container = $this->getContainer();
        $ccDeveloper = $container->getParameter('reports_dev_email');

        $em = $container->get('doctrine')->getEntityManager();
        $logger = $container->get('yilinker_core.express_api_logger');
        $listHtml = "";
        $orderProductCount = 0;
        $runningCount = 0;
        do{
            $orderProducts = $em->getRepository('YilinkerCoreBundle:OrderProduct')
                                ->getShippableOrderProducts(
                                    $invoiceNumber, false, $runningCount, self::ORDER_PRODUCT_PER_ITERATION, $dateFrom
                                );
            $orderProducts = new Collections\ArrayCollection($orderProducts);
            $orderProductCount = $orderProducts->count();
            /**
             * Offsetting(pagination) is implemented here to avoid inifnite loops when there are cases where
             * shpments fails. An issues exist here where the offsetting skips every other set of results (due to the
             * successful shipments affecting the next execution of getShippableOrderProducts 
             * [max of self::ORDER_PRODUCT_PER_ITERATION]). In any case, this script is designed to be ran every few minutes
             * so the pool of shippable products will eventually still empty out.
             */
            $runningCount += $orderProductCount;
            if($orderProductIds){
                $criteria = Criteria::create()
                          ->andWhere(Criteria::expr()->in("orderproductId", $orderProductIds));
                $orderProducts = $orderProducts->matching($criteria);
            }

            if (count($orderProducts) > 0) {
                $expressService = $this->getContainer()->get('yilinker_core.logistics.yilinker.express');
                $groupedOrderProducts = array();

                /**
                 * Group order products by order then by seller
                 */
                foreach ($orderProducts as $orderProduct) {
                    if ($this->is3rdPartyLogistics($orderProduct)) {
                        continue;
                    }

                    $orderId = $orderProduct->getOrder()->getInvoiceNumber();
                    $sellerId = $orderProduct->getSeller()->getUserId();
                    $userWareHouseId = !is_null($orderProduct->getUserWarehouse()) ? $orderProduct->getUserWarehouse()->getUserWareHouseId() : null;
                    $waybillRequestStatus = $orderProduct->getWaybillRequestStatus();
                    $sellerIdUserWareHouseId = !is_null($userWareHouseId) ? $sellerId.'-'.$userWareHouseId : $sellerId.'-0';

                    if (!$this->isOrderProductShippable($orderProduct) || !in_array($waybillRequestStatus['id'], $expressService->getProcessableStatuses())) {
                        continue;
                    }

                    if (!isset($groupedOrderProducts[$orderId][$sellerIdUserWareHouseId])) {
                        $groupedOrderProducts[$orderId][$sellerIdUserWareHouseId] = array();
                    }

                    $groupedOrderProducts[$orderId][$sellerIdUserWareHouseId][] = $orderProduct;
                }

                foreach ($groupedOrderProducts as $key => $groupedOrderProduct) {
                    $result = array();
                    $dateToday = new \Datetime();
                    $remark = "Shipped automatically by Yilinker Online";
                    $sellerIdUserWareHouseId = array_keys($groupedOrderProduct)[0];
                    $sellerId = isset(explode('-', $sellerIdUserWareHouseId)[0]) ? explode('-', $sellerIdUserWareHouseId)[0] : '(Error in)' . $sellerIdUserWareHouseId;

                    $shipmentResponse = $expressService->schedulePickup($dateToday, $remark, $groupedOrderProduct[$sellerIdUserWareHouseId]);

                    if ((bool) $shipmentResponse['isSuccessful'] == true) {
                        $output->writeln("Invoice Number: ".$key." Seller ID: ".$sellerId." => SUCCESSFUL");
                        $expressService->updateDateWaybillRequested($groupedOrderProduct[$sellerIdUserWareHouseId], Carbon::now());
                        $result = array(
                            'isSuccessful' => true,
                            'message'      => 'Waybill requested with (' . count($groupedOrderProduct[$sellerIdUserWareHouseId]) . ') products',
                            'data'         => array(
                                'sellerIdUserWareHouseId' => $sellerIdUserWareHouseId,
                                'invoiceNumber'           => $key,
                                'dateWaybillRequest'      => Carbon::now()->format('H:i:s A'),
                                'orderProductIds'         => implode($shipmentResponse['data'])
                            )
                        );
                        $logger->getLogger()->info(json_encode($result));
                    }
                    else {
                        $message = "Invoice Number: ".$key." Seller ID: ".$sellerId." => FAILED: ".$shipmentResponse['message'];
                        $output->writeln($message);
                        $listHtml .= "<li>".$message."</li>";
                        $result = array(
                            'isSuccessful' => false,
                            'message'      => $shipmentResponse['message'],
                            'data'         => array(
                                'sellerIdUserWareHouseId' => $sellerIdUserWareHouseId,
                                'invoiceNumber'           => $key
                            ),
                        );
                        $logger->getLogger()->err(json_encode($result));
                    }

                }
            }
        }
        while($orderProductCount > 0);

        if(count($emailRecipients) > 0){
            $dateToday = new \DateTime();
            $mailer = $container->get('mailer');
            $mailerEmail = $container->getParameter('mailer_user');
            $message = Swift_Message::newInstance();            
            $message->setSubject("Failed pickup schedule: ".$dateToday->format('Y-m-d H:i:s'))
                    ->setFrom($mailerEmail)
                    ->addCc($ccDeveloper)
                    ->setTo($emailRecipients)
                    ->setBody('<ul>'.$listHtml.'<ul>', 'text/html');
            
            $mailer->send($message);
            $output->writeln("Email sent to ".implode(",",$emailRecipients)."\n");
        }
        $output->writeln("Execution complete ... [OK]\n");
    }

    private function getAllowableStatus()
    {
        return array(
            OrderProduct::STATUS_PAYMENT_CONFIRMED,
            OrderProduct::STATUS_COD_TRANSACTION_CONFIRMED,
        );
    }

    private function isOrderProductShippable($orderProduct)
    {
        $allowedOrderProductStatuses = $this->getAllowableStatus();
        if ($orderProduct->getOrderProductStatus()
                        && in_array($orderProduct->getOrderProductStatus()->getOrderProductStatusId(), $allowedOrderProductStatuses)
                        && (bool) $orderProduct->getIsNotShippable() === false) {
            return true;
        }

        return false;
    }

    private function is3rdPartyLogistics($orderProduct)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();
        $userwarehouse = $orderProduct->getUserWarehouse();    
        
        if ( !is_null($userwarehouse) ) {
            
            $conn = $em->getConnection();
            $warehouse = $conn->fetchAll("
                select count(*) as count from ProductWarehouse
                where user_warehouse_id = :userWarehouse
                and product_id = :product_id
                and logistics_id = :logistics"
                , array(
                    'userWarehouse' => $userwarehouse->getUserWareHouseId(),
                    'logistics'     => 2,
                    'product_id'    => $orderProduct->getProduct()->getProductId(),
                    )
                );

            return $warehouse[0]['count'] > 0 ? true : false;
        }


    }
}


