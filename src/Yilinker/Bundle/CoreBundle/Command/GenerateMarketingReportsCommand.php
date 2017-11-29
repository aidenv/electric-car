<?php
namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;


/**
 *
 */
class GenerateMarketingReportsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker:generate:marketing-report')
            ->setDescription('Generate marketing report')
            ->addOption(
                'send_email_to',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Email recipient'
            );
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {


       $dateFrom = Carbon::now()->subDay()->startOfDay();
        $dateTo = Carbon::now()->subDay()->endOfDay();

        $data = array(
            'Date From' => $dateFrom->format('Y-m-d H:i:s'),
            'Date To'   => $dateTo->format('Y-m-d H:i:s'),
        );

        $container = $this->getContainer();       
        $emailRecipients = $input->getOption('send_email_to', null);

        $em = $container->get('doctrine')->getManager();        


        
        $registrationTypes = array(
            User::REGISTRATION_TYPE_WEB,
            User::REGISTRATION_TYPE_MOBILE,
        );

        foreach($registrationTypes as $registrationType){
            
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select(array(
                "COUNT(u.userId) as userCount",
                "u.userType as userType",
                "s.storeType as storeType",
            ))
                ->from("YilinkerCoreBundle:User", "u")
                ->leftJoin("YilinkerCoreBundle:Store", "s", "WITH", "s.user = u")
                ->andWhere('u.dateAdded >= :dateFrom')
                ->andWhere('u.dateAdded <= :dateTo')
                ->andWhere('u.registrationType = :registrationType')
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo)
                ->setParameter('registrationType', $registrationType)
                ->groupBy('u.userType');
        
            $results = $queryBuilder->getQuery()
                     ->getResult();

            $type = $registrationType ? "Web" : "Mobile";
            $key = $type." Registered User Count";
            $data[$key] = array(
                'Buyers'     => 0,
                'Sellers'    => 0,
                'Affiliates' => 0,
            );

            foreach($results as $result){
                $userType = (int) $result['userType'];
                if($userType === User::USER_TYPE_BUYER){
                    $data[$key]['Buyers'] = $result['userCount'];
                }
                else if($userType === User::USER_TYPE_SELLER){
                    $storeType =  (int) $result['storeType'];
                    if($storeType === Store::STORE_TYPE_MERCHANT){
                        $data[$key]['Sellers'] = $result['userCount'];
                    }
                    else if($storeType === Store::STORE_TYPE_RESELLER){
                        $data[$key]['Affiliates'] = $result['userCount'];
                    }
                }
            }
        }

        $checkoutTypes = array(
            'Web'    => UserOrder::CHECKOUT_TYPE_WEB,
            'Mobile' => UserOrder::CHECKOUT_TYPE_MOBILE,
        );

        foreach($checkoutTypes as $checkoutType){
            $checkoutTypeString = $checkoutType ? 'Mobile' : 'Web';
            $transactionService = $container->get('yilinker_core.service.transaction');
            $validOrderStatus = $transactionService->getOrderStatusesValid();
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select(array(
                "COUNT(o.orderId) as totalTransactionCount",
            ))
                         ->from("YilinkerCoreBundle:UserOrder", "o")
                         ->andWhere('o.dateAdded >= :dateFrom')
                         ->andWhere('o.dateAdded <= :dateTo')
                         ->andWhere('o.checkoutType = :webCheckout')
                         ->andWhere('o.orderStatus IN (:orderStatuses)')
                         ->setParameter('orderStatuses', $validOrderStatus)
                         ->setParameter('dateFrom', $dateFrom)
                         ->setParameter('dateTo', $dateTo)
                         ->setParameter('webCheckout', $checkoutType);

            $data[$checkoutTypeString.' Transactions']['Total'] = $queryBuilder->getQuery()
                                                              ->getSingleScalarResult();

            $cancelledOrderProductStatuses = $transactionService->getOrderProductStatusesCancelled();
            $subQueryBuilder = $em->createQueryBuilder();
            $subQueryBuilder->select("o.orderId")
                            ->from("YilinkerCoreBundle:UserOrder", "o")
                            ->innerJoin("YilinkerCoreBundle:OrderProduct", "op", 'WITH',
                                        "op.order = o AND op.orderProductStatus IN (:orderProductStatuses)")
                            ->andWhere('o.dateAdded >= :dateFrom')
                            ->andWhere('o.dateAdded <= :dateTo')
                            ->andWhere('o.checkoutType = :checkoutType')
                            ->groupBy("o.orderId");

            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select(array("count(aggregated.orderId) as cancelledTransactionCount"))
                         ->from("YilinkerCoreBundle:UserOrder", "aggregated")
                         ->andWhere($queryBuilder->expr()->in('aggregated.orderId', $subQueryBuilder->getDql()))
                         ->setParameter('orderProductStatuses', $cancelledOrderProductStatuses)
                         ->setParameter('dateFrom', $dateFrom)
                         ->setParameter('dateTo', $dateTo)
                         ->setParameter('checkoutType', $checkoutType);

            $data[$checkoutTypeString.' Transactions']['Cancelled'] = $queryBuilder->getQuery()
                                                                                   ->getSingleScalarResult();

            $saleableOrderProductStatuses = $transactionService->getOrderProductSalesStatuses();
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select("SUM(op.totalPrice) as totalSales")
                         ->from("YilinkerCoreBundle:OrderProduct", "op")
                         ->innerJoin("YilinkerCoreBundle:UserOrder", "o", 'WITH',
                                     "op.order = o AND o.checkoutType = :checkoutType AND o.dateAdded >= :dateFrom AND o.dateAdded <= :dateTo")
                         ->andWhere('op.orderProductStatus in (:orderProductStatuses)')
                         ->setParameter('orderProductStatuses', $saleableOrderProductStatuses)
                         ->setParameter('dateFrom', $dateFrom)
                         ->setParameter('dateTo', $dateTo)
                         ->setParameter('checkoutType', $checkoutType);

            $total = $queryBuilder->getQuery()
                                  ->getSingleScalarResult();

            $data[$checkoutTypeString.' Transactions']['Total Sales (PHP)'] = number_format($total, 2, '.', ',');

            /**
             * Failed Credit Card Transactions
             */
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select("COUNT(o.orderId) as failedTransactions")
                         ->from("YilinkerCoreBundle:UserOrder", "o")
                         ->andWhere('o.paymentMethod = :pesopay')
                         ->andWhere('o.orderStatus = :failedOrderStatus')
                         ->andWhere('o.checkoutType = :checkoutType')
                         ->andWhere('o.dateAdded >= :dateFrom')
                         ->andWhere('o.dateAdded <= :dateTo')
                         ->setParameter('pesopay', PaymentMethod::PAYMENT_METHOD_PESOPAY)
                         ->setParameter('failedOrderStatus', OrderStatus::PAYMENT_FAILED)
                         ->setParameter('checkoutType', $checkoutType)
                         ->setParameter('dateFrom', $dateFrom)
                         ->setParameter('dateTo', $dateTo);
                                
            $failedCount = $queryBuilder->getQuery()
                                        ->getSingleScalarResult();            
            $data[$checkoutTypeString.' Transactions']['Failed Credit-Card Transaction'] = $failedCount;

            
            /**
             * Rejected for Fraud
             */
            $queryBuilder = $em->createQueryBuilder();
            $queryBuilder->select("COUNT(o.orderId) as rejectedForFraud")
                         ->from("YilinkerCoreBundle:UserOrder", "o")
                         ->andWhere('o.orderStatus = :rejectedForFraud')
                         ->andWhere('o.checkoutType = :checkoutType')
                         ->andWhere('o.dateAdded >= :dateFrom')
                         ->andWhere('o.dateAdded <= :dateTo')
                         ->setParameter('rejectedForFraud', OrderStatus::ORDER_REJECTED_FOR_FRAUD)
                         ->setParameter('checkoutType', $checkoutType)
                         ->setParameter('dateFrom', $dateFrom)
                         ->setParameter('dateTo', $dateTo);
                
            $fraudCount = $queryBuilder->getQuery()
                                        ->getSingleScalarResult();            
            $data[$checkoutTypeString.' Transactions']['Rejected for Fraud Transaction'] = $fraudCount;
        }
        
        /**
         * First order above 500 in mobile
         */
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select(array(
            "o.invoiceNumber",
            "o.totalPrice",
            "COUNT(o2.orderId) as HIDDEN otherOrder",
        ))
                     ->from("YilinkerCoreBundle:UserOrder", "o")
                     ->leftJoin("YilinkerCoreBundle:UserOrder", "o2", "WITH", "o.buyer = o2.buyer AND o2.orderStatus IN (:orderStatuses)")
                     ->innerJoin("YilinkerCoreBundle:User", "u", "WITH", "o.buyer = u AND u.userType = :buyer")
                     ->andWhere('o.dateAdded >= :dateFrom')
                     ->andWhere('o.dateAdded <= :dateTo')
                     ->andWhere('o.orderStatus IN (:orderStatuses)')
                     ->andWhere('o.totalPrice > 500')
                     ->andWhere('o.checkoutType = :checkoutType')
                     ->having('otherOrder = 1')
                     ->groupBy("o.orderId")
                     ->setParameter('orderStatuses', $validOrderStatus)
                     ->setParameter('dateFrom', $dateFrom)
                     ->setParameter('dateTo', $dateTo)
                     ->setParameter('buyer', User::USER_TYPE_BUYER)
                     ->setParameter('checkoutType', UserOrder::CHECKOUT_TYPE_MOBILE);

        $result = $queryBuilder->getQuery()
                               ->getResult();
        $invoices = array();
        foreach($result as $order){
            $invoices[$order['invoiceNumber']] = number_format($order['totalPrice'], 2, '.', ',');
        }

        if(count($invoices) > 0){
            $data['Invoice Numbers of First Mobile Purchases Above PHP500:'] = $invoices;
        }
        else{
            $data['Invoice Numbers of First Mobile Purchases Above PHP500:'] = "N/A";
        }

        $this->createExcelAndEmail($data, $emailRecipients);

    }
     

    private function createExcelAndEmail($iterableData, $emailRecipients)
    {        
        $dateNow = Carbon::now();
        $container = $this->getContainer();       
        $ccDeveloper = $container->getParameter('reports_dev_email');
        $phpExcelObject = $container->get('phpexcel')
                                    ->createPHPExcelObject();
        $writer = $container->get('phpexcel')
                            ->createWriter($phpExcelObject, 'Excel5');
        try{
            $path = $container->get('kernel')->locateResource('@YilinkerCoreBundle/Resources/reports/marketing');
        }
        catch(\Exception $e){}

        $title = "marketing-report-".time();
        $phpExcelObject->getProperties()
                       ->setSubject("Marketing Report")
                       ->setDescription("Marketing Report")
                       ->setTitle($title);

        $rowCounter = "3";
        $phpExcelObject->setActiveSheetIndex(0)
                       ->setCellValue('A1', "Report generated on: ")
                       ->setCellValue('B1', $dateNow->format('Y-m-d H:i:s'));

        foreach($iterableData as $key => $dataRow){
            $phpExcelObject->setActiveSheetIndex(0)
                           ->setCellValue('A'.$rowCounter, $key)
                           ->getStyle('A'.$rowCounter)->getFont()->setBold(true);

            if(is_array($dataRow)){
                foreach($dataRow as $innerKey => $innerRow){
                    $rowCounter++;
                    $phpExcelObject->setActiveSheetIndex(0)
                                   ->setCellValue('A'.$rowCounter, $innerKey)
                                   ->setCellValue('B'.$rowCounter, $innerRow)
                                   ->getStyle('A'.$rowCounter)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                }
            }
            else{
                $phpExcelObject->setActiveSheetIndex(0)
                               ->setCellValue('B'.$rowCounter, $dataRow);
            }
            $rowCounter++;
        }

        foreach (range('A', $phpExcelObject->getActiveSheet()->getHighestDataColumn()) as $col) {
            $phpExcelObject->getActiveSheet()
                           ->getColumnDimension($col)
                           ->setAutoSize(true);
        }

        $filename = $path.DIRECTORY_SEPARATOR.$title.".xls";
        $writer->save($filename);
        echo "File generated: ".$filename."\n";

        if(count($emailRecipients) > 0){
            $mailer = $container->get('mailer');
            $mailerEmail = $container->getParameter('mailer_user');
            $message = Swift_Message::newInstance();
            
            $message->setSubject("Marketing Report  from ".$iterableData['Date From']." to ".$iterableData['Date To'])
                    ->setFrom($mailerEmail)
                    ->addCc($ccDeveloper)
                    ->setTo($emailRecipients)
                    ->attach(Swift_Attachment::fromPath($filename));
            $mailer->send($message);
            echo "Email sent to ".implode(",",$emailRecipients)."\n";
        }          
    }

}
