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
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
class CompleteEarningsOtherCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yilinker:earnings:complete-creditcard')
            ->setDescription('Complete Earnings from Transaction if n days have passed after the user Recieved the item')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();

        $conn = $em->getConnection();

        $sql = "SELECT uo.order_id, uo.invoice_number, op.order_product_id
            FROM OrderProduct op
            LEFT JOIN UserOrder uo on op.order_id = uo.order_id
            LEFT JOIN EarningTransaction et on uo.order_id = et.order_id
            WHERE uo.order_status_id = :order_delivered 
                AND et.order_id IS NULL
            ORDER BY uo.date_added DESC
        ";

        $orderProducts = $conn->fetchAll($sql, array('order_delivered' => OrderStatus::ORDER_DELIVERED));

        foreach ($orderProducts as $orderProduct) {
            var_dump($orderProduct);
            $orderProductObj = $em->find('YilinkerCoreBundle:OrderProduct',$orderProduct['order_product_id']);
            
            $earner = $container->get('yilinker_core.service.earner');
            $orderProductNet = $orderProductObj->getNet();

            $earner->get($orderProductObj)
               ->addParameter(array('net' => $orderProductNet))
               ->setEntityManager($em)
               ->earn();

                
            $userOrderEarner = $container->get('yilinker_core.service.earner');
            $userOrderEarner->get($orderProductObj->getOrder())
                   ->earn();



            $em->flush();
        }

        
    }
}