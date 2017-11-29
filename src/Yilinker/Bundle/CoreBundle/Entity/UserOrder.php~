<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * UserOrder
 */
class UserOrder
{

    const ORDER_STATUS_WAITING_FOR_PAYMENT = 1;

    const ORDER_STATUS_PAYMENT_CONFIRMED = 2;
    
    const ORDER_STATUS_COMPLETED = 3;
    
    const ORDER_STATUS_ORDER_REJECTED_FOR_FRAUD = 4;

    const ORDER_STATUS_PAYMENT_FAILED = 5;

    const ORDER_STATUS_DELIVERED = 6;

    const ORDER_STATUS_FOR_PICKUP = 7;

    const ORDER_STATUS_FOR_CANCELLATION = 8;
    
    const ORDER_STATUS_FOR_REFUND = 9;

    const ORDER_STATUS_FOR_REPLACEMENT = 10;

    const ORDER_STATUS_COD_WAITING_FOR_PAYMENT = 11;

    const FIRST_TIME_BUYER_FLAG_AMOUNT = "15000";
    
    const FIRST_TIME_BUYER_AND_MORE_LIMIT = 1;

    const PREVIOUS_ORDER_HAS_CANCEL_BEFORE_DELIVERY = 2;

    const CANCEL_FREQUENCY_GREATER_THAN_50_PERCENT = 3;

    const FIRST_TIME_BUYER_AND_USING_CREDIT_CARD = 4;

    const PREVIOUS_ORDER_FLAGGED_REJECTED = 5;

    const CHECKOUT_TYPE_WEB = 0;

    const CHECKOUT_TYPE_MOBILE = 1;


    /**
     * @var integer
     */
    private $orderId;

    /**
     * @var string
     */
    private $invoiceNumber = '';

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $totalPrice;

    /**
     * @var string
     */
    private $net = 0;

    /**
     * @var string
     */
    private $paymentMethodCharge = 0.00;

    /**
     * @var string
     */
    private $paymentMethodStatus = '';

    /**
     * @var string
     */
    private $yilinkerCharge = 0.00;

    /**
     * @var string
     */
    private $handlingFee = 0.00;

    /**
     * @var string
     */
    private $ipAddress = '';

    /**
     * @var \DateTime
     */
    private $lastDateModified;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderProducts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderHistories;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderStatus
     */
    private $orderStatus;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $buyer;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PaymentMethod
     */
    private $paymentMethod;

    /**
     * @var string
     */
    private $address = '';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderFeedbacks;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged
     */
    private $userOrderFlagged;
    
    /**
     * @var string
     */
    private $consigneeFirstName = '';

    /**
     * @var string
     */
    private $consigneeLastName = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Location
     */
    private $consigneeLocation;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $packages;

    /**
     * @var string
     */
    private $consigneeLatitude = '';

    /**
     * @var string
     */
    private $consigneeLongitude = '';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderVouchers;
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderConsigneeAddress
     */
    private $orderConsigneeAddress;

    /**
     * @var string
     */
    private $consigneeName = '';

    /**
     * @var string
     */
    private $consigneeContactNumber;

    /**
     * @var integer
     */
    private $checkoutType = 0;

    /**
     * isNewAccount
     * @var boolean
     */
    private $isNewAccount = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orderProducts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->packages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderVouchers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->dateAdded = new \DateTime();
        $this->lastDateModified = new \DateTime();
    }

    /**
     * Get orderId
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set invoiceNumber
     *
     * @param string $invoiceNumber
     * @return UserOrder
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    /**
     * Get invoiceNumber
     *
     * @return string 
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserOrder
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set totalPrice
     *
     * @param string $totalPrice
     * @return UserOrder
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * Get totalPrice
     *
     * @return string 
     */
    public function getTotalPrice($seller = null)
    {
        if ($seller) {
            $orderProducts = $this->getOrderProducts($seller);
            return array_reduce($orderProducts->toArray(), function($total, $orderProduct) {
                return $total + $orderProduct->getTotalPrice();
            });
        }

        return $this->totalPrice;
    }

    /**
     * Set net
     *
     * @param string $net
     * @return UserOrder
     */
    public function setNet($net)
    {
        $this->net = $net;

        return $this;
    }

    /**
     * Get net
     *
     * @return string 
     */
    public function getNet()
    {
        return $this->net;
    }

    /**
     * Set paymentMethodCharge
     *
     * @param string $paymentMethodCharge
     * @return UserOrder
     */
    public function setPaymentMethodCharge($paymentMethodCharge)
    {
        $this->paymentMethodCharge = $paymentMethodCharge;

        return $this;
    }

    /**
     * Get paymentMethodCharge
     *
     * @return string 
     */
    public function getPaymentMethodCharge()
    {
        return $this->paymentMethodCharge;
    }

    /**
     * Set yilinkerCharge
     *
     * @param string $yilinkerCharge
     * @return UserOrder
     */
    public function setYilinkerCharge($yilinkerCharge)
    {
        $this->yilinkerCharge = $yilinkerCharge;

        return $this;
    }

    /**
     * Get yilinkerCharge
     *
     * @return string 
     */
    public function getYilinkerCharge()
    {
        return $this->yilinkerCharge;
    }

    /**
     * Set handlingFee
     *
     * @param string $handlingFee
     * @return UserOrder
     */
    public function setHandlingFee($handlingFee)
    {
        $this->handlingFee = $handlingFee;

        return $this;
    }

    /**
     * Get handlingFee
     *
     * @return string 
     */
    public function getHandlingFee($seller = null)
    {
        if ($seller) {
            $orderProducts = $this->getOrderProducts($seller);
            return array_reduce($orderProducts->toArray(), function($total, $orderProduct) {
                return $total + $orderProduct->getHandlingFee();
            });
        }

        return $this->handlingFee;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return UserOrder
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set lastDateModified
     *
     * @param \DateTime $lastDateModified
     * @return UserOrder
     */
    public function setLastDateModified($lastDateModified)
    {
        $this->lastDateModified = $lastDateModified;

        return $this;
    }

    /**
     * Get lastDateModified
     *
     * @return \DateTime 
     */
    public function getLastDateModified()
    {
        return $this->lastDateModified;
    }

    /**
     * Add orderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts
     * @return UserOrder
     */
    public function addOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts)
    {
        $this->orderProducts[] = $orderProducts;

        return $this;
    }

    /**
     * Remove orderProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts
     */
    public function removeOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProducts)
    {
        $this->orderProducts->removeElement($orderProducts);
    }

    /**
     * Get orderProducts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderProducts($seller = null)
    {
        $orderProducts = $this->orderProducts;
        if ($seller) {
            $criteria = Criteria::create()
                ->andWhere(Criteria::expr()->eq('seller', $seller))
            ;
            $orderProducts = $orderProducts->matching($criteria);
        }

        return $orderProducts;
    }

    /**
     * @return $data - array of products grouped by sellerd Id; 
     *                 key is sellerId and value is array of products
     */
    public function getOrderProductsBySellerId()
    {
        $data = array();
        $orderProducts = $this->getOrderProducts();
        foreach ($orderProducts as $orderProduct) {
            $seller = $orderProduct->getSeller();
            if ($seller) {
                $sellerId = $seller->getUserId();
                $data[$sellerId][] = $orderProduct;
            }
            else {
                $data[0][] = $orderProduct;
            }
        }

        return $data;
    }

    /**
     * Set orderStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderStatus $orderStatus
     * @return UserOrder
     */
    public function setOrderStatus(\Yilinker\Bundle\CoreBundle\Entity\OrderStatus $orderStatus = null)
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderStatus 
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    /**
     * Set buyer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $buyer
     * @return UserOrder
     */
    public function setBuyer(\Yilinker\Bundle\CoreBundle\Entity\User $buyer = null)
    {
        $this->buyer = $buyer;

        return $this;
    }

    /**
     * Get buyer
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getBuyer()
    {
        return $this->buyer;
    }

    /**
     * Set paymentMethod
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PaymentMethod $paymentMethod
     * @return UserOrder
     */
    public function setPaymentMethod(\Yilinker\Bundle\CoreBundle\Entity\PaymentMethod $paymentMethod = null)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PaymentMethod 
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Add orderHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories
     * @return UserOrder
     */
    public function addOrderHistory(\Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories)
    {
        $this->orderHistories[] = $orderHistories;

        return $this;
    }

    /**
     * Remove orderHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories
     */
    public function removeOrderHistory(\Yilinker\Bundle\CoreBundle\Entity\OrderHistory $orderHistories)
    {
        $this->orderHistories->removeElement($orderHistories);
    }

    /**
     * Get orderHistories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderHistories()
    {
        return $this->orderHistories;
    }

    /**
     * Gets the sum of all products
     */
    public function getProductQuantity()
    {
        $quantity = 0;
        foreach($this->getOrderProducts() as $orderProduct){
            $quantity += $orderProduct->getQuantity();
        }

        return $quantity;
    }

    /**
     * Retrieves the total shipping fee
     *
     * @return string
     */
    public function getTotalShippingFee()
    {
        $totalShippingFee = "0.0000";
        $orderProducts = $this->getOrderProducts();
        foreach($orderProducts as $orderProduct){
            $totalShippingFee = bcadd($totalShippingFee, $orderProduct->getHandlingFee(), 4);
        }

        return $totalShippingFee;
    }

    public function toArray($priceFormatted = false, $displayMessage = false)
    {
        $orderProducts = array();
        foreach ($this->getOrderProducts() as $orderProduct) {
            $orderProducts[] = $orderProduct->toArray($priceFormatted);
        }

        $data = array(
            'orderId'           => $this->getOrderId(),
            'invoiceNumber'     => $this->getInvoiceNumber(),
            'dateAdded'         => $this->getDateAdded(),
            'totalPrice'        => $priceFormatted ? number_format($this->getTotalPrice(), 2, '.', ',') : $this->getTotalPrice(),
            'net'               => $priceFormatted ? number_format($this->getNet(), 2, '.', ',') : $this->getNet(),
            'ipAddress'         => $this->getIpAddress(),
            'lastDateModified'  => $this->getLastDateModified(),
            'orderStatus'       => $this->getOrderStatus()->toArray(),
            'address'           => $this->getAddress(),
            'paymentMethod'     => $this->getPaymentMethod()->toArray(),
            'orderProducts'     => $orderProducts,
            'message'           => null
        );

        if($displayMessage){
            $data["message"] = $this->getIsNewAccount()? 
                "Account has been registered. A password has been sent to your mobile number. Kindly use it to log in your account." : 
                "Your transaction has been successfully recorded into your registered account.";
        }

        return $data;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return UserOrder
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Add orderFeedbacks
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedback $orderFeedbacks
     * @return UserOrder
     */
    public function addOrderFeedback(\Yilinker\Bundle\CoreBundle\Entity\UserFeedback $orderFeedbacks)
    {
        $this->orderFeedbacks[] = $orderFeedbacks;

        return $this;
    }

    /**
     * Remove orderFeedbacks
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedback $orderFeedbacks
     */
    public function removeOrderFeedback(\Yilinker\Bundle\CoreBundle\Entity\UserFeedback $orderFeedbacks)
    {
        $this->orderFeedbacks->removeElement($orderFeedbacks);
    }

    /**
     * Get orderFeedbacks
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderFeedbacks()
    {
        return $this->orderFeedbacks;
    }

    public function getOrderProductWithStatus($orderProductStatus, $limit = null)
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq('orderProductStatus', $orderProductStatus));
        if ($limit) {
            $criteria->setMaxResults($limit);
        }
        $orderProducts = $this->getOrderProducts()->matching($criteria);

        return $orderProducts;
    }

    /**
     * Set userOrderFlagged
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged $userOrderFlagged
     * @return UserOrder
     */
    public function setUserOrderFlagged(\Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged $userOrderFlagged = null)
    {
        $this->userOrderFlagged = $userOrderFlagged;

        return $this;
    }

    /**
     * Get userOrderFlagged
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserOrderFlagged 
     */
    public function getUserOrderFlagged()
    {
        return $this->userOrderFlagged;
    }

    /**
     * Get total quantity of products in the order
     *
     * @return int
     */
    public function getTotalProductQuantity($seller = null)
    {
        $quantity = 0;
        foreach($this->getOrderProducts($seller) as $orderProduct){
            $quantity += $orderProduct->getQuantity();
        }

        return $quantity;
    }
    
    /**
     * Get total unit price
     *
     * @return string
     */
    public function getTotalUnitPrice()
    {
        $totalUnitPrice = "0.0000";
        foreach($this->getOrderProducts() as $orderProduct){
            $totalUnitPrice = bcadd($orderProduct->getUnitPrice(), $totalUnitPrice, 4);
        }

        return $totalUnitPrice;
    }

    /**
     * Get sum of unit cost x quantity
     *
     * @return string
     */
    public function getSubtotal()
    {
        $subtotal = "0.0000";
        foreach($this->getOrderProducts() as $orderProduct){
            $subtotal = bcadd(bcmul($orderProduct->getUnitPrice(), $orderProduct->getQuantity(), 4), $subtotal, 4);
        }

        return $subtotal;
    }

    /**
     * Set consigneeFirstName
     *
     * @param string $consigneeFirstName
     * @return UserOrder
     */
    public function setConsigneeFirstName($consigneeFirstName)
    {
        $this->consigneeFirstName = is_null($consigneeFirstName) ? '': $consigneeFirstName;

        return $this;
    }

    /**
     * Get consigneeFirstName
     *
     * @return string 
     */
    public function getConsigneeFirstName()
    {
        return $this->consigneeFirstName;
    }

    /**
     * Set consigneeLastName
     *
     * @param string $consigneeLastName
     * @return UserOrder
     */
    public function setConsigneeLastName($consigneeLastName)
    {
        $this->consigneeLastName = is_null($consigneeLastName) ? '': $consigneeLastName;

        return $this;
    }

    /**
     * Get consigneeLastName
     *
     * @return string 
     */
    public function getConsigneeLastName()
    {
        return $this->consigneeLastName;
    }

    /**
     * Set consigneeLocation
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Location $consigneeLocation
     * @return UserOrder
     */
    public function setConsigneeLocation(\Yilinker\Bundle\CoreBundle\Entity\Location $consigneeLocation = null)
    {
        $this->consigneeLocation = $consigneeLocation;

        return $this;
    }

    /**
     * Get consigneeLocation
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Location 
     */
    public function getConsigneeLocation()
    {
        return $this->consigneeLocation;
    }

    public function __toString()
    {
        return $this->getInvoiceNumber();
    }

    /**
     * Add packages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Package $packages
     * @return UserOrder
     */
    public function addPackage(\Yilinker\Bundle\CoreBundle\Entity\Package $packages)
    {
        $this->packages[] = $packages;

        return $this;
    }

    /**
     * Remove packages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Package $packages
     */
    public function removePackage(\Yilinker\Bundle\CoreBundle\Entity\Package $packages)
    {
        $this->packages->removeElement($packages);
    }

    /**
     * Get packages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPackages()
    {
        return $this->packages;
    }

    public function getPackagesOfSeller($seller)
    {
        $packages = $this->getPackages();
        $filtered = array();
        foreach ($packages as $package) {
            $packageDetail = $package->getPackageDetails()->first();
            if ($packageDetail) {
                $orderProductSeller = $packageDetail->getOrderProduct()->getSeller();
                if ($seller->getUserId() == $orderProductSeller->getUserId()) {
                    $filtered[] = $package;
                }
            }
        }

        return $filtered;
    }

    /**
     * Retrieves collection of all order product statuses in the order
     * Removes duplicates
     *
     * @param User $seller
     * @return OrderProductStatus[]
     */
    public function getUniqueOrderProductStatuses($seller = null)
    {
        if($seller){
            $criteria = Criteria::create()->where(Criteria::expr()->eq('seller', $seller));
            $orderProducts = $this->getOrderProducts()->matching($criteria);
        }
        else{
            $orderProducts = $this->getOrderProducts();
        }
        $orderProductStatuses = array();
        foreach($orderProducts as $orderProduct){
            $orderProductStatus = $orderProduct->getOrderProductStatus();
            if($orderProductStatus && isset($orderProductStatuses[$orderProductStatus->getOrderProductStatusId()]) === false){
                $orderProductStatuses[$orderProductStatus->getOrderProductStatusId()] = $orderProductStatus;
            }
        }

        return $orderProductStatuses;
    }

    public function getResellerOrderProducts()
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->neq('manufacturerProductUnit', null)
        );
        $resellerOrderProducts = $this->getOrderProducts()->matching($criteria);

        return $resellerOrderProducts;
    }
    
    /**
     * Returns if the transaction is flagged
     *
     * @return boolean
     */
    public function getIsFlagged()
    {
        $orderFlag = $this->getUserOrderFlagged();
        return  $orderFlag !== null && $orderFlag->getStatus() !== UserOrderFlagged::APPROVE;
    }

    /**
     * Set paymentMethodStatus
     *
     * @param string $paymentMethodStatus
     * @return UserOrder
     */
    public function setPaymentMethodStatus($paymentMethodStatus)
    {
        $this->paymentMethodStatus = $paymentMethodStatus;

        return $this;
    }

    /**
     * Get paymentMethodStatus
     *
     * @return string 
     */
    public function getPaymentMethodStatus()
    {
        return $this->paymentMethodStatus;
    }

    public function activityLoggable()
    {
        $orderStatus = $this->getOrderStatus();
        if ($orderStatus) {
            $statusId = $orderStatus->getOrderStatusId();
            $checkoutStatus = array(
                self::ORDER_STATUS_PAYMENT_CONFIRMED,
                self::ORDER_STATUS_COD_WAITING_FOR_PAYMENT
            );

            return in_array($statusId, $checkoutStatus);
        }

        return false;
    }

    /**
     * Set consigneeLatitude
     *
     * @param string $consigneeLatitude
     * @return UserOrder
     */
    public function setConsigneeLatitude($consigneeLatitude)
    {
        $this->consigneeLatitude = $consigneeLatitude;

        return $this;
    }

    /**
     * Get consigneeLatitude
     *
     * @return string 
     */
    public function getConsigneeLatitude()
    {
        return $this->consigneeLatitude;
    }

    /**
     * Set consigneeLongitude
     *
     * @param string $consigneeLongitude
     * @return UserOrder
     */
    public function setConsigneeLongitude($consigneeLongitude)
    {
        $this->consigneeLongitude = $consigneeLongitude;

        return $this;
    }

    /**
     * Get consigneeLongitude
     *
     * @return string 
     */
    public function getConsigneeLongitude()
    {
        return $this->consigneeLongitude;
    }

    public function isRejectedForFraud()
    {
        $currentStatus = $this->getOrderStatus();
        
        return $currentStatus && $currentStatus->getOrderStatusId() == self::ORDER_STATUS_ORDER_REJECTED_FOR_FRAUD;
    }

    /**
     * Add orderVouchers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers
     * @return UserOrder
     */
    public function addOrderVoucher(\Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers)
    {
        $this->orderVouchers[] = $orderVouchers;

        return $this;
    }

    /**
     * Remove orderVouchers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers
     */
    public function removeOrderVoucher(\Yilinker\Bundle\CoreBundle\Entity\OrderVoucher $orderVouchers)
    {
        $this->orderVouchers->removeElement($orderVouchers);
    }

    /**
     * Get orderVouchers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderVouchers()
    {
        return $this->orderVouchers;
    }

    public function getVoucherCodes()
    {
        $orderVouchers = $this->getOrderVouchers();
        $codes = array();
        foreach ($orderVouchers as $orderVoucher) {
            $voucherCode = $orderVoucher->getVoucherCode();
            if ($voucherCode) {
                $codes[] = $voucherCode->getCode();
            }
        }

        return $codes;
    }

    public function getVoucherDeduction()
    {
        $orderVouchers = $this->getOrderVouchers();
        $deduction = 0;
        foreach ($orderVouchers as $orderVoucher) {
            $deduction += $orderVoucher->getValue();
        }

        return $deduction;
    }

    /**
     * Set orderConsigneeAddress
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderConsigneeAddress $orderConsigneeAddress
     * @return UserOrder
     */
    public function setOrderConsigneeAddress(\Yilinker\Bundle\CoreBundle\Entity\OrderConsigneeAddress $orderConsigneeAddress = null)
    {
        $this->orderConsigneeAddress = $orderConsigneeAddress;

        return $this;
    }

    /**
     * Get orderConsigneeAddress
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderConsigneeAddress 
     */
    public function getOrderConsigneeAddress()
    {
        return $this->orderConsigneeAddress;
    }

    /**
     * Set consigneeName
     *
     * @param string $consigneeName
     * @return UserOrder
     */
    public function setConsigneeName($consigneeName)
    {
        $this->consigneeName = $consigneeName;

        return $this;
    }

    /**
     * Get consigneeName
     *
     * @return string 
     */
    public function getConsigneeName()
    {
        return $this->consigneeName;
    }

    /**
     * Set consigneeContactNumber
     *
     * @param string $consigneeContactNumber
     * @return UserOrder
     */
    public function setConsigneeContactNumber($consigneeContactNumber)
    {
        $this->consigneeContactNumber = $consigneeContactNumber;

        return $this;
    }

    /**
     * Get consigneeContactNumber
     *
     * @return string 
     */
    public function getConsigneeContactNumber()
    {
        return $this->consigneeContactNumber;
    }

    /**
     * Set checkoutType
     *
     * @param integer $checkoutType
     * @return UserOrder
     */
    public function setCheckoutType($checkoutType)
    {
        $this->checkoutType = $checkoutType;

        return $this;
    }

    /**
     * Get checkoutType
     *
     * @return integer 
     */
    public function getCheckoutType()
    {
        return $this->checkoutType;
    }

    /**
     * Retrieve total based on order product
     * 
     * @return string
     */
    public function getOrderTotalBasedOnOrderProducts()
    {
        $total = "0.0000";
        foreach($this->getOrderProducts() as $orderProduct){
            $orderProductAmount = bcmul($orderProduct->getQuantity(), $orderProduct->getUnitPrice(), 8);            
            $total = bcadd($total, $orderProductAmount, 8);
        }

        return $total;
    }

    /**
     * @var string
     */
    private $additionalCost = '0';


    /**
     * Set additionalCost
     *
     * @param string $additionalCost
     * @return UserOrder
     */
    public function setAdditionalCost($additionalCost)
    {
        $this->additionalCost = $additionalCost;

        return $this;
    }

    /**
     * Get additionalCost
     *
     * @return string 
     */
    public function getAdditionalCost()
    {
        return $this->additionalCost;
    }

    public function setIsNewAccount($isNewAccount)
    {
        $this->isNewAccount = $isNewAccount;

        return $this;
    }

    public function getIsNewAccount()
    {
        return $this->isNewAccount;
    }

    public function getOriginalPrice()
    {
        $total = $this->totalPrice;
        foreach ($this->getOrderVouchers() as $orderVoucher) {
            $total = bcadd($total, $orderVoucher->getValue(), 4);
        }

        return $total;
    }

    /**
     * Returns the storenames associates with the order
     *
     * @param boolean $asString
     */
    public function getStoreNames($asString = false)
    {
        $storeNames = [];
        $orderProducts = $this->getOrderProducts();
        foreach($orderProducts as $orderProduct){
            $storeNames[] = $orderProduct->getSeller()->getStore()->getStoreName();
        }

        return $asString ? implode(",", $storeNames) : $storeNames;
    }
}
