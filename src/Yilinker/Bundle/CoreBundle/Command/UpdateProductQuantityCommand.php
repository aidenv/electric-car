<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;

class UpdateProductQuantityCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('product:update_quantity')
             ->setDescription('Updates the product quantity');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();
        $orderPaymentFailed = $em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_FAILED);
        $tbOrderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct');

        $page = 1;
        do {
            $orderProducts = $tbOrderProduct->inactiveOrderProducts($page++);
            if ($orderProducts) {
                foreach ($orderProducts as $orderProduct) {
                    $quantity = $orderProduct->getReturnableQuantity();
                    $increased = $tbOrderProduct->increaseProductQuantity($orderProduct, true);
                    if ($increased) {
                        $output->writeln('Ordered Product #'.$orderProduct->getOrderProductId().' returned x'.$quantity.' stock.');
                    }
                    $em->transactional(function($em) use ($orderProduct, $orderPaymentFailed, $output) {
                        $order = $orderProduct->getOrder();
                        $orderStatus = $order->getOrderStatus();
                        if ($orderStatus && $orderStatus->getOrderStatusId() == OrderStatus::PAYMENT_WAITING) {
                            $order->setOrderStatus($orderPaymentFailed);
                            $output->writeln('Order #'.$order->getOrderId().' set status to payment failed.');
                        }
                    });
                }
            }
        } while ($orderProducts);
    }
}