<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Exception;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Services\Earner\Earnings;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\Contact;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Event\PreFlushEventArgs;

class OrderProductListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    private function syncProductStatuses($orderProduct, $args)
    {
        $em = $args->getEntityManager();
        $userOrder = $orderProduct->getOrder();
        if ($userOrder && $userOrder->getOrderStatus()) {
            if ($userOrder->getOrderStatus()->getOrderStatusId() == OrderStatus::PAYMENT_CONFIRMED) {
                $productStatus = $orderProduct->getOrderProductStatus();
                $orderStatus = $productStatus ? $productStatus->getOrderProductStatusId(): 0;
                if($orderStatus != OrderProductStatus::PAYMENT_CONFIRMED) {
                    $orderProductStatus = $em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::PAYMENT_CONFIRMED);
                    $orderProduct->setOrderProductStatus($orderProductStatus);
                }
            }
            elseif ($userOrder->getOrderStatus()->getOrderStatusId() == OrderStatus::COD_WAITING_FOR_PAYMENT) {
                $productStatus = $orderProduct->getOrderProductStatus();
                $orderStatus = $productStatus ? $productStatus->getOrderProductStatusId(): 0;
                if ($orderStatus != OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED) {
                    $orderProductStatus = $em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED);
                    $orderProduct->setOrderProductStatus($orderProductStatus);
                }
            }
        }
    }

    private function recordHistory($orderProduct, $args, $new = false)
    {
        $em = $args->getEntityManager();
        $customEm = $this->container->get('doctrine')->getManager('custom');
        $customEm->clear();
        $uow = $em->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($orderProduct);

        $orderProductStatus = null;
        if ($new && $orderProduct->getOrderProductStatus()) {
            $orderProductStatus = $orderProduct->getOrderProductStatus();
        }
        elseif (array_key_exists('orderProductStatus', $changes)) {
            $beforeOrderProductStatus = array_shift($changes['orderProductStatus']);
            $afterOrderProductStatus = array_shift($changes['orderProductStatus']);
            $beforeOrderProductStatusId = $beforeOrderProductStatus ? $beforeOrderProductStatus->getOrderProductStatusId(): null;
            $isValidChange = $afterOrderProductStatus && 
                             ($beforeOrderProductStatusId != 
                             $afterOrderProductStatus->getOrderProductStatusId());
            if ($isValidChange) {
                $orderProductStatus = $afterOrderProductStatus;
            }
        }

        if ($orderProductStatus) {
            $orderProductStatusProxy = $customEm->getReference('YilinkerCoreBundle:OrderProductStatus', $orderProductStatus->getOrderProductStatusId());
            $orderProductProxy = $customEm->getReference('YilinkerCoreBundle:OrderProduct', $orderProduct->getOrderProductId());
            
            $orderProductHistory = new OrderProductHistory;
            $orderProductHistory->setOrderProductStatus($orderProductStatusProxy);
            $orderProductHistory->setOrderProduct($orderProductProxy);
            $customEm->persist($orderProductHistory);
            $customEm->flush();
        }
    }

    private function addBuyerToContacts($orderProduct, $args)
    {
        $em = $this->container->get('doctrine')->getManager('custom');
        $em->clear();
        $contactRepository = $em->getRepository("YilinkerCoreBundle:Contact");

        $seller = $orderProduct->getSeller();

        if(!is_null($seller->getStore()) && ($seller->getStore()->getStoreType() == Store::STORE_TYPE_MERCHANT)){
            $requestor = $em->getReference('YilinkerCoreBundle:User', $seller->getUserId());
            $requestee = $em->getReference('YilinkerCoreBundle:User', $orderProduct->getOrder()->getBuyer()->getUserId());

            $inContact = $contactRepository->getUserContact($requestor, $requestee);

            if(is_null($inContact)){
                $contact = new Contact();
                $contact->setRequestor($requestor)
                        ->setRequestee($requestee)
                        ->setDateAdded(Carbon::now());

                $em->persist($contact);
                $em->flush();

                try{
                    $predisService = $this->container->get("yilinker_core.service.predis.predis_service");
                    $predisService->publishContact($contact);
                }
                catch(Exception $e){
                    /**
                     * Try catch statement just in case the redis server is unavailable
                     */
                }
            }
        }
    }
    
    /**
     * Update the order status when the order product status has been updated
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     * @param Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    private function updateOrderStatus($orderProduct, $args)
    {
        $entityManager = $this->container->get('doctrine')->getManager('custom');
        $entityManager->clear();
        $completedOrderProductStatuses = $this->container->get('yilinker_core.service.transaction')
                                              ->getOrderProductStatusesCompleted();
        $cancellationOrderProductStatuses = array(
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED,
            OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED,
            OrderProductStatus::STATUS_CANCELED_BY_ADMIN,
        );
        $refundOrderProducStatuses = array(
            OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED,
            OrderProductStatus::STATUS_ITEM_REFUND_BOOKED_FOR_PICKUP,
            OrderProductStatus::STATUS_REFUNDED_ITEM_RECEIVED,
            OrderProductStatus::STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED,
            OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_THE_SPOT,
            OrderProductStatus::STATUS_REFUND_REASON_DENIED_ON_INSPECTION,
        );
        $replacementOrderProducStatuses = array(
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED,
            OrderProductStatus::STATUS_ITEM_RETURN_BOOKED_FOR_PICKUP,
            OrderProductStatus::STATUS_RETURNED_ITEM_RECEIVED,
            OrderProductStatus::STATUS_REPLACEMENT_PRODUCT_INSPECTION_APPROVED,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT,
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_INSPECTION,
        );

        /**
         * Obtain entity from custom entity manager
         */
        $orderProduct = $entityManager->find('YilinkerCoreBundle:OrderProduct', $orderProduct->getOrderProductId());
       
        $order = $orderProduct->getOrder();
        $orderProducts = $orderProduct->getOrder()->getOrderProducts();

        $completedOrderProducts = array();
        $receivedOrderProducts = array();
        $pickedupOrderProducts = array();
        $cancelledOrderProducts = array();
        $refundedOrderProducts = array();
        $replacedOrderProducts = array();

        foreach($orderProducts as $orderProduct){
            if($orderProduct->getOrderProductStatus()){
                $orderProductStatus = $orderProduct->getOrderProductStatus()->getOrderProductStatusId();
                if(in_array($orderProductStatus, $completedOrderProductStatuses)){
                    $completedOrderProducts[] = $orderProduct->getOrderProductId();
                }
                else if($orderProductStatus === OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER){
                    $receivedOrderProducts[] = $orderProduct->getOrderProductId();
                }
                else if($orderProductStatus === OrderProductStatus::STATUS_READY_FOR_PICKUP){
                    $pickedupOrderProducts[] = $orderProduct->getOrderProductId();
                }
                else if(in_array($orderProductStatus, $cancellationOrderProductStatuses)){
                    $cancelledOrderProducts[] = $orderProduct->getOrderProductId();
                }
                else if(in_array($orderProductStatus, $refundOrderProducStatuses)){
                    $refundedOrderProducts[] = $orderProduct->getOrderProductId();
                }
                else if(in_array($orderProductStatus, $replacementOrderProducStatuses)){
                    $replacedOrderProducts[] = $orderProduct->getOrderProductId();
                }
            }
        }

        if(count($completedOrderProducts) === count($orderProducts)){
            $completeStatus = $entityManager->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_STATUS_COMPLETED);
            $order->setOrderStatus($completeStatus);
            $entityManager->flush();
        }
        else if(count($receivedOrderProducts) === count($orderProducts)){
            $deliveredStatus = $entityManager->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_DELIVERED);
            $order->setOrderStatus($deliveredStatus);
            $entityManager->flush();
        }
        else if(count($pickedupOrderProducts) === count($orderProducts)){
            $pickedupStatus = $entityManager->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_FOR_PICKUP);
            $order->setOrderStatus($pickedupStatus);
            $entityManager->flush();
        }
        else if(count($cancelledOrderProducts) === count($orderProducts)){
            $cancelledStatus = $entityManager->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_FOR_CANCELLATION);
            $order->setOrderStatus($cancelledStatus);
            $entityManager->flush();
        }
        else if(count($refundedOrderProducts) === count($orderProducts)){
            $refundStatus = $entityManager->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_FOR_REFUND);
            $order->setOrderStatus($refundStatus);
            $entityManager->flush();
        }
        else if(count($replacedOrderProducts) === count($orderProducts)){
            $replaceStatus = $entityManager->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::ORDER_FOR_REPLACEMENT);
            $order->setOrderStatus($replaceStatus);
            $entityManager->flush();
        }

    }

    /**
     * Set the full image path
     */
    public function setFullImagePath($orderProduct, $args)
    {
        $twigExtension = $this->container->get('yilinker_core_bundle.twig.custom_extension');

        $productImage = $orderProduct->getImage();
        $imagePath = $productImage ? $productImage->getImageLocation() : null;
        
        $fullImagePath = $twigExtension->assetHelper($imagePath, 'product');
        $orderProduct->setFullImagePath($fullImagePath);
    }

    /**
     * Set if the item has been received
     */
    public function setHasBeenReceived($orderProduct, $args)
    {
        $entityManager = $args->getEntityManager();

        
        $itemRecevedStatus = $entityManager->getReference(
            'YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER
        );
        
        $criteria = Criteria::create()
                           ->andWhere(Criteria::expr()->eq("orderProductStatus", $itemRecevedStatus));

        $hasBeenReceived = count($orderProduct->getOrderProductHistories()->matching($criteria)) > 0;

        $orderProduct->setHasBeenReceived($hasBeenReceived);
    }

    public function decreaseProductQuantity($orderProduct, $args)
    {
        $productUnitRef = $orderProduct->getProductUnitReference();
        if ($productUnitRef) {
            $orderedQuantity = $orderProduct->getQuantity();
            $orderedQuantity = $orderedQuantity ? $orderedQuantity: 0;

            $supplyQuantity = $productUnitRef->getQuantity();
            $supplyQuantity = $supplyQuantity ? $supplyQuantity: 0;
            $supplyQuantity -= $orderedQuantity;

            $productUnitRef->setQuantity($supplyQuantity);
            $orderProduct->setReturnableQuantity($orderedQuantity);
            $product = $productUnitRef->getProduct();
            
            /**
             * If the quantity of all the units of the product is 0, 
             * set dateLastEmptied to the current date
             */
            $totalInventory = $product->getInventory();
            if($totalInventory < 1){
                $now = Carbon::now();
                $product->setDateLastEmptied($now);               
            }
        }
    }

    public function increaseProductQuantity($orderProduct, $args)
    {
        $em = $args->getEntityManager();
        $tbOrderProduct = $em->getRepository('YilinkerCoreBundle:OrderProduct');
        $tbOrderProduct->increaseProductQuantity($orderProduct);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $orderProduct = $args->getEntity();
        if ($orderProduct instanceof OrderProduct) {
            $this->syncProductStatuses($orderProduct, $args);
            $this->decreaseProductQuantity($orderProduct, $args);
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $orderProduct = $args->getEntity();
        if ($orderProduct instanceof OrderProduct) {
            try {
                $orderProduct->recalculateNet();
                $this->recordHistory($orderProduct, $args, true);
                $this->addBuyerToContacts($orderProduct, $args);
                $this->earnAffiliate($orderProduct, $args);
            } catch (\Exception $e) {
                $logger = $this->container->get('yilinker_core.logger');
                $logger->getLogger()->err($e->getMessage());

                return false;
            }
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $orderProduct = $args->getEntity();
        if ($orderProduct instanceof OrderProduct) {
            $orderProductStatus = $orderProduct->getOrderProductStatus();
            $earningTransactions = $orderProduct->getEarningTransactions();
            if ($orderProductStatus && $earningTransactions) {
                $orderProductStatusId = $orderProductStatus->getOrderProductStatusId();
                $tentativeToInvalid = array(
                    OrderProductStatus::STATUS_CANCELED_BY_ADMIN,
                    OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED,
                    OrderProductStatus::STATUS_BUYER_REFUND_RELEASED,
                    OrderProductStatus::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY
                );
                if (in_array($orderProductStatusId, $tentativeToInvalid)) {
                    foreach ($earningTransactions as $earningTransaction) {
                        $earning = $earningTransaction->getEarning();
                        $earningStatus = $earning->getStatus();
                        if ($earningStatus == Earning::TENTATIVE) {
                            $earning->setStatus(Earning::INVALID);
                        }
                    }
                }
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $orderProduct = $args->getEntity();
        if ($orderProduct instanceof OrderProduct) {
            $this->orderModified($orderProduct, $args);
            $this->recordHistory($orderProduct, $args);
            $this->updateOrderStatus($orderProduct, $args);
            $this->increaseProductQuantity($orderProduct, $args);
            $this->earnAffiliate($orderProduct, $args);
        }
    }

    public function orderModified($orderProduct, $args)
    {
        $customEm = $this->container->get('doctrine')->getManager('custom');
        $customEm->clear();

        $orderId = $orderProduct->getOrder()->getOrderId();
        $tbUserOrder = $customEm->getRepository('YilinkerCoreBundle:UserOrder');
        $order = $tbUserOrder->find($orderId);
        $order->setLastDateModified(Carbon::now());
        $customEm->flush();
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $orderProduct = $args->getEntity();
        if ($orderProduct instanceof OrderProduct) {
            $this->setFullImagePath($orderProduct, $args);
            $this->setHasBeenReceived($orderProduct, $args);
        }
    }
   
    private function earnAffiliate(OrderProduct $orderProduct, $args)
    {
        $customEm = $this->container->get('doctrine')->getManager('custom');
        $customEm->clear();
        $earner = $this->container->get('yilinker_core.service.earner');
        $orderProductNet = $orderProduct->getNet();
        $orderProduct = $customEm->find('YilinkerCoreBundle:OrderProduct', $orderProduct->getOrderProductId());

        $earner->get($orderProduct)
               ->addParameter(array('net' => $orderProductNet))
               ->setEntityManager($customEm)
               ->earn();

        $customEm->flush();
    }
}
