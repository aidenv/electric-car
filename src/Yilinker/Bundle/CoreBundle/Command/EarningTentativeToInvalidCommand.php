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

class EarningTentativeToInvalidCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('yilinker:earnings:tentative-to-invalid')
            ->setDescription('Convert Un Process Transaction Of Tentative Earnings to Invalid')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();

        $conn = $em->getConnection();

        $sql = "SELECT uo.order_id, uo.invoice_number, op.order_product_id, e.earning_id
                FROM OrderProduct op
                LEFT JOIN UserOrder uo on op.order_id = uo.order_id
                LEFT JOIN EarningTransaction et on uo.order_id = et.order_id
                INNER JOIN Earning e on et.earning_id = e.earning_id
                WHERE e.status = :earning_tentative
                AND uo.order_status_id IN (4,5,8,9)
                ORDER BY uo.date_added DESC
        ";

        $orderProducts = $conn->fetchAll($sql, array('earning_tentative' => Earning::TENTATIVE));

        foreach ($orderProducts as $orderProduct) {
            var_dump($orderProduct);

            $conn->update("Earning",
                array('status' => Earning::INVALID),
                array('earning_id' => $orderProduct['earning_id'])
            );
        
        }

        
        echo "Complete";        
    }
}