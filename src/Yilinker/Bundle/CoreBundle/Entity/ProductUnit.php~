<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue;
use Yilinker\Bundle\CoreBundle\Entity\ProductPromoMap;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnitImage;
use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable;
use Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;

/**
 * ProductUnit
 */
class ProductUnit extends YilinkerTranslatable
{

    const STATUS_INACTIVE = 0;

    const STATUS_ACTIVE = 1;

    const STATUS_COMING_SOON = 2;

    /**
     * @var integer
     */
    private $productUnitId;

    /**
     * @var integer
     */
    private $quantity = '0';

    /**
     * @var string
     */
    private $sku = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var string
     */
    private $price;

    /**
     * @var string
     */
    private $discountedPrice;

    /**
     * @var string
     */
    private $commission = '0.00';

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var integer
     */
    private $status = '1';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productAttributeValues;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productUnitImages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productPromoMaps;

    /**
     * @var null
     */
    private $appliedBaseDiscountPrice = null;

    /**
     * @var null
     */
    private $appliedDiscountPrice = null;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnitMap
     */
    private $manufacturerProductUnitMap;

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
    private $internalSku = '';

    private $promotions = array();
    private $inWishlist = false;

    private $hasCurrentPromo = false;

    private $hasUpcomingPromo = false;

    private $isBulkDiscount = false;

    private $promoInstance = null;

    private $upcomingPromoInstances = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productUnitImages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productPromoMaps = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productUnitWarehouses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get productUnitId
     *
     * @return integer
     */
    public function getProductUnitId()
    {
        return $this->productUnitId;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return ProductUnit
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        if ($product = $this->getProduct()) {
            $primaryWarehouse = $product->getWarehouse(ProductWarehouse::DEFAULT_PRIORITY);

            if ($primaryWarehouse) {
                $productUnitWarehouse = $this->getProductUnitByWarehouse($primaryWarehouse);
                if ($productUnitWarehouse) {
                    $productUnitWarehouse->setQuantity($quantity);
                }
            }
        }

        return $this;
    }

    /**
     * Get quantity
     *
     * @param boolean $isRaw
     * @return integer
     */
    public function getQuantity($isRaw = false)
    {
        $quantity = 0;
        if ($isRaw) {
            $storeType = $this->getProduct()->getUser()->getStore()->getStoreType();
            if($storeType == Store::STORE_TYPE_MERCHANT){
                $productUnitWarehouses = $this->getProductUnitWarehouses();
                foreach($productUnitWarehouses as $productUnitWarehouse){
                    $quantity += $productUnitWarehouse->getQuantity();
                }

                return $quantity;
            }
        }

        $quantity = $this->quantity;
        // if not an inhouse product unit then get quantity from warehouse
        if (!$this->isInhouseProductUnit()) {
            $priority = ProductWarehouse::DEFAULT_PRIORITY;

            if ($product = $this->getProduct()) {
                $primaryWarehouse = $product->getWarehouse($priority);
                if ($primaryWarehouse) {
                    $quantity = $this->getWarehouseQuantity($primaryWarehouse);

                    if ($quantity <= 0
                        && $secondaryWarehouse = $product->getWarehouse(++$priority)) {
                        $quantity = $this->getWarehouseQuantity($secondaryWarehouse);
                    }
                }
            }
        }

        return $quantity;
    }

    public function getWarehouse($priority = ProductWarehouse::DEFAULT_PRIORITY)
    {
        $productUnitWarehouse = null;
        $manufacturerProductUnitMap = $this->getManufacturerProductUnitMap();
        if(!$manufacturerProductUnitMap && $product = $this->getProduct()){
            $productUnitWarehouse = $product->getWarehouse($priority);
            if ($productUnitWarehouse) {
                $quantity = $this->getWarehouseQuantity($productUnitWarehouse);

                if ($quantity <= 0) {
                    return $this->getWarehouse(++$priority);
                }
            }
        }

        return $productUnitWarehouse;
    }

    public function getProductUnitByWarehouse($warehouse)
    {
        $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq('userWarehouse', $warehouse))
                        ->setMaxResults(1);

        if ($productUnitWarehouses = $this->getProductUnitWarehouses()) {
            $productUnit = $productUnitWarehouses->matching($criteria);

            if ($productUnit) {
                return $productUnit->first();
            }
        }

        return null;
    }

    /**
     * Gets the quantity within a user warehouse
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $warehouse
     * @return int
     */
    public function getWarehouseQuantity($warehouse)
    {
        $quantity = 0;

        $productUnitWarehouse = $this->getProductUnitByWarehouse($warehouse);

        if ($productUnitWarehouse) {
            $quantity = (int) $productUnitWarehouse->getQuantity();
        }

        return $quantity;
    }

    /**
     * Set sku
     *
     * @param string $sku
     * @return ProductUnit
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
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductUnit
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
     * Set price
     *
     * @param string $price
     * @return ProductUnit
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        $price = $this->price;
        if (!$price && $this->isInhouseProductUnit()) {
            $price = $this->getRetailPrice();
        }

        return $price ? $price: '0';
    }

    /**
     * Set discounted_price
     *
     * @param string $discountedPrice
     * @return ProductUnit
     */
    public function setDiscountedPrice($discountedPrice)
    {
        $this->discountedPrice = $discountedPrice;

        return $this;
    }

    /**
     * Get discounted_price
     *
     * @return string
     */
    public function getDiscountedPrice()
    {
        return $this->discountedPrice ? $this->discountedPrice: '0';
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return ProductUnit
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return ProductUnit
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ProductUnit
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        // temporarily set to always be true as long as
        // not all of the logic for this status is not yet
        // implemented
        return 1;
    }

    /**
     * Converts the entity into an array
     *
     * @param boolean $isFormatted
     * @param boolean $getWarehouse
     */
    public function toArray(
        $isFormatted = false,
        $showBulkDiscount = true,
        $getWarehouses = false,
        $activeUnits = false
    ){
        if($activeUnits){
            if($this->status != self::STATUS_ACTIVE){
                return null;
            }
        }

        $unitImages = $this->getProductUnitImages();
        $imageIds = array();
        foreach ($unitImages as $unitImage) {
            $productImage = $unitImage->getProductImage();
            if(!$productImage){
                continue;
            }
            $productImageDetails = $productImage->toArray();
            $imageIds[] = $productImageDetails['id'];
        }

        $price = $this->price ? $this->price : 0;
        $discountedPrice = $this->discountedPrice;
        $discount = $this->getDiscount();
        $promoTypeId = null;
        $promoTypeName = null;

        if(!is_null($this->appliedDiscountPrice)){
            $price = $this->appliedBaseDiscountPrice;
            $discountedPrice = $this->appliedDiscountPrice;
        }

        $promoMaps = $this->getProductPromoMaps();
        $promoInstance = $this->attachPromoDetails(
                            $promoMaps,
                            $promoTypeId,
                            $promoTypeName,
                            $discount,
                            $showBulkDiscount
                        );

        $appliedDiscountPrice = $this->getAppliedDiscountPrice();
        $appliedBaseDiscountPrice = $this->getAppliedBaseDiscountPrice();

        $data = array(
            'productId'                 => $this->getProduct()->getProductId(),
            'productUnitId'             => $this->productUnitId,
            'quantity'                  => $this->getPromoInstanceQuantity($promoInstance),
            'sku'                       => $this->sku,
            'slug'                      => $this->getProduct()->getSlug(),
            'price'                     => $isFormatted? number_format($price, 2) : $price,
            'discountedPrice'           => $isFormatted? number_format($discountedPrice, 2) : $discountedPrice,
            'appliedBaseDiscountPrice'  => $isFormatted? number_format($appliedBaseDiscountPrice, 2) : $appliedBaseDiscountPrice,
            'appliedDiscountPrice'      => $isFormatted? number_format($appliedDiscountPrice, 2) : $appliedDiscountPrice,
            'promoTypeId'               => $promoTypeId,
            'promoTypeName'             => $promoTypeName,
            'discount'                  => $discount,
            'dateCreated'               => $this->dateCreated,
            'dateLastModified'          => $this->dateLastModified,
            'status'                    => $this->status,
            'imageIds'                  => $imageIds,
            'primaryImage'              => $this->getPrimaryImageLocation(),
            'primaryThumbnailImage'     => $this->getPrimaryImageLocationBySize("thumbnail"),
            'primarySmallImage'         => $this->getPrimaryImageLocationBySize("small"),
            'primaryMediumImage'        => $this->getPrimaryImageLocationBySize("medium"),
            'primaryLargeImage'         => $this->getPrimaryImageLocationBySize("large"),
            'dateCreated'               => $this->getDateCreated(),
            'dateLastModified'          => $this->getDateLastModified(),
            'promoInstance'             => $promoInstance['promoInstance'],
            'promoInstanceNotYetStarted'=> $promoInstance['promoInstanceNotYetStarted'],
            'inWishlist'                => $this->inWishlist(),
            'commission'                => $this->getCommission(),
            'weight'                    => $this->getWeight(),
            'length'                    => $this->getLength(),
            'height'                    => $this->getHeight(),
            'width'                     => $this->getWidth(),
        );

        if ($this->isInhouseProductUnit()) {
            $data['discountPercentage'] = $this->getDiscountPercentage();
            $data['retailPrice'] = $this->getRetailPrice();
        }

        if($getWarehouses){
            $data['warehouses'] = $this->getWarehousesDetails(true);
        }

        return $data;
    }

    public function getPromoInstanceQuantity($promoInstance)
    {
        $quantity = $this->getQuantity();

        //promo is active but not yet started
        foreach ($promoInstance['promoInstanceNotYetStarted'] as $pInstance) {
            if ($pInstance['isEnabled']) {
                $quantity = $this->getQuantity() - $pInstance['maxQuantity'];
                continue;
            }
        }
        //promo is started and active
        foreach ($promoInstance['promoInstance'] as $pInstance) {
            if ($pInstance['isEnabled']) {
                $quantity = $pInstance['maxQuantity'] - $pInstance['currentQuantity'];
                continue;
            }
        }

        return $quantity;
    }

    /**
     * Get the warehouse details of the ProductUnit
     *
     * @param boolean $asArray
     * @return mixed
     */
    public function getWarehousesDetails($asArray = false)
    {
        $product = $this->getProduct();
        $warehouses = array();
        $primaryUserWarehouse = $product->getWarehouse(ProductWarehouse::DEFAULT_PRIORITY);
        if($primaryUserWarehouse){
            $warehouses[] = array(
                'priority'  => ProductWarehouse::DEFAULT_PRIORITY,
                'quantity'  => $this->getWarehouseQuantity($primaryUserWarehouse),
                'warehouse' => $asArray ? $primaryUserWarehouse->toArray() : $primaryUserWarehouse,
            );
        }
        $secondaryUserWarehouse = $product->getWarehouse(ProductWarehouse::SECONDARY_PRIORITY);
        if($secondaryUserWarehouse){
            $warehouses[] = array(
                'priority'  => ProductWarehouse::SECONDARY_PRIORITY,
                'quantity'  => $this->getWarehouseQuantity($secondaryUserWarehouse),
                'warehouse' => $asArray ? $secondaryUserWarehouse->toArray() : $secondaryUserWarehouse,
            );
        }

        return $warehouses;
    }

    /**
     * @return null
     */
    public function getAppliedBaseDiscountPrice()
    {
        $appliedBaseDiscountPrice = $this->appliedBaseDiscountPrice;
        if(is_null($appliedBaseDiscountPrice)){
            return $this->price;
        }
        else{
            return $appliedBaseDiscountPrice;
        }
    }

    /**
     * @param null $appliedBaseDiscountPrice
     */
    public function setAppliedBaseDiscountPrice($appliedBaseDiscountPrice)
    {
        $this->appliedBaseDiscountPrice = $appliedBaseDiscountPrice;
    }

    /**
     * @return null
     */
    public function getAppliedDiscountPrice()
    {
        $appliedDiscountPrice = $this->appliedDiscountPrice;
        if(is_null($appliedDiscountPrice)){
            return $this->discountedPrice;
        }
        else{
            return $appliedDiscountPrice;
        }
    }

    public function singleAppliedDiscountPrice()
    {
        return $this->appliedDiscountPrice;
    }

    /**
     * @param null $appliedDiscountPrice
     */
    public function setAppliedDiscountPrice($appliedDiscountPrice)
    {
        $this->appliedDiscountPrice = $appliedDiscountPrice;
    }

    public function getDiscount($forBulkDiscount = false)
    {
        $discount = 0;

        if($forBulkDiscount){
            if(
                $this->promoInstance && is_array($this->promoInstance)
            ){
                $productPromoMap = $this->getProductPromoMapByInstance($this->promoInstance["promoInstanceId"]);

                if($productPromoMap){
                    $discountedPrice = floatval($productPromoMap->getDiscountedPrice());
                    $discount = (1 - ($discountedPrice / $this->getAppliedBaseDiscountPrice())) * 100;
                }
            }
            elseif(
                $this->promoInstance &&
                $this->promoInstance instanceof promoInstance
            ){
                $productPromoMap = $this->getProductPromoMapByInstance($this->promoInstance);

                if($productPromoMap){
                    $discountedPrice = floatval($productPromoMap->getDiscountedPrice());
                    $discount = (1 - ($discountedPrice / $this->getAppliedBaseDiscountPrice())) * 100;
                }
            }
        }
        else{
            if ($this->getAppliedDiscountPrice() > 0 && $this->getAppliedBaseDiscountPrice() > 0) {
                $discount = (1 - ($this->getAppliedDiscountPrice() / $this->getAppliedBaseDiscountPrice())) * 100;
            }
        }

        $discount = floatval(number_format($discount,2)); // floatval(number_format(floor($discount*100)/100, 2));

        return $discount;
    }

    public function getDiscountPercentage()
    {
        if(bccomp($this->price, "0") === 0){
            return "0.00";
        }

        return bcmul(bcdiv($this->discountedPrice, $this->price, 4), "100.00", 2);
    }

    /**
     * @return array combination of attributes and values
     */
    public function getCombination($attributeNameIds = array())
    {
        $attributeValues = count($attributeNameIds) > 0 ?
                           $this->getProductAttributeValuesOf($attributeNameIds) :
                           $this->productAttributeValues;

        $combination = array();
        foreach ($attributeValues as $attributeValue) {
            $attributeNameId = $attributeValue->getProductAttributeName()
                                              ->getProductAttributeNameId();
            $combination[$attributeNameId] = $attributeValue->toArray();
        }

        return $combination;
    }

    /**
     * Add productAttributeValues
     *
     * @param ProductAttributeValue $productAttributeValues
     * @return ProductUnit
     */
    public function addProductAttributeValue(ProductAttributeValue $productAttributeValues)
    {
        $this->productAttributeValues[] = $productAttributeValues;

        return $this;
    }

    /**
     * Remove productAttributeValues
     *
     * @param ProductAttributeValue $productAttributeValues
     */
    public function removeProductAttributeValue(ProductAttributeValue $productAttributeValues)
    {
        $this->productAttributeValues->removeElement($productAttributeValues);
    }

    /**
     * Get productAttributeValues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductAttributeValues()
    {
        return $this->productAttributeValues;
    }

    public function getProductAttributeValuesOf($attributeNameIds = array())
    {
        $filterAttribute = Criteria::expr()->in('productAttributeName', $attributeNameIds);
        $criteria = Criteria::create()->andWhere($filterAttribute);
        $filteredAttributeValues = $this->productAttributeValues->matching($criteria);

        return $filteredAttributeValues;
    }

    /**
     * Add productUnitImages
     *
     * @param ProductUnitImage $productUnitImages
     * @return ProductUnit
     */
    public function addProductUnitImage(ProductUnitImage $productUnitImages)
    {
        $this->productUnitImages[] = $productUnitImages;

        return $this;
    }

    /**
     * Remove productUnitImages
     *
     * @param ProductUnitImage $productUnitImages
     */
    public function removeProductUnitImage(ProductUnitImage $productUnitImages)
    {
        $this->productUnitImages->removeElement($productUnitImages);
    }

    /**
     * Get productUnitImages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductUnitImages()
    {
        return $this->productUnitImages;
    }

    /**
     * Add productPromoMaps
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductPromoMap $productPromoMaps
     * @return ProductUnit
     */
    public function addProductPromoMap(ProductPromoMap $productPromoMaps)
    {
        $this->productPromoMaps[] = $productPromoMaps;

        return $this;
    }

    /**
     * Remove productPromoMaps
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductPromoMap $productPromoMaps
     */
    public function removeProductPromoMap(ProductPromoMap $productPromoMaps)
    {
        $this->productPromoMaps->removeElement($productPromoMaps);
    }

    /**
     * Get productPromoMaps
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductPromoMaps()
    {
        return $this->productPromoMaps;
    }

    /**
     * Get productPromoMap
     */
    public function getProductPromoMapByInstance($promoInstance)
    {
        $criteria = Criteria::create()
                        ->where(Criteria::expr()->eq("promoInstance", $promoInstance))
                        ->setMaxResults(1);

        $productPromoMap = $this->getProductPromoMaps()->matching($criteria);

        if($productPromoMap){
            return $productPromoMap->first();
        }
        else{
            return null;
        }
    }

    public function addPromotions($value)
    {
        array_push($this->promotions, $value);
    }

    /**
     * @return array
     */
    public function getPromotions()
    {
        return $this->promotions;
    }

    /**
     * @return ProductImage
     */
    public function getPrimaryProductImage()
    {
        $productUnitImage = $this->getProductUnitImages()->first();
        if($productUnitImage){
            return $productUnitImage->getProductImage();
        }

        return $this->getProduct()->getPrimaryImage();
    }

    public function getPrimaryImageLocation()
    {
        $imageLocation = '';
        $primaryImage = $this->getPrimaryProductImage();
        if ($primaryImage) {
            $imageLocation = $primaryImage->getImageLocation();
        }

        if(is_null($imageLocation) || $imageLocation == ""){
            $imageLocation = $this->getProduct()->getPrimaryImageLocation();
        }

        return $imageLocation;
    }

    public function getPrimaryImageLocationBySize($size = null)
    {
        $imageLocation = '';
        $primaryImage = $this->getPrimaryProductImage();

        if ($primaryImage) {
            $imageLocation = $primaryImage->getImageLocationBySize($size);
        }

        if(is_null($imageLocation) || $imageLocation == ""){
            $imageLocation = $this->getProduct()->getPrimaryImageLocationBySize($size);
        }

        return $imageLocation;
    }

    public function attachPromoDetails(
        $promoMaps,
        &$promoTypeId,
        &$promoTypeName,
        &$discount,
        $showBulkDiscount
    ){
        $promoInstance = array();
        $promoInstanceNotYetStarted = array();

        foreach($promoMaps as $promoMap){
            $instance = $promoMap->getPromoInstance();
            $map = $this->pushPromoInstance($instance, $promoMap);

            $dateNow = Carbon::now();
            $dateStart = Carbon::instance($instance->getDateStart());
            $dateEnd = Carbon::instance($instance->getDateEnd());

            if(
                $instance->getIsEnabled() &&
                $dateNow->between($dateStart, $dateEnd) &&
                ($map["currentQuantity"] < $map["maxQuantity"] || $showBulkDiscount)
            ){

                array_push($promoInstance, $map);

                $promoTypeId = $instance->getPromoType()->getPromoTypeId();
                $promoTypeName = $instance->getPromoType()->getName();

                $hours = $dateNow->diffInHours($dateStart);
                $this->overrideDiscount($promoMap, $hours, $showBulkDiscount, $discount);
            }
            else if (
                $instance->getIsEnabled() &&
                $dateNow->lt($dateStart) &&
                $dateNow->lt($dateEnd) &&
                ($map["currentQuantity"] < $map["maxQuantity"] || $showBulkDiscount)
            ){
                array_push($promoInstanceNotYetStarted,$map);
            }
        }


        return  array('promoInstance' => $promoInstance, 'promoInstanceNotYetStarted' => $promoInstanceNotYetStarted);
    }

    private function pushPromoInstance($instance, $promoMap)
    {
        $currentQuantity = $this->getCurrentQuantity($instance);

        return array(
            "promoInstanceId" => $instance->getPromoInstanceId(),
            "promoType" => $instance->getPromoType()->getPromoTypeId(),
            "dateStart" => $instance->getDateStart(),
            "dateEnd" => $instance->getDateEnd(),
            "isEnabled" => $instance->getIsEnabled(),
            "quantityRequired" => $promoMap->getQuantityRequired(),
            "maxPercentage" => $promoMap->getMaximumPercentage(),
            "minPercentage" => $promoMap->getMinimumPercentage(),
            "discountedPrice" => $this->discountedPrice,
            "advertisement" => $instance->getAdvertisement(),
            "maxQuantity" => $promoMap->getMaxQuantity(),
            "currentQuantity" => $currentQuantity
        );
    }

    private function getCurrentQuantity($instance)
    {
        $currentQuantity = 0;

        if(is_array($instance)){
            $dateStart = $instance["dateStart"];
            $dateEnd = $instance["dateEnd"];
        }
        else{
            $dateStart = $instance->getDateStart();
            $dateEnd = $instance->getDateEnd();
        }

        $orderProducts = $this->getProduct()->filterOrderProductsByDate(
                            $dateStart,
                            $dateEnd,
                            $this->getSku()
                        );

        foreach ($orderProducts as $orderProduct){

            if ($orderProduct->getOrder()->getPaymentMethod()->getPaymentMethodId() == PaymentMethod::PAYMENT_METHOD_COD) {
                $isBought = $orderProduct->hasStatus(array(
                                OrderProductStatus::PAYMENT_CONFIRMED,
                                OrderProductStatus::STATUS_COD_TRANSACTION_CONFIRMED
                            ),
                            $dateStart,
                            $dateEnd
                        );
            } else {
                $isBought = true;
            }

            $cancelledStatuses = array(
                OrderProductStatus::STATUS_CANCELED_REQUEST_BY_SELLER_BEFORE_DELIVERY,
                OrderProductStatus::STATUS_CANCELLATION_BEFORE_DELIVERY_APPROVED,
                OrderProductStatus::STATUS_CANCELED_BY_ADMIN
            );
            $currentStatus = $orderProduct->getOrderProductStatus() ?
                             $orderProduct->getOrderProductStatus()->getOrderProductStatusId() : null;

            if($isBought && in_array($currentStatus, $cancelledStatuses) === false){
                $currentQuantity += $orderProduct->getQuantity();
            }
        }

        return $currentQuantity;
    }

    private function overrideDiscount($promoMap, $hours, $showBulkDiscount, &$discount)
    {
        $promoInstance = $promoMap->getPromoInstance();

        switch($promoInstance->getPromoType()->getPromoTypeId()){
            case PromoType::BULK:
                $discount = (1 - ($promoMap->getDiscountedPrice() / $this->getPrice())) * 100;
                $discount = round($discount, 2);
                break;
            case PromoType::PER_HOUR:
                $discount = $this->getDiscount();
                if($discount < 0){
                    $discount = floatval($promoInstance->getMinimumPercentage());
                }
                break;
            default:
                $discount = $this->getDiscount();
                break;
        }
    }

    /**
     * Set manufacturerProductUnitMap
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnitMap $manufacturerProductUnitMap
     * @return ProductUnit
     */
    public function setManufacturerProductUnitMap(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnitMap $manufacturerProductUnitMap = null)
    {
        $this->manufacturerProductUnitMap = $manufacturerProductUnitMap;

        return $this;
    }

    /**
     * Get manufacturerProductUnitMap
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnitMap
     */
    public function getManufacturerProductUnitMap()
    {
        return $this->manufacturerProductUnitMap;
    }

    /**
     * Set weight
     *
     * @param string $weight
     * @return ProductUnit
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
     * @return ProductUnit
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
     * @return ProductUnit
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
     * @return ProductUnit
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

    public function inWishlist($value = null)
    {
        if (is_null($value)) {
            return $this->inWishlist;
        }
        else {
            $this->inWishlist = $value;
        }
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productUnitWarehouses;


    /**
     * Add productUnitWarehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses
     * @return ProductUnit
     */
    public function addProductUnitWarehouse(\Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses)
    {
        $this->productUnitWarehouses[] = $productUnitWarehouses;
    }

    /**
     * Remove productUnitWarehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses
     */
    public function removeProductUnitWarehouse(\Yilinker\Bundle\CoreBundle\Entity\ProductUnitWarehouse $productUnitWarehouses)
    {
        $this->productUnitWarehouses->removeElement($productUnitWarehouses);
    }

    /**
     * Get productUnitWarehouses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductUnitWarehouses()
    {
        return $this->productUnitWarehouses;
    }

    /**
     * Set internalSku
     *
     * @param string $internalSku
     * @return ProductUnit
     */
    public function setInternalSku($internalSku)
    {
        $this->internalSku = $internalSku;

        return $this;
    }

    /**
     * Get internalSku
     *
     * @return string
     */
    public function getInternalSku()
    {
        return $this->internalSku;
    }

    /*
     * Set commission
     *
     * @param string $commission
     * @return ProductUnit
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /*
     * Get commission
     *
     * @return string
     */
    public function getCommission()
    {
        return $this->commission;
    }

    public function getProductWarehouse()
    {
        $product = $this->getProduct();
        $product->getWarehouse();

        return $product->productWarehouse;
    }

    public function hasCOD()
    {
        return $this->getProduct()->hasCOD();
    }

    public function getHandlingFee()
    {
        $productWarehouse = $this->getProductWarehouse();
        if ($productWarehouse) {
            return $productWarehouse->getHandlingFee();
        }

        return 0;
    }

    public function setHasCurrentPromo($hasCurrentPromo = false)
    {
        $this->hasCurrentPromo = $hasCurrentPromo;
    }

    public function getHasCurrentPromo()
    {
        return $this->hasCurrentPromo;
    }

    public function setHasUpcomingPromo($hasUpcomingPromo = false)
    {
        $this->hasUpcomingPromo = $hasUpcomingPromo;
    }

    public function getHasUpcomingPromo()
    {
        return $this->hasUpcomingPromo;
    }

    public function setIsBulkDiscount($isBulkDiscount = false)
    {
        $this->isBulkDiscount = $isBulkDiscount;
    }

    public function getIsBulkDiscount()
    {
        return $this->isBulkDiscount;
    }

    public function setPromoInstance($promoInstance = null)
    {
        $this->promoInstance = $promoInstance;
    }

    public function getPromoInstance()
    {
        return $this->promoInstance;
    }

    /**
     * Add upcoming promoinstances
     */
    public function addUpcomingPromoInstances($promoInstance)
    {
        $this->upcomingPromoInstances[] = $promoInstance;

        return $this;
    }

    public function getPublicQuantity($handleMultiple = false)
    {
        $quantity = $this->getQuantity();

        //promo is started and active
        if(
            is_array($this->promoInstance) &&
            $this->promoInstance["isEnabled"] &&
            $this->hasCurrentPromo
        ){
            $quantity = $this->promoInstance["maxQuantity"] - $this->getCurrentQuantity($this->promoInstance);
        }
        elseif(
            $this->promoInstance instanceof PromoInstance &&
            $this->promoInstance->getIsEnabled() &&
            $this->hasCurrentPromo
        ){
            $productPromoMap = $this->getProductPromoMapByInstance($this->promoInstance);
            $quantity = $productPromoMap->getMaxQuantity() - $this->getCurrentQuantity($this->promoInstance);
        }

        //promo is active but not yet started
        if($handleMultiple){
            $quantity = $this->handleSingleQuantityReservation($quantity);
        }
        else{
            $quantity = $this->handleMultipleQuantityReservations($quantity);
        }

        if($quantity < 0){
            $quantity = 0;
        }

        return $quantity;
    }

    public function isInhouseProductUnit()
    {
        return $this instanceof InhouseProductUnit;
    }

    private function handleSingleQuantityReservation($quantity)
    {
        $promoInstance = array_shift($this->upcomingPromoInstances);

        if($promoInstance){
            if(is_array($promoInstance)){
                $quantity -= $promoInstance["maxQuantity"];
            }
            else{
                $quantity -= $promoInstance->getMaxQuantity();
            }
        }

        return $quantity;
    }

    /** FOR KEEPS : this function will handle multiple quantity reservation for the unit */
    private function handleMultipleQuantityReservations($quantity)
    {
        foreach($this->upcomingPromoInstances as $upcomingPromoInstance){
            if(is_array($upcomingPromoInstance)){
                $quantity -= $upcomingPromoInstance["maxQuantity"];
            }
            elseif($upcomingPromoInstance){
                $productPromoMap = $upcomingPromoInstance->getProductPromoMapByUnit($this);

                if($productPromoMap){
                    $quantity -= $productPromoMap->getMaxQuantity();
                }
            }
        }

        return $quantity;
    }
}
