<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail;
use Yilinker\Bundle\CoreBundle\Services\Logistics\Yilinker\Express;

/**
 * OrderProduct
 */
class OrderProduct
{
    const STATUS_PAYMENT_CONFIRMED = 1;
    
    const STATUS_READY_FOR_PICKUP = 2;

    const STATUS_PRODUCT_ON_DELIVERY = 3;

    const STATUS_ITEM_RECEIVED_BY_BUYER = 4;

    const STATUS_SELLER_PAYMENT_RELEASED = 5;

    const STATUS_CANCELED_REQUEST_BY_BUYER_BEFORE_DELIVERY = 6;

    const STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY = 7;

    const STATUS_BUYER_CANCELLATION_BEFORE_DELIVERY_APPROVED = 8;

    const STATUS_CANCELLATION_BEFORE_DELIVERY_DENIED = 9;

    const STATUS_ITEM_REFUND_REQUESTED = 10;

    const STATUS_ITEM_REFUND_BOOKED_FOR_PICKUP = 11;

    const STATUS_REFUNDED_ITEM_RECEIVED = 12;

    const STATUS_REFUNDED_PRODUCT_INSPECTION_APPROVED = 13;

    const STATUS_REFUND_REASON_DENIED_ON_THE_SPOT = 14;

    const STATUS_REFUND_REASON_DENIED_ON_INSPECTION = 15;

    const STATUS_ITEM_REPLACEMENT_REQUESTED = 16;

    const STATUS_ITEM_RETURN_BOOKED_FOR_PICKUP = 17;

    const STATUS_RETURNED_ITEM_RECEIVED = 18;

    const STATUS_REPLACEMENT_PRODUCT_INSPECTION_APPROVED = 19;

    const STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_THE_SPOT = 20;

    const STATUS_ITEM_REPLACEMENT_REASON_DENIED_ON_INSPECTION = 21;

    const STATUS_SELLER_PAYOUT_UN_HELD = 22;

    const STATUS_BUYER_REFUND_RELEASED = 23;

    const STATUS_COD_TRANSACTION_CONFIRMED = 24;

    const STATUS_CANCELED_BY_ADMIN = 25;

    const STATUS_DISPUTE_IN_PROCESS = 26;

    // WAYBILL REQUEST STATUSES

    const WAYBILL_REQUEST_STATUS_GENERATED = 1;

    const WAYBILL_REQUEST_STATUS_WAITING_FOR_RESPONSE = 2;

    const WAYBILL_REQUEST_STATUS_ERROR = 3;

    const WAYBILL_REQUEST_STATUS_READY_TO_REPROCESS = 4;

    const WAYBILL_REQUEST_STATUS_READY_TO_PROCESS = 5;

    private $boughtStatus = array(
        self::STATUS_PAYMENT_CONFIRMED,
        self::STATUS_COD_TRANSACTION_CONFIRMED
    );

    /**
     * @var integer
     */
    private $orderProductId;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var integer
     */
    private $returnableQuantity;

    /**
     * @var string
     */
    private $totalPrice;

    /**
     * @var string
     */
    private $unitPrice;

    /**
     * @var string
     */
    private $origPrice;

    /**
     * @var string
     */
    private $productName;

     /**
     * @var string
     */
    private $paymentMethodCharge = 0.00;

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
    private $shippingFee = 0;

    /**
     * @var \DateTime
     */
    private $lastDateModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserOrder
     */
    private $order;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus
     */
    private $orderProductStatus;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $attributes = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Brand
     */
    private $brand;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductImage
     */
    private $image;

    /**
     * @var string
     */
    private $sku = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $seller;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCondition
     */
    private $condition;

    /**
     * @var string
     */
    private $weight = '0';

    /**
     * @var string
     */
    private $length = '0';

    /**
     * @var string
     */
    private $width = '0';

    /**
     * @var string
     */
    private $height = '0';

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $shortDescription = '';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $packageDetails;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderProductHistories;

    /**
     * @var string
     */
    private $brandName = '';

    /**
     * The image full path
     *
     * @var string
     */
    private $fullImagePath;
    
    /**
     * @var string
     */
    private $manufacturerProductReference = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    private $manufacturerProductUnit;

    /**
     * Has the order product been received
     *
     * @var boolean
     */
    private $hasBeenReceived = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $orderProductCancellationDetails;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productReviews;

    /**
     * @var string
     */
    private $net = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $earningTransactions;
    
    /**
     * @var string
     */
    private $commission = "0";

    /**
     * @var boolean
     */
    private $isNotShippable = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse
     */
    private $userWarehouse;

    /**
     * @var \DateTime
     */
    private $dateWaybillRequested;

    public function __construct()
    {
        $this->dataAdded = new \DateTime;
        $this->lastDateModified = new \DateTime;
        $this->packageDetails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderProductHistories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get orderProductId
     *
     * @return integer 
     */
    public function getOrderProductId()
    {
        return $this->orderProductId;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return OrderProduct
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set totalPrice
     *
     * @param string $totalPrice
     * @return OrderProduct
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
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * Set unitPrice
     *
     * @param string $unitPrice
     * @return OrderProduct
     */
    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Get unitPrice
     *
     * @return string 
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * Set productName
     *
     * @param string $productName
     * @return OrderProduct
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;

        return $this;
    }

    /**
     * Get productName
     *
     * @return string 
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * Set handlingFee
     *
     * @param string $handlingFee
     * @return OrderProduct
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
    public function getHandlingFee()
    {
        return $this->handlingFee;
    }

    /**
     * Set shippingFee
     *
     * @param string $shippingFee
     * @return OrderProduct
     */
    public function setShippingFee($shippingFee)
    {
        $this->shippingFee = $shippingFee;

        return $this;
    }

    /**
     * Get shippingFee
     *
     * @return string 
     */
    public function getShippingFee()
    {
        return $this->shippingFee;
    }

    /**
     * Set lastDateModified
     *
     * @param \DateTime $lastDateModified
     * @return OrderProduct
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
     * Set order
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserOrder $order
     * @return OrderProduct
     */
    public function setOrder(\Yilinker\Bundle\CoreBundle\Entity\UserOrder $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserOrder 
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set orderProductStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus $orderProductStatus
     * @return OrderProduct
     */
    public function setOrderProductStatus(\Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus $orderProductStatus = null)
    {
        $this->orderProductStatus = $orderProductStatus;

        return $this;
    }

    /**
     * Get orderProductStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus 
     */
    public function getOrderProductStatus()
    {
        return $this->orderProductStatus;
    }
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;


    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return OrderProduct
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return OrderProduct
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

    public function toArray($priceFormatted = false)
    {
        $orderProductStatus = array(
            "orderProductStatusId" => null,
            "name"                 => "Waiting for payment",
            "description"          => "Transaction is still unpaid"
        );
        if ($this->getOrderProductStatus()) {
            $orderProductStatus = $this->getOrderProductStatus()->toArray();
        }
        

        $productId = $this->getProduct() ? $this->getProduct()->getProductId(): 0;
        $image = $this->getImage() ? $this->getImage()->getImageLocation() : '';
        
        $data = array(
            'orderProductId'        => $this->getOrderProductId(),
            'productId'             => $productId,
            'quantity'              => (int) $this->getQuantity(),
            'unitPrice'             => $priceFormatted ? number_format($this->getUnitPrice(), 2, '.', ',') : $this->getUnitPrice(),
            'totalPrice'            => $priceFormatted ? number_format($this->getQuantifiedUnitPrice(), 2, '.', ',') : $this->getQuantifiedUnitPrice(),            
            'originalUnitPrice'     => $priceFormatted ? number_format($this->getOrigPrice(), 2, '.', ',') : $this->getOrigPrice(),
            'handlingFee'           => $priceFormatted ? number_format($this->getHandlingFee(), 2, '.', ',') : $this->getHandlingFee(),
            'productName'           => $this->getProductName(),
            'dateAdded'             => $this->getDateAdded(),
            'lastDateModified'      => $this->getLastDateModified(),
            'orderProductStatus'    => $orderProductStatus,
            'productImage'          => $image,
            'sku'                   => $this->getSku(),
            'attributes'            => $this->getAttributes(true),
            'discount'              => $this->getDiscount(),
            'width'                 => $this->getWidth(),
            'height'                => $this->getHeight(),
            'length'                => $this->getLength(),
            'weight'                => $this->getWeight(),
            'description'           => $this->getDescription(),
            'shortDescription'      => $this->getShortDescription(),
         );

        return $data;
    }
    
    /**
     * Set attributes
     *
     * @param string $attributes
     * @return OrderProduct
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Get attributes
     *
     * @return string 
     */
    public function getAttributes($toArray = false)
    {
        $data = $toArray ? json_decode($this->attributes, true): $this->attributes;

        return $data;
    }

    public function getAttributeDetails()
    {
        $attributes = $this->getAttributes(true);
        if (!$attributes) {
            return '';
        }
        $attributes = array_values($attributes);
        $attributes = implode(', ', $attributes);

        return $attributes;
    }

    /**
     * Set brand
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Brand $brand
     * @return OrderProduct
     */
    public function setBrand(\Yilinker\Bundle\CoreBundle\Entity\Brand $brand = null)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Brand 
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return OrderProduct
     */
    public function setProductCategory(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCategory 
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }

    /**
     * Set image
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductImage $image
     * @return OrderProduct
     */
    public function setImage(\Yilinker\Bundle\CoreBundle\Entity\ProductImage $image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductImage 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Get image location
     *
     * @return string
     */
    public function getImageLocation()
    {
        return $this->getImage() ? $this->getImage()->getImageLocation() : '';
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return OrderProduct
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get sku
     *
     * @return string 
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set condition
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCondition $condition
     * @return OrderProduct
     */
    public function setCondition(\Yilinker\Bundle\CoreBundle\Entity\ProductCondition $condition = null)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCondition 
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set seller
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $seller
     * @return OrderProduct
     */
    public function setSeller(\Yilinker\Bundle\CoreBundle\Entity\User $seller = null)
    {
        $this->seller = $seller;

        return $this;
    }

    /**
     * Get seller
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getSeller()
    {
        return $this->seller;
    }

    /**
     * Set origPrice
     *
     * @param string $origPrice
     * @return OrderProduct
     */
    public function setOrigPrice($origPrice)
    {
        $this->origPrice = $origPrice;

        return $this;
    }

    /**
     * Get origPrice
     *
     * @return string 
     */
    public function getOrigPrice()
    {
        return $this->origPrice;
    }

    public function getDiscount()
    {
        $discount = "0.00";
        if ($this->getUnitPrice() > 0 && $this->getOrigPrice() > 0) {
            $discount = bcmul(bcsub("1.0", bcdiv($this->getUnitPrice(), $this->getOrigPrice(), 4), 4), "100", 2);
        }

        return $discount;
    }

    public function getProductSlug()
    {
        $product = $this->getProduct();
        $slug = $product ? $product->getSlug(): '';

        return $slug;
    }

    /**
     * Set weight
     *
     * @param string $weight
     * @return OrderProduct
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return string 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set length
     *
     * @param string $length
     * @return OrderProduct
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return string 
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set width
     *
     * @param string $width
     * @return OrderProduct
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return string 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param string $height
     * @return OrderProduct
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return string 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return OrderProduct
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description ? $this->description : '';
    }

    /**
     * Set shortDescription
     *
     * @param string $shortDescription
     * @return OrderProduct
     */
    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string 
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Add packageDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails
     * @return OrderProduct
     */
    public function addPackageDetail(\Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails)
    {
        $this->packageDetails[] = $packageDetails;

        return $this;
    }

    /**
     * Remove packageDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails
     */
    public function removePackageDetail(\Yilinker\Bundle\CoreBundle\Entity\PackageDetail $packageDetails)
    {
        $this->packageDetails->removeElement($packageDetails);
    }

    /**
     * Get packageDetails
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPackageDetails()
    {
        return $this->packageDetails;
    }

    /**
     * Set paymentMethodCharge
     *
     * @param string $paymentMethodCharge
     * @return OrderProduct
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
     * @return OrderProduct
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
     * Set brandName
     *
     * @param string $brandName
     * @return OrderProduct
     */
    public function setBrandName($brandName)
    {
        $this->brandName = $brandName;

        return $this;
    }

    /**
     * Get brandName
     *
     * @return string 
     */
    public function getBrandName()
    {
        return $this->brandName;
    }

    /**
     * Add orderProductHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory $orderProductHistories
     * @return OrderProduct
     */
    public function addOrderProductHistory(\Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory $orderProductHistories)
    {
        $this->orderProductHistories[] = $orderProductHistories;

        return $this;
    }

    /**
     * Remove orderProductHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory $orderProductHistories
     */
    public function removeOrderProductHistory(\Yilinker\Bundle\CoreBundle\Entity\OrderProductHistory $orderProductHistories)
    {
        $this->orderProductHistories->removeElement($orderProductHistories);
    }

    /**
     * Get orderProductHistories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderProductHistories()
    {
        return $this->orderProductHistories;
    }

    /**
     * Get orderProductHistories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function hasStatus(array $statuses = array(), $dateStart, $dateEnd)
    {
        $gte = Criteria::expr()->gte("dateAdded", $dateStart);
        $lte = Criteria::expr()->lte("dateAdded", $dateEnd);
        $in = Criteria::expr()->in("orderProductStatus", $statuses);

        $criteria = Criteria::create()->andWhere($lte)->andWhere($gte)->andWhere($in);

        $histories = $this->getOrderProductHistories()->matching($criteria);

        if($histories->count()){
            return true;
        }

        return false;
    }

    /**
     * Gets the full image path
     *
     * @return string
     */
    public function getFullImagePath()
    {
        return $this->fullImagePath;
    }

    /**
     * Sets the full image path
     *
     * @param string $fullImagePath
     */
    public function setFullImagePath($fullImagePath)
    {
        $this->fullImagePath = $fullImagePath;
    }

    /**
     * Get if the order product has been received
     *
     * @param boolean $hasBeenReceived
     */
    public function setHasBeenReceived($hasBeenReceived)
    {
        $this->hasBeenReceived = $hasBeenReceived;

        return $this;
    }
    
    /**
     * Set that the order product been received
     *
     * @return boolean
     */
    public function getHasBeenReceived()
    {
        return $this->hasBeenReceived;
    }

    /**
     * Set manufacturerProductReference
     *
     * @param string $manufacturerProductReference
     * @return OrderProduct
     */
    public function setManufacturerProductReference($manufacturerProductReference)
    {
        $this->manufacturerProductReference = $manufacturerProductReference;

        return $this;
    }

    /**
     * Get manufacturerProductReference
     *
     * @return string 
     */
    public function getManufacturerProductReference()
    {
        return $this->manufacturerProductReference;
    }

    /**
     * Set manufacturerProductUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit
     * @return OrderProduct
     */
    public function setManufacturerProductUnit(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit = null)
    {
        $this->manufacturerProductUnit = $manufacturerProductUnit;

        return $this;
    }

    /**
     * Get manufacturerProductUnit
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit 
     */
    public function getManufacturerProductUnit()
    {
        return $this->manufacturerProductUnit;
    }

    /**
     * @return mixed    Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit, Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     *
     */
    public function getProductUnitReference()
    {
        $crit = Criteria::create()
            ->andWhere(Criteria::expr()->eq('sku', $this->getSku()))
            ->orderBy(array('productUnitId' => 'DESC'))
            ->setMaxResults(1)
        ;

        return $this
            ->getProduct()
            ->getUnits()
            ->matching($crit)
            ->first()
        ;
    }

    /**
     * Set returnableQuantity
     *
     * @param integer $returnableQuantity
     * @return OrderProduct
     */
    public function setReturnableQuantity($returnableQuantity)
    {
        $this->returnableQuantity = $returnableQuantity;

        return $this;
    }

    /**
     * Get returnableQuantity
     *
     * @return integer 
     */
    public function getReturnableQuantity()
    {
        return $this->returnableQuantity;
    }

    /**
     * Add orderProductCancellationDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails
     * @return OrderProduct
     */
    public function addOrderProductCancellationDetail(\Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails)
    {
        $this->orderProductCancellationDetails[] = $orderProductCancellationDetails;

        return $this;
    }

    /**
     * Remove orderProductCancellationDetails
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails
     */
    public function removeOrderProductCancellationDetail(\Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationDetail $orderProductCancellationDetails)
    {
        $this->orderProductCancellationDetails->removeElement($orderProductCancellationDetails);
    }

    /**
     * Get orderProductCancellationDetails
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderProductCancellationDetails()
    {
        return $this->orderProductCancellationDetails;
    }

    /**
     * Check if the the order product has been cancelled completely (approved)
     */
    public function getIsCancellationApproved()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq('status', OrderProductCancellationDetail::DETAIL_STATUS_APPROVED));

        $cancellationDetails = $this->getOrderProductCancellationDetails()->matching($criteria);
        
        return $cancellationDetails ? $cancellationDetails->count() > 0: false;
    }

    /**
     * Returns if the order product is still waiting for delivery
     *
     * @return boolean
     */
    public function isWaitingForDelivery()
    {
        $hasBeenReceived = $this->getHasBeenReceived();
        $isWaitingForDelivery = true;

        $deliverableStatuses = array(
            self::STATUS_PAYMENT_CONFIRMED,
            self::STATUS_READY_FOR_PICKUP,            
            self::STATUS_PRODUCT_ON_DELIVERY,
            self::STATUS_COD_TRANSACTION_CONFIRMED,
        );

        $orderProductStatusId = $this->getOrderProductStatus() ? $this->getOrderProductStatus()->getOrderProductStatusId() : null;
        if($hasBeenReceived || in_array($orderProductStatusId, $deliverableStatuses) !== true){
            $isWaitingForDelivery = false;
        }

        return $isWaitingForDelivery;
    }

    /**
     * Set net
     *
     * @param string $net
     * @return OrderProduct
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
     * Add productReviews
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductReview $productReviews
     * @return OrderProduct
     */
    public function addProductReview(\Yilinker\Bundle\CoreBundle\Entity\ProductReview $productReviews)
    {
        $this->productReviews[] = $productReviews;

        return $this;
    }

    /**
     * Remove productReviews
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductReview $productReviews
     */
    public function removeProductReview(\Yilinker\Bundle\CoreBundle\Entity\ProductReview $productReviews)
    {
        $this->productReviews->removeElement($productReviews);
    }

    /**
     * Get productReviews
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductReviews()
    {
        return $this->productReviews;
    }

    /**
     * Set commission
     *
     * @param string $commission
     * @return OrderProduct
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * Get commission
     *
     * @return string 
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * Retrieve the date of a particular order product history
     *
     * @return \DateTime
     */
    public function getHistoryDate(OrderProductStatus $orderProductStatus)
    {
        $criteria = Criteria::create()
                            ->andWhere(
                                Criteria::expr()->eq("orderProductStatus", $orderProductStatus)
                            );
        $history = $this->orderProductHistories->matching($criteria)->first();
        
        return $history ? $history->getDateAdded() : null;
    }

    /**
     * Add earningTransactions
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningTransaction $earningTransactions
     * @return OrderProduct
     */
    public function addEarningTransaction(\Yilinker\Bundle\CoreBundle\Entity\EarningTransaction $earningTransactions)
    {
        $this->earningTransactions[] = $earningTransactions;

        return $this;
    }

    /**
     * Remove earningTransactions
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\EarningTransaction $earningTransactions
     */
    public function removeEarningTransaction(\Yilinker\Bundle\CoreBundle\Entity\EarningTransaction $earningTransactions)
    {
        $this->earningTransactions->removeElement($earningTransactions);
    }

    /**
     * Get earningTransactions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEarningTransactions()
    {
        return $this->earningTransactions;
    }

    public function isBought()
    {
        $status = $this->getOrderProductStatus();
        $orderProductStatusId = $status ? $status->getOrderProductStatusId(): null;

        return in_array($orderProductStatusId, $this->boughtStatus);
    }

    public function itemReceivedByBuyer()    
    {
        $status = $this->getOrderProductStatus();
        $orderProductStatusId = $status ? $status->getOrderProductStatusId(): null;
        
        return $orderProductStatusId == self::STATUS_ITEM_RECEIVED_BY_BUYER;
    }
    /**
     * @var string
     */
    private $additionalCost = '0';


    /**
     * Set additionalCost
     *
     * @param string $additionalCost
     * @return OrderProduct
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

    public function recalculateNet()
    {
        $amount = $this->getQuantifiedUnitPrice();
        $net = bcsub(bcsub(bcsub($amount, $this->getHandlingFee(), 8), $this->getYilinkerCharge(), 8), $this->getAdditionalCost(), 8);

        $this->setNet($net);
    }

    /**
     * Get unit price multiplied by quantity
     *
     * @return string
     */
    public function getQuantifiedUnitPrice()
    {
        $amount = bcmul($this->getUnitPrice(), $this->getQuantity(), 8);

        return $amount;
    }

    /**
     * Set isNotShippable
     *
     * @param boolean $isNotShippable
     * @return OrderProduct
     */
    public function setIsNotShippable($isNotShippable)
    {
        $this->isNotShippable = $isNotShippable;

        return $this;
    }

    /**
     * Get isNotShippable
     *
     * @return boolean 
     */
    public function getIsNotShippable()
    {
        return $this->isNotShippable;
    }

    /**
     * Set userWarehouse
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $userWarehouse
     * @return OrderProduct
     */
    public function setUserWarehouse(\Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $userWarehouse = null)
    {
        $this->userWarehouse = $userWarehouse;

        return $this;
    }

    /**
     * Get userWarehouse
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse 
     */
    public function getUserWarehouse()
    {
        return $this->userWarehouse;
    }

    /**
     * Set dateWaybillRequested
     *
     * @param \DateTime $dateWaybillRequested
     * @return OrderProduct
     */
    public function setDateWaybillRequested($dateWaybillRequested)
    {
        $this->dateWaybillRequested = $dateWaybillRequested;

        return $this;
    }

    /**
     * Get dateWaybillRequested
     *
     * @return \DateTime 
     */
    public function getDateWaybillRequested()
    {
        return $this->dateWaybillRequested;
    }

    /**
     * Get waybill request status
     *
     * @return array|null
     */
    public function getWaybillRequestStatus ()
    {
        $hasPackageDetail = $this->getPackageDetails()->first() != false;
        $dateWaybillRequested = $this->getDateWaybillRequested();

          try {
            if (!is_null($dateWaybillRequested) && $hasPackageDetail) {
                $status = array(
                    'id'   => self::WAYBILL_REQUEST_STATUS_GENERATED,
                    'desc' => 'Generated'
                );
            }
            else if (!is_null($dateWaybillRequested) && !$hasPackageDetail) {
                $status = array(
                    'id'   => self::WAYBILL_REQUEST_STATUS_WAITING_FOR_RESPONSE,
                    'desc' => 'Waiting for response'
                );

                if ($this->canReRequestWayill()) {
                    $status = array(
                        'id'   => self::WAYBILL_REQUEST_STATUS_READY_TO_REPROCESS,
                        'desc' => 'Ready to re-process generation of waybill'
                    );
                }
            }
            else if (is_null($dateWaybillRequested) && !$hasPackageDetail) {
                $status = array(
                    'id'   => self::WAYBILL_REQUEST_STATUS_READY_TO_PROCESS,
                    'desc' => 'Ready to process generation of waybill'
                );
            }
            else {
                $status = array(
                    'id'   => self::WAYBILL_REQUEST_STATUS_ERROR,
                    'desc' => 'Error upon generation'
                );
            }

        }
        catch (\Exception $e) {
            $status = array(
                'id'   => self::WAYBILL_REQUEST_STATUS_ERROR,
                'desc' => 'Error upon generation'
            );
        }

        return $status;
    }

    public function canReRequestWayill()
    {
        $dateWaybillRequested = $this->getDateWaybillRequested();
        if (!$dateWaybillRequested) {
            return false;
        }

        $dateDiffinHour = Carbon::now()->diffInHours(Carbon::instance($dateWaybillRequested));

        return (int)$dateDiffinHour >= 3;
    }
}
