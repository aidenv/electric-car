<?php

namespace Yilinker\Bundle\CoreBundle\Services\Logistics\Yilinker;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\PackageStatus;
use Yilinker\Bundle\CoreBundle\Entity\Package;
use Yilinker\Bundle\CoreBundle\Entity\PackageDetail;
use Yilinker\Bundle\CoreBundle\Entity\PackageHistory;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\OrderConsigneeAddress;
use Yilinker\Bundle\CoreBundle\Entity\Warehouse;
use Yilinker\Bundle\CoreBundle\Helpers\ArrayHelper;
use Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use DateTime;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use Doctrine\Common\Collections;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;

/**
 * Class Express
 *
 * @package Yilinker\Bundle\CoreBundle\Services\Logistics\Yilinker
 */
class Express
{
    /**
     * Opening hour
     *
     * @var int
     */
    const OPENING_HOUR = 8;

    /**
     * Closing hour
     *
     * @var int
     */
    const CLOSING_HOUR = 16;
    
    /**
     * Pickup schedule interval in minutes
     *
     * @var int
     */
    const PICKUP_INTERVAL_MINUTES = 30;

    /**
     * API Timeout in seconds
     *
     * @var int
     */
    const API_TIMEOUT_SEC = 10;

    /**
     * API Timeout in seconds
     *
     * @var int
     */
    const API_PICKUP_TIMEOUT_SEC = 5;

    /**
     * Pickup schedule start time
     *
     * @var Carbon\Carbon
     */
    private $pickupScheduleFrom;

    /**
     * Pickup schedule end time
     *
     * @var Carbon\Carbon
     */
    private $pickupScheduleTo;

    /**
     * API Configuration file
     *
     * @param mixed
     */
    private $configuration;

    /**
     * Doctrine Entity Manager
     *
     * @param Doctrine\ORM\EntityManager
     */
    private $entityManager;

    private $container;

    /**
     * Constructor
     *
     * @param Doctrine\ORM\EntityManager $entityManager
     * @param Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper $assetHelper
     */
    public function __construct($entityManager, $assetHelper, $container)
    {
        $this->pickupScheduleFrom = Carbon::createFromTime(self::OPENING_HOUR, 0, 0);
        $this->pickupScheduleTo = Carbon::createFromTime(self::CLOSING_HOUR, 0, 0);
        $this->entityManager = $entityManager;
        $this->assetHelper = $assetHelper;
        $this->container = $container;
    }

    /**
     * Set the API configuration
     *
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->configuration = $config;
    }

    /**
     * Get available pickup schedule
     *
     * @return Carbon\Carbon[]
     */
    public function getAvailablePickupSchedule() 
    {
        $schedule = array();
        $currentSchedule = $this->pickupScheduleFrom;
        while($this->pickupScheduleTo->gte($currentSchedule)){
            $schedule[] = $currentSchedule->copy();
            $currentSchedule = $currentSchedule->addMinutes(self::PICKUP_INTERVAL_MINUTES);
        }

        return $schedule;
    }

    /**
     * Schedule the pickup of the product
     *
     * @param DateTime $scheduleDatetime
     * @param string $remark
     * @param Yilinker\Bundle\CoreBundle\Entity\OrderProduct[] $orderProducts
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param string $rescheduledWaybillNumber
     * @param boolean $isTestPackage Packages will be automatically completed by YLX. Use this for testing only.
     * @param mixed
     */
    public function schedulePickup($scheduleDatetime, $remark, $orderProducts, $user = null, $rescheduledWaybillNumber = null, $isTestPackage = false)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );
   
        $products = array();
        $orders = array();
        $sellers = array();
        $hasNonUserOrderProduct = false;
        $hasNonShippableOrderProduct = false;
        $shippableOrderProducts = TransactionService::getShippableStatuses();
        $resellerProductCount = 0;
        $warehouseRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Warehouse');
        $orderProductRepository = $this->entityManager->getRepository('YilinkerCoreBundle:OrderProduct');

        foreach($orderProducts as $orderProduct){
            $order = $orderProduct->getOrder();
            $seller = $orderProduct->getSeller();
            if($user && $user->getUserId() !== $seller->getUserId()){
                /**
                 * Optional check to determine if the authenticated user is indeed the seller
                 */
                $hasNonUserOrderProduct = true;
                break;
            }
            if(is_null($rescheduledWaybillNumber) && !in_array($orderProduct->getOrderProductStatus()->getOrderProductStatusId(), $shippableOrderProducts)){
                $hasNonShippableOrderProduct = true;
                break;
            }
            $isCod = $order->getPaymentMethod()->getPaymentMethodId() === PaymentMethod::PAYMENT_METHOD_COD;

            if(isset($orders[$order->getOrderId()]) === false){
                $orders[$order->getOrderId()] = $order;
            }
            if(isset($sellers[$seller->getUserId()]) === false){
                $sellers[$seller->getUserId()] = $seller;
            }

            $hasVoucher = false;
            $finalPrice = $orderProduct->getUnitPrice();
            $orderVouchers = $order->getOrderVouchers();
            if($orderVouchers->count() > 0){
                $hasVoucher = true;
                $defaultVoucher = $orderVouchers->first();
                $totalVoucherAmount = $defaultVoucher->getValue();                
                $totalOrderPricedBasedOnOrderProduct = $order->getOrderTotalBasedOnOrderProducts();
                $totalProductPrice = $orderProduct->getQuantifiedUnitPrice();               
                $totalPriceLessVoucher = bcsub($totalProductPrice, bcmul(bcdiv($totalProductPrice, $totalOrderPricedBasedOnOrderProduct, 8), $totalVoucherAmount, 8), 8);
                if(bccomp($totalPriceLessVoucher, "0.0000") < 1){
                    $finalPrice = "0.0000";
                }
                else{   
                    $finalPrice = bcdiv($totalPriceLessVoucher,$orderProduct->getQuantity(),8);
                }
            }

            $productData = array(
                'width'         => $orderProduct->getWidth(),
                'height'        => $orderProduct->getHeight(),
                'length'        => $orderProduct->getLength(),
                'weight'        => $orderProduct->getWeight(),
                'declaredValue' => $orderProduct->getUnitPrice(),
                'finalAmount'   => $finalPrice,
                //'description'   => $orderProduct->getDescription(),
                'name'          => $orderProduct->getProductName(),
                'quantity'      => $orderProduct->getQuantity(),
                'image'         => $orderProduct->getFullImagePath(),
                'productId'     => $orderProduct->getProduct()->getProductId(),
                'hasVoucher'    => $hasVoucher,
                'orderProductId' => $orderProduct->getOrderProductId()
            );

            if($orderProduct->getManufacturerProductUnit()){
                $productData['referencenumber'] = $orderProduct->getManufacturerProductReference();
                $productData['combinationid'] = $orderProduct->getManufacturerProductUnit()->getManufacturerProductUnitId();
                $resellerProductCount++;
            }

            $products[] = $productData;
        }
        $isResellerPackage = $resellerProductCount === count($orderProducts);

        if($hasNonUserOrderProduct){
            $response['message'] = 'An order product does not belong to this user';
        }
        else if($hasNonShippableOrderProduct){
            $response['message'] = 'An order product is not shippable';
        }
        else if(count($orders) === 0){
            $response['message'] = 'Order does not exist';
        }
        else if(count($orders) > 1){
            $response['message'] = 'Order products must belong to the same order';
        }
        else if(count($seller) > 1){
            $response['message'] = 'Order products must belong to the same seller';
        }
        else{
            $seller = reset($sellers);
            $order = reset($orders);
 
            $shipper = $this->formatShipperShipmentDetails($seller);
            $consignee = $this->formatConsigneeShipmentDetails($order);

            if(!$isResellerPackage && ( $shipper['barangayId'] === 0 || $shipper['cityId'] === 0 || $shipper['provinceId'] === 0)){
                $response['message'] = "Shipper address is incomplete";
            }
            else if(($consignee['barangayId'] === 0 || $consignee['cityId'] === 0 || $consignee['provinceId'] === 0)){
                $response['message'] = "Consignee address is incomplete";
            }
            else{
                $parameters = array(
                    'transactionDate'   => $order->getDateAdded()->format('Y-m-d H:i:s'),
                    'transactionNumber' => $order->getInvoiceNumber(),
                    'shipper'           => $shipper,
                    'consignee'         => $consignee,
                    'products'          => $products,
                    'pickupSchedule'    => $scheduleDatetime->format('Y-m-d H:i:s'),
                    'pickupRemark'      => $remark,
                    'isCod'             => $isCod ? "true" : "false",
                );

                if($rescheduledWaybillNumber){
                    $parameters['waybillNumber'] = $rescheduledWaybillNumber;
                }
                
                try{                
                    if($rescheduledWaybillNumber){
                        $url = $this->configuration['routes']['cancel_package'];
                    }
                    else{                        
                        $url = $this->configuration['routes']['create_package'];
                        if($isResellerPackage){
                            if($isTestPackage){
                                $url = $this->configuration['routes']['test_create_internal_package'];
                            }
                            else{
                                $url = $this->configuration['routes']['create_internal_package'];
                            }
                        }
                    }
                    
                    $request = new FormRequest(
                        FormRequest::METHOD_POST, '/'.$url, $this->configuration['baseurl']
                    );

                    $request->setFields($parameters);
                    $client = new Curl();
                    $buzzResponse = new Response();
                    $client->setTimeout(self::API_PICKUP_TIMEOUT_SEC);
                    $client->send($request, $buzzResponse);

                    if ($buzzResponse->isSuccessful()) {
                        $response['isSuccessful'] = true;
                        $response['data'] = ArrayHelper::array_column($products, 'orderProductId');
                    }

                }
                catch(RequestException $e){
                    $response['message'] = "Courier API has timeout";
                }
            }
        }
        return $response;
    }

    /**
     * Process Express schedule pickup postback
     *
     * @param array $apiResponse
     * @return array
     */
    public function processExpressSchedulePickupPostback (array $apiResponse)
    {
        $warehouseRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Warehouse');
        $orderProductRepository = $this->entityManager->getRepository('YilinkerCoreBundle:OrderProduct');
        $userOrderRepository = $this->entityManager->getRepository('YilinkerCoreBundle:UserOrder');
        $packageRepository = $this->entityManager->getRepository('YilinkerCoreBundle:Package');

        $this->entityManager->beginTransaction();
        try {
            if ($apiResponse['isSuccessful']) {
                $response = array(
                    'isSuccessful' => false,
                    'message'      => '',
                    'data'         => array(),
                );
                $packagesData = array();
                $apiPackageData = $apiResponse['data'];

                if (!is_null($apiPackageData)) {
                    $order = null;
                    $packageCreateDatetime = DateTime::createFromFormat('Y-m-d H:i:s', $apiPackageData['dateCreated']);
                    $packageStatus = $this->entityManager->getReference('YilinkerCoreBundle:PackageStatus', $apiPackageData['initialStatusId']);
                    $package = $packageRepository->findOneByWaybillNumber($apiPackageData['waybillNumber']);

                    if (isset($apiPackageData['invoiceNumber'])) {
                        $order =  $userOrderRepository->findOneByInvoiceNumber($apiPackageData['invoiceNumber']);
                    }

                    if ($package instanceof Package) {
                        $response['message'] = 'Waybill exist';
                    }
                    else {
                        if ($order instanceof UserOrder) {

                            if ($packageStatus) {
                                $currentDate = new DateTime;
                                $warehouseDetails = $apiPackageData['warehouse'];
                                $warehouse = $warehouseRepository->findOneByReferenceNumber($warehouseDetails['warehouseReferenceNumber']);
                                if (is_null($warehouse)) {
                                    $warehouse = new Warehouse;
                                    $warehouse->setName($warehouseDetails['name'])
                                        ->setReferenceNumber($warehouseDetails['warehouseReferenceNumber'])
                                        ->setDateCreated($currentDate)
                                        ->setDateLastModified($currentDate);
                                    $this->entityManager->persist($warehouse);
                                }

                                $package = new Package();
                                $package->setWaybillNumber($apiPackageData['waybillNumber']);
                                $package->setDateAdded($packageCreateDatetime);
                                $package->setDateLastModified($packageCreateDatetime);
                                $package->setUserOrder($order)
                                    ->setWarehouse($warehouse);

                                $package->setPackageStatus($packageStatus);
                                $this->entityManager->persist($package);

                                $readyForPickupStatus =  $this->entityManager->getReference(
                                    'YilinkerCoreBundle:OrderProductStatus', OrderProduct::STATUS_READY_FOR_PICKUP
                                );
                                $packageDetails = array();

                                foreach ($apiPackageData['products'] as $key => $product) {

                                    if (isset($product['orderProductId'])) {
                                        $orderProduct = $orderProductRepository->find($product['orderProductId']);
                                    }
                                    else {
                                        $orderProduct = $orderProductRepository->findOneBy(array(
                                            'order' => $order,
                                            'product' => $product['productReferenceNumber']
                                        ));
                                    }
                                    $packageDetails[$key] = new PackageDetail();
                                    $packageDetails[$key]->setPackage($package);
                                    $packageDetails[$key]->setOrderProduct($orderProduct);
                                    $packageDetails[$key]->setDateAdded($packageCreateDatetime);
                                    $packageDetails[$key]->setQuantity($product['quantity']);
                                    $this->entityManager->persist($packageDetails[$key]);
                                    $package->addPackageDetail($packageDetails[$key]);
                                    $orderProduct->setOrderProductStatus($readyForPickupStatus);
                                }

                                $packageHistory = new PackageHistory();
                                $packageHistory->setPackage($package);
                                $packageHistory->setPackageStatus($packageStatus);
                                $packageHistory->setDateAdded($packageCreateDatetime);
                                $this->entityManager->persist($packageHistory);

                                $packagesData[] = $apiPackageData;
                            }
                            else {
                                $response['message'] = 'The API returned an invalid package status';
                            }

                        }
                        else {
                            $response['message'] = 'The API returned an invalid invoice number';
                        }
                    }


                    if (count($packagesData) > 0) {
                        $response = array(
                            'isSuccessful' => true,
                            'message'      => count($packagesData)." packages created",
                            'data'         => $packagesData,
                        );
                        $this->entityManager->flush();
                        $this->entityManager->getConnection()->commit();
                    }
                }
                else {
                    $response['message'] = 'Invalid Json string';
                }

            }
            else {
                $response = array(
                    'isSuccessful' => false,
                    'message'      => $apiResponse['message'],
                    'data'         => $apiResponse['data'],
                );

                if (isset($apiResponse['data']['products'])) {

                    foreach ($apiResponse['data']['products'] as $products) {
                        $orderProduct = $orderProductRepository->find($products['orderProductId']);

                        if ($orderProduct instanceof OrderProduct) {
                            $orderProduct->setDateWaybillRequested(null);
                        }

                    }

                    $this->entityManager->flush();
                }
            }

        }
        catch (RequestException $e) {
            $response = array(
                'isSuccessful' => false,
                'message'      => $e->getMessage(),
                'data'         => array(),
            );
            $this->entityManager->rollback();
        }

        return $response;
    }

    /**
     * Formats the shipment details of an order
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\UserOrder $order
     * @return mixed
     */
    public function formatConsigneeShipmentDetails(UserOrder $order)
    {
        $consigneeAddress = $order->getOrderConsigneeAddress();
        $buyer = $order->getBuyer();
        if($consigneeAddress === null || $consigneeAddress->getLocation() === null){
           /**
            * if consignee address is null select whatever is available from the user address
            */
            $consigneeAddress = $buyer->getAddressNonNullLocation();
        }

        $consigneeLocation = $consigneeAddress ? $consigneeAddress->getLocation() : null;
        $consigneeLocationTree = $consigneeLocation ? $consigneeLocation->getLocalizedLocationTree() : array();
        $shipmentDetails = array(
            'firstName'      => $order->getConsigneeFirstName() ? $order->getConsigneeFirstName() : $order->getConsigneeName() ,
            'lastName'       => $order->getConsigneeLastName() ? $order->getConsigneeLastName() : $order->getConsigneeName() ,
            'email'          => $order->getBuyer()->getEmail() ? $order->getBuyer()->getEmail() : $this->container->getParameter('mailer_user'),
            'contactNumber'  => $order->getConsigneeContactNumber() ? $order->getConsigneeContactNumber() : '',
            'barangayId'     => isset($consigneeLocationTree['barangay']) ? $consigneeLocationTree['barangay']->getLocationId() : $this->getDefaultBarangay($consigneeLocationTree),
            'cityId'         => isset($consigneeLocationTree['city']) ? $consigneeLocationTree['city']->getLocationId() : 0,
            'provinceId'     => isset($consigneeLocationTree['province']) ? $consigneeLocationTree['province']->getLocationId() : 0,
            'latitude'       => "0.0000",
            'longitude'      => "0.0000",
        );

        if($consigneeAddress instanceof UserAddress){
            $shipmentDetails['userAddressId']  = 'CONSIGNEE-'.($consigneeAddress ? $consigneeAddress->getUserAddressId() : 0);
        }
        else if($consigneeAddress instanceof OrderConsigneeAddress ){
            $shipmentDetails['userAddressId']  = 'CONSIGNEE-'.($consigneeAddress ? $consigneeAddress->getOrderConsigneeAddressId() : 0);
        }

        if($consigneeAddress){
            $shipmentDetails['unitNumber'] = $consigneeAddress->getUnitNumber() ? $consigneeAddress->getUnitNumber() : "";
            $shipmentDetails['buildingName'] = $consigneeAddress->getBuildingName() ? $consigneeAddress->getBuildingName() : "";
            $shipmentDetails['streetNumber'] = $consigneeAddress->getStreetNumber() ? $consigneeAddress->getStreetNumber() : "";
            $shipmentDetails['streetName'] = $consigneeAddress->getStreetName() ? $consigneeAddress->getStreetName() : "";
            $shipmentDetails['subdivision'] = $consigneeAddress->getSubdivision() ? $consigneeAddress->getSubdivision() : "";
            $shipmentDetails['zipCode'] = $consigneeAddress->getZipCode() ? $consigneeAddress->getZipCode() : "";
            $shipmentDetails['fullAddress'] = $consigneeAddress->getAddressString() ? $consigneeAddress->getAddressString() : "";
        }        

        return $shipmentDetails;
    }

    /**
     * Formats the shipment details of the shipper
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return mixed
     */
    public function formatShipperShipmentDetails(User $user)
    {
        $address = $user->getDefaultAddress();
        $locationTree = array();
        if($address){
            $location = $address->getLocation();
            if($location){
                $locationTree = $location->getLocalizedLocationTree();
            }
        }

        $shipmentDetails =  array(
            'userId'         => $user->getUserId(),
            'firstName'      => $user->getFirstName(),
            'lastName'       => $user->getLastName(),
            'email'          => $user->getEmail(),
            'isSeller'       => $user->isSeller(),
            'isEmailVerified' => $user->getIsEmailVerified(),
            'password'       => $user->getPassword(),
            'contactNumber'  => $user->getContactNumber(),
            'barangayId'     => isset($locationTree['barangay']) ? $locationTree['barangay']->getLocationId() : 0,
            'cityId'         => isset($locationTree['city']) ? $locationTree['city']->getLocationId() : 0,
            'provinceId'     => isset($locationTree['province']) ? $locationTree['province']->getLocationId() : 0,
            'latitude'       => "0.0000",
            'longitude'      => "0.0000",
            'userAddressdId' => 'SHIPPER-'.($address ? $address->getUserAddressId() : 0),
        );

        if($address){
            $shipmentDetails['unitNumber'] = $address->getUnitNumber() ? $address->getUnitNumber() : "";
            $shipmentDetails['buildingName'] = $address->getBuildingName() ? $address->getBuildingName() : "";
            $shipmentDetails['streetNumber'] = $address->getStreetNumber() ? $address->getStreetNumber() : "";
            $shipmentDetails['streetName'] = $address->getStreetName() ? $address->getStreetName() : "";
            $shipmentDetails['subdivision'] = $address->getSubdivision() ? $address->getSubdivision() : "";
            $shipmentDetails['zipCode'] = $address->getZipCode() ? $address->getZipCode() : "";
            $shipmentDetails['fullAddress'] = $address->getAddressString() ? $address->getAddressString() : "";
        }
        else if(!$address && $user->isAffiliate()){
            $shipmentDetails['unitNumber'] =  "";
            $shipmentDetails['buildingName'] = "";
            $shipmentDetails['streetNumber'] = "";
            $shipmentDetails['streetName'] = "";
            $shipmentDetails['subdivision'] = "";
            $shipmentDetails['zipCode'] = "";
            $shipmentDetails['fullAddress'] = "";
        }

        return $shipmentDetails;
    }

    public function handlingFee($parameters)
    {
        $request = new FormRequest(FormRequest::METHOD_POST, '/'.$this->configuration['routes']['handling_fee'], $this->configuration['baseurl']);
        $request->setFields($parameters);
        $response = new Response();
        $client = new Curl();
        $client->send($request, $response);
        if ($response->isSuccessful()) {
            $content = json_decode($response->getContent(), true);
            if (array_key_exists('shippingCost', $content)) {
                return $content['shippingCost'];
            }
        }

        return false;
    }

    /**
     * Forward product list
     *
     * @param ManufacturerProduct[] $manufacturerProducts
     * @return boolean
     */
    public function forwardProductList($manufacturerProducts)
    {
        $response = array(
            'message'      => 'No manufacturer product found',
            'isSuccessful' => false,
            'data'         => array(),
        );

        $productsData = array();
        foreach($manufacturerProducts as $manufacturerProduct){

            $imageData = array();
            $productImages = $manufacturerProduct->getActiveImages();
            foreach($productImages as $image){
                $imageData[] = array(
                    'url'       => $this->assetHelper->getUrl($image->getImageLocation(), 'manufacturer_product'),
                    'imageId'   => $image->getManufacturerProductImageId(),
                    'isPrimary' => $image->getIsPrimary(),
                );
            }
            $productUnitsData = array();
            $productUnits = $manufacturerProduct->getActiveUnits();
            foreach($productUnits as $productUnit){
                $productUnitsData[] = array(
                    'combinationid' => $productUnit->getManufacturerProductUnitId(),
                    'sku'           => $productUnit->getSku(),
                    'originalPrice' => $productUnit->getPrice(),
                    'finalPrice'    => $productUnit->getDiscountedPrice(),
                    'discount'      => $productUnit->getDiscountPercentage(),
                    'inventory'     => $productUnit->getQuantity(),
                    'weight'        => $productUnit->getWeight(),
                    'height'        => $productUnit->getHeight(),
                    'length'        => $productUnit->getLength(),
                    'width'         => $productUnit->getWidth(),
                    'attributes'    => $productUnit->getCombinationString(),
                    'moq'           => $productUnit->getMoq(),
                    'name'          => '',
                 );
            }

            $productsData[] = array(
                'name'        => $manufacturerProduct->getName(),
                'referenceno' => $manufacturerProduct->getReferenceNumber(),
                'description' => $manufacturerProduct->getDescription(),
                'datecreated' => $manufacturerProduct->getDateAdded(),
                'images'      => $imageData,
                'combination'  => $productUnitsData,
            );
        }    

        if(count($productsData) > 0){
            $response['message'] = "API Endpoint is currently unavailable";
            try{
                $request = new FormRequest(
                    FormRequest::METHOD_POST, '/'.$this->configuration['routes']['create_products'], $this->configuration['baseurl']
                );
                $request->setFields($productsData);
                $buzzResponse = new Response();
                $client = new Curl();
                $client->setTimeout(self::API_TIMEOUT_SEC);
                $client->send($request, $buzzResponse);

                if ($buzzResponse->isSuccessful()) {
                    $content = json_decode($buzzResponse->getContent(), true);
                    $response['isSuccessful'] = true;
                    $response['message'] = "Product created";
                    $response['data'] = $content;
                }
            }
            catch(RequestException $e){
                $response['message'] = "API endpoint request has timeout";
            }
        }

        return $response;
    }
    
    /**
     * Cancel a package by order products
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\OrderProduct[] $orderProducts
     * @param string waybillNumber
     * @return mixed
     */
    public function cancelOrder($orderProducts, $waybillNumber = null)
    {
        $response = array(
            'message'      => 'Package cancellation is currently unavailable',
            'isSuccessful' => false,
            'data'         => array(),
        );

        $orderProductIds = array();
        foreach($orderProducts as $orderProduct){
            $orderProductIds[] = $orderProduct->getOrderProductId();
        }
        $packages = $this->entityManager->getRepository('YilinkerCoreBundle:Package')
                         ->getPackagesByOrderProducts($orderProductIds, false);

        if(count($packages) > 0){
            $cancelledPackageStatus = $this->entityManager->getReference('YilinkerCoreBundle:PackageStatus', PackageStatus::STATUS_CANCELLED);
            $criteria = Criteria::create()->where(Criteria::Expr()->neq('packageStatus', $cancelledPackageStatus));
            $packagesCollection = new Collections\ArrayCollection($packages);
            $package = $packagesCollection->matching($criteria)->first();
           
            if($waybillNumber !== null && $package->getWaybillNumber() !== $waybillNumber){
                $package = null;
            }
            
            if($package){
                $criteria = Criteria::create()->where(
                    Criteria::Expr()->notIn('orderProduct', is_array($orderProducts) ? $orderProducts : $orderProducts->getValues())
                );
                $remainingPackageDetails = $package->getPackageDetails()->matching($criteria);
                $remainingOrderProducts = array();
                foreach($remainingPackageDetails as $remainingPackageDetail){
                    $orderProduct = $remainingPackageDetail->getOrderProduct();
                    if(!$orderProduct->getIsCancellationApproved()){
                        $remainingOrderProducts[] = $orderProduct;
                    }
                }

                if(count($remainingOrderProducts) > 0){
                    /**
                     * Reschedule packages
                     */
                    $scheduleDatetime = new DateTime();
                    $response = $this->schedulePickup(
                        $scheduleDatetime, "Package cancellation", $remainingOrderProducts, null, $package->getWaybillNumber()
                    );
                }
                else{
                    try{                
                        $parameters = array(
                            'waybillNumber' => $package->getWaybillNumber(),
                        );

                        $url = $this->configuration['routes']['cancel_package'];
                        $request = new FormRequest(
                            FormRequest::METHOD_POST, '/'.$url, $this->configuration['baseurl']
                        );
                        $request->setFields($parameters);
                        $client = new Curl();
                        $buzzResponse = new Response();
                        $client->setTimeout(self::API_TIMEOUT_SEC);                    
                        $client->send($request, $buzzResponse);

                        if ($buzzResponse->isSuccessful()){
                            $apiResponse = json_decode($buzzResponse->getContent(), true);
                            $response['isSuccessful'] = $apiResponse['isSuccessful'];
                            $response['message'] = $apiResponse['message'];
                        }
                        else{
                            $response['message'] = 'API Endpoint is currently unavailable';
                        }
                    }
                    catch(RequestException $e){
                        $response['message'] = "Courier API has timeout";
                    }                    
                }
            }
            else{
                $response['message'] = "Package not found";
            }
        }
        else{
            $response['message'] = "Order products do not belong to the same package";
        }

        return $response;
    }

    public function triggerPackageUpdates($waybillNumber, \DateTime $waybillDate)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => "",
        );
        
        $url = $this->configuration['routes']['test_trigger_package_updates'];
        try{
            $request = new FormRequest(
                FormRequest::METHOD_POST, '/'.$url, $this->configuration['baseurl']
            );
            $request->setFields(array(
                'waybill'         => $waybillNumber,
                'transactionDate' => $waybillDate->format("Y-m-d H:i:s"),
            ));
            $client = new Curl();
            $buzzResponse = new Response();
            $client->setTimeout(self::API_TIMEOUT_SEC);                    
            $client->send($request, $buzzResponse);
            if ($buzzResponse->isSuccessful()){
                $apiResponse = json_decode($buzzResponse->getContent(), true);
                $response['isSuccessful'] = $apiResponse['isSuccessful'];
                $response['message'] = $apiResponse['message'];
            }
            else{
                $response['message'] = 'API Endpoint is currently unavailable';
            }
        }
        catch(RequestException $e){
            $response['message'] = "Courier API has timeout";
        }                    
        return $response;
    }

    /**
     * Update DateWaybillRequested
     *
     * @param array $orderProducts
     * @param Carbon $dateGenerated
     * @return bool
     */
    public function updateDateWaybillRequested (array $orderProducts, Carbon $dateGenerated)
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {

            foreach ($orderProducts as $orderProduct) {

                if ($orderProduct instanceof OrderProduct) {
                    $orderProduct->setDateWaybillRequested($dateGenerated);
                }

            }

            $isSuccessful = true;
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        }
        catch (\Exception $e) {
            $isSuccessful = false;
            $this->entityManager->getConnection()->rollback();
        }

        return $isSuccessful;
    }

    public function getProcessableStatuses()
    {
        return array(
            OrderProduct::WAYBILL_REQUEST_STATUS_READY_TO_PROCESS,
            OrderProduct::WAYBILL_REQUEST_STATUS_READY_TO_REPROCESS
        );
    }

    protected function getDefaultBarangay($consigneeLocationTree)
    {
        if (isset($consigneeLocationTree['city'])) {
            $location = $this->entityManager->getRepository('YilinkerCoreBundle:Location')->findOneBy(array(
                'parent' => $consigneeLocationTree['city']->getLocationId(),
                'location' => 'Others',
                'isActive' => 1,
                'locationType' => $this->entityManager->getReference('YilinkerCoreBundle:LocationType', LocationType::LOCATION_TYPE_BARANGAY)
            ));

            return $location ? $location->getLocationId() : 0;
        }

        return 0;
    }
}
