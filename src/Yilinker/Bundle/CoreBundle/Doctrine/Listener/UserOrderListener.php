<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Services\Earner\Earnings;

use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderHistory;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Carbon\Carbon;

class UserOrderListener
{
    private $serviceContainer;
    private $sitePrefix;

    public function setServiceContainer($serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }

    public function setSitePrefix($sitePrefix)
    {
        $this->sitePrefix = $sitePrefix;
    }

    public function createInvoiceNumber($userOrder, $args)
    {
        $em = $args->getEntityManager();
        $date = date('Ymd');
        $buyer = $userOrder->getBuyer();
        $buyerId = $buyer ? $buyer->getUserId(): 0;

        $paymentMethod = $userOrder->getPaymentMethod();
        $paymentMethodId = $paymentMethod ? $paymentMethod->getPaymentMethodId(): 0;

        $ipAddressSum = 0;
        $ipAddress = $userOrder->getIpAddress();
        $ipAddressParts = explode('.', $ipAddress);
        foreach ($ipAddressParts as $ipAddressPart) {
            $ipAddressSum += intval($ipAddressPart);
        }
        $ipAddressSum = str_pad($ipAddressSum, 4, '0', STR_PAD_LEFT);
        $orderId = $userOrder->getOrderId();
        $invoiceNumber = $this->sitePrefix.$buyerId.$date.$paymentMethodId.$ipAddressSum.$orderId;

        $userOrder->setInvoiceNumber($invoiceNumber);
        $em->flush();
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $userOrder = $args->getEntity();
        if ($userOrder instanceof UserOrder) {
            $em = $args->getEntityManager();
            $paymentMethodId = $userOrder->getPaymentMethod() ? $userOrder->getPaymentMethod()->getPaymentMethodId(): 0;
            if (PaymentMethod::PAYMENT_METHOD_COD == $paymentMethodId) {
                $orderStatus = $em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::COD_WAITING_FOR_PAYMENT);
            }
            else {
                $orderStatus = $em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_WAITING);
            }
            $userOrder->setOrderStatus($orderStatus);

            $this->calculateNet($userOrder, $args);
        }
    }

    public function referrerEarn($userOrder, $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if ($userOrder instanceof UserOrder) {
            $earner = $this->serviceContainer->get('yilinker_core.service.earner');
            $earner->get($entity)
                   ->earn();

            $entityManager->flush();
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $userOrder = $args->getEntity();
        if ($userOrder instanceof UserOrder) {
            $this->recordHistory($userOrder, $args, true);
            $this->createInvoiceNumber($userOrder, $args);
            $this->referrerEarn($userOrder, $args);
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $userOrder = $args->getEntity();
        if ($userOrder instanceof UserOrder) {
            $this->calculateNet($userOrder, $args);
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $userOrder = $args->getEntity();
        if ($userOrder instanceof UserOrder) {
            $this->recordHistory($userOrder, $args);
        }
    }

    private function recordHistory($userOrder, $args, $new = false)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($userOrder);

        if ($new && $userOrder->getOrderStatus()) {
            $orderHistory = new OrderHistory;
            $orderHistory->setOrderStatus($userOrder->getOrderStatus());
            $orderHistory->setOrder($userOrder);

            $em->persist($orderHistory);
            $em->flush();
        }
        elseif (array_key_exists('orderStatus', $changes)) {
            $beforeOrderStatus = array_shift($changes['orderStatus']);
            $afterOrderStatus = array_shift($changes['orderStatus']);
            if ($beforeOrderStatus && $afterOrderStatus) {
                if ($beforeOrderStatus->getOrderStatusId() != $afterOrderStatus->getOrderStatusId()) {
                    $conn = $em->getConnection();
                    $now = Carbon::now();
                    $conn->insert('OrderHistory', array(
                        'order_id' => $userOrder->getOrderId(),
                        'order_status_id' => $afterOrderStatus->getOrderStatusId(),
                        'date_added' => $now
                    ));

                    $this->syncProductStatuses($userOrder, $args);
                }
            }

        }
    }

    private function syncProductStatuses($userOrder, $args)
    {
        $em = $args->getEntityManager();
        $conn = $em->getConnection();
        if ($userOrder->getOrderStatus()->getOrderStatusId() == OrderStatus::PAYMENT_CONFIRMED) {
            $change = array('order_product_status_id' => OrderProductStatus::PAYMENT_CONFIRMED);
            $criteria = array('order_id' => $userOrder->getOrderId());
            $conn->update('OrderProduct', $change, $criteria);
        }
    }

    private function calculateNet($userOrder, $args)
    {
        $orderProductTotal = $userOrder->getOrderTotalBasedOnOrderProducts();

        $runningNet = bcsub($orderProductTotal, $userOrder->getHandlingFee(), 8);
        $runningNet = bcsub($runningNet, $userOrder->getYilinkerCharge(), 8);
        $runningNet = bcsub($runningNet, $userOrder->getAdditionalCost(), 8);
        $net = $runningNet;

        if (bccomp($net, $userOrder->getNet()) !== 0) {
            $userOrder->setNet($net);
        }
    }
}