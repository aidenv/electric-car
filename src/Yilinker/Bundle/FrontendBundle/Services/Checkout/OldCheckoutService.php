<?php

namespace Yilinker\Bundle\FrontendBundle\Services\Checkout;

use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\CartItem;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\OrderHistory;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\OrderVoucher;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Yilinker\Bundle\CoreBundle\Entity\OrderConsigneeAddress;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidContactNumber;

class OldCheckoutService
{
    public $throwError = true;

    protected $rs;
    protected $em;
    protected $ts;
    protected $cartService;
    protected $express;
    protected $paymentConfig;
    protected $computeInShippingCategory;

    protected $request;
    protected $session;
    protected $user;

    public $voucherCode = null;
    public $api = false;
    protected $container;

    public function __construct($rs, $em, $ts, $cartService, $express, $paymentConfig, $computeInShippingCategory)
    {
        $this->rs = $rs;
        $this->em = $em;
        $this->ts = $ts;
        $this->cartService = $cartService;
        $this->express = $express;
        $this->paymentConfig = $paymentConfig;
        $this->computeInShippingCategory = $computeInShippingCategory;

        $this->request = $this->rs->getCurrentRequest();
        $this->session = $this->request->getSession();
        $this->user = $this->ts->getToken()->getUser();

        $this->cartService->apiMode(true);
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function buynow($productId, $unitId, $quantity)
    {
        $this->cartService->updateCart($productId, $unitId, $quantity);
        $itemId = null;
        if ($this->cartService->latestItem instanceof CartItem) {
            $itemId = $this->cartService->latestItem->getId();
        }
        elseif (is_array($this->cartService->latestItem) && array_key_exists('itemId', $this->cartService->latestItem)) {
            $itemId = $this->cartService->latestItem['itemId'];
        }

        if (!$itemId) {
            $cart = $this->cartService->getCart();
            foreach ($cart as $item) {
                if ($item['id'] == $productId && $item['unitId'] == $unitId) {
                    $itemId = $item['itemId'];
                }
            }
        }

        if ($itemId) {
            $this->setSelectedOnCart(array($itemId));
            return true;
        }
        else {
            return false;
        }
    }

    public function loadPreviousCheckoutSession()
    {
        $previousSession = $this->session->get('previousCheckout');
        $this->session->set('checkout', $previousSession);
    }

    public function clearSession()
    {
        if (!($this->ts->getToken()->getUser() instanceof User)) {
            $previousCheckout = $this->session->get('checkout');
            $this->session->set('previousCheckout', $previousCheckout);
            $this->session->remove('checkout');
        }
    }

    public function merchantRefToUserId($merchantRef)
    {
        $userId = 0;
        foreach ($this->paymentConfig as $paymentConfig) {
            $userId = str_replace($paymentConfig['merchantPrefixRef'], '', $merchantRef);
        }

        return $userId;
    }

    public function getUserOrder($merchantRef)
    {
        $user = $this->getCheckoutUser();
        $orderId = $this->merchantRefToUserId($merchantRef);
        if (!($orderId > 0) && $this->throwError) {
            throw new YilinkerException('Invalid merchant reference'.($merchantRef ? " ($merchantRef)" : ''));
        }
        $tbUserOrder = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $userOrder = $tbUserOrder->findOneBy(array(
            'orderId'   => $orderId,
            'buyer'     => $user->getUserId()
        ));
        if (!$userOrder && $this->throwError) {
            throw new YilinkerException('You have no order with reference'.($merchantRef ? " ($merchantRef)" : ''));
        }

        return $userOrder;
    }

    public function paymentFailed($merchantRef)
    {
        $userOrder = $this->getUserOrder($merchantRef);
        if ($userOrder) {
            $orderStatus = $this->em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_FAILED);
            $userOrder->setOrderStatus($orderStatus);
            $this->em->flush();
        }
    }

    public function saveGuestUser($user)
    {
        $user->setPlainPassword('');
        $user->setUserType(User::USER_TYPE_GUEST);
        $this->em->persist($user);
        $this->em->flush();
        $this->session->set('checkout/userId', $user->getId());

        return $user;
    }

    /**
     * Coule be a guest user or the logined user
     */
    public function getCheckoutUser()
    {
        $user = null;
        $userId = $this->session->get('checkout/userId');
        if ($userId) {
            $tbUser = $this->em->getRepository('YilinkerCoreBundle:User');
            $user = $tbUser->find($userId);
        }
        if (!$user && ($this->user instanceof User)) {
            $user = $this->user;
            $this->session->set('checkout/userId', $user->getUserId());
        }

        if (!$user && $this->throwError) {
            throw new YilinkerException('You cannot be identified. Please sign in or use the guest checkout.');
        }
        
        return $user;
    }

    public function addAddress($address)
    {
        $user = $this->getCheckoutUser();
        $addresses = $user->getAddresses();

        if(
            $user->getUserType() != User::USER_TYPE_GUEST
            ||
            (
                $user->getUserType() == User::USER_TYPE_GUEST && 
                $addresses->count() <= 1
            )
        ){

        if($addresses->count() < 1){
            $address->setIsDefault(true);
        }

            $address->setUser($user);
            $this->em->persist($address);
            $this->em->flush();
        }
    }

    public function setDeliveryAddress($addressId)
    {
        $user = $this->getCheckoutUser();
        // if (!$user->getIsMobileVerified()) {
        //     throw new YilinkerException('Mobile verification is required');
        // }
        
        $tbUserAddress = $this->em->getRepository('YilinkerCoreBundle:UserAddress');
        $userAddress = $tbUserAddress->getAddressOfUser($user->getId(), $addressId);
        if (!$userAddress && $this->throwError) {
            throw new YilinkerException('User Address cannot be found.');
        }
        $this->session->set('checkout/userAddressId', $addressId);

        return $userAddress;
    }

    public function getDeliveryAddress()
    {
        $addressId = $this->session->get('checkout/userAddressId');
        if ($addressId > 0) {
            $user = $this->getCheckoutUser();
            $tbUserAddress = $this->em->getRepository('YilinkerCoreBundle:UserAddress');
            $userAddress = $tbUserAddress->getAddressOfUser($user->getId(), $addressId);

            if ($userAddress) {
                return $userAddress;
            }
        }
        $user = $this->getCheckoutUser();
        $defaultAddress = $user->getDefaultAddress();
        if (!$defaultAddress) {
            $defaultAddress = $user->getAddresses()->first();
        }

        if ($defaultAddress) {
            return $defaultAddress;
        }

        if ($this->throwError) {
            throw new YilinkerException('You have not selected a delivery address');
        }

        return null;
    }

    public function cartSessionToDB()
    {
        $cartSession = $this->cartService->cartSessionToDB();
        $cartItems = $this->session->get('checkout/cartItems');
        $this->session->remove('checkout');

        $dbCartItems = array();
        if ($cartItems) {
            foreach ($cartItems as $itemId) {
                if (array_key_exists($itemId, $cartSession) && $cartSession[$itemId]) {
                    $dbCartItems[] = $cartSession[$itemId]->getId();
                }
            }
        }

        $this->setSelectedOnCart($dbCartItems);
    }

    public function setSelectedOnCart($cartItems)
    {
        $this->cartService->refreshCart();
        $this->session->set('checkout/cartItems', $cartItems);
    }

    public function getSelectedOnCart()
    {
        $cart = $this->cartService->getCart();
        $selectedItems = $this->session->get('checkout/cartItems');
        $selectedCart = array();

        if (is_array($selectedItems) && $selectedItems) {
            foreach ($cart as $product) {
                if (in_array($product['itemId'], $selectedItems)) {
                    $selectedCart[] = $product;
                }
            }
        }
        else {
            $selectedCart = $cart;
        }

        if (!$selectedCart && $this->throwError) {
            throw new YilinkerException('There are no items to checkout');
        }

        return $selectedCart;
    }

    public function getSelectedOnCartBySeller()
    {
        $cart = $this->getSelectedOnCart();
        $cartBySeller = array();
        foreach ($cart as $product) {
            $cartBySeller[$product['sellerId']][] = $product;
        }

        return $cartBySeller;
    }

    public function makeOrderProducts($userOrder, $cart)
    {
        $tbProductUnit = $this->em->getRepository('YilinkerCoreBundle:ProductUnit');
        $tbProduct = $this->em->getRepository('YilinkerCoreBundle:Product');

        $orderTotalPrice = 0;
        $orderTotalHandlingFee = 0;
        $orderTotalYilinkerCharge = 0;
        $orderTotalAdditionalCharge = 0;

        foreach ($cart as $product) {
            $productUnit = $tbProductUnit->find($product['unitId']);
            if (!$productUnit) {
                continue;
            }
            $productObj = $tbProduct->find($product['id']);
            $productImage = $productUnit->getPrimaryProductImage();
            if (!$productImage) {
                $productImage = $productObj->getPrimaryImage();
            }

            $origPrice = $product['productUnits'][$product['unitId']]['price'];
            $origPrice = $origPrice ? $origPrice: 0;
            $unitPrice = $product['productUnits'][$product['unitId']]['discountedPrice'];
            $totalPrice = $product['quantity'] * $unitPrice;

            $brand = $this->em->getReference('YilinkerCoreBundle:Brand', $product['brandId']);
            $productCategory = $this->em->getReference('YilinkerCoreBundle:ProductCategory', $product['productCategoryId']);
            $sku = $productUnit->getSku();

            $callableClass = $productCategory;
            if (filter_var($this->computeInShippingCategory, FILTER_VALIDATE_BOOLEAN)
                && $productObj->getShippingCategory()) {
                $country = $this->em->getRepository('YilinkerCoreBundle:Country')->findByCode(Country::COUNTRY_CODE_PHILIPPINES);
                $shippingCategoryCountry = $this->em->getRepository('YilinkerCoreBundle:ShippingCategoryCountry')
                                                    ->findOneBy(array(
                                                        'country' => $country,
                                                        'shippingCategory' => $productObj->getShippingCategory()
                                                    ));
                if ($shippingCategoryCountry) {
                    $callableClass = $shippingCategoryCountry;
                }
            }

            $handlingFee = ProductCategory::SHIPPING_FEE_COMPUTE_AS_PERCENTAGE
                           ? bcmul($totalPrice, bcdiv((float) $callableClass->getHandlingFee(), 100, 4), 4)
                           : bcmul((float) $callableClass->getHandlingFee(), $product['quantity'], 4);

            $yilinkerCharge = ProductCategory::YILINKER_CHARGE_COMPUTE_AS_PERCENTAGE
                            ? bcmul($totalPrice, bcdiv((float) $callableClass->getYilinkerCharge(), 100, 4), 4)
                            : bcmul((float) $callableClass->getYilinkerCharge(), $product['quantity'], 4);

            $additionalCharge = ProductCategory::ADDITIONAL_COST_COMPUTE_AS_PERCENTAGE
                            ? bcmul($totalPrice, bcdiv((float) $callableClass->getAdditionalCost(), 100, 4), 4)
                            : bcmul((float) $callableClass->getAdditionalCost(), $product['quantity'], 4);

            $orderTotalPrice += $totalPrice;
            $orderTotalHandlingFee += $handlingFee;
            $orderTotalYilinkerCharge += $yilinkerCharge;
            $orderTotalAdditionalCharge += $additionalCharge;

            $attributes = $this->cartService->getProductAttributes($product);

            $orderProduct = new OrderProduct;
            $orderProduct->setProduct($productObj);
            $orderProduct->setSeller($productObj->getUser());
            $orderProduct->setBrand($brand);
            $orderProduct->setCondition($productObj->getCondition());
            $orderProduct->setProductCategory($productCategory);
            $orderProduct->setAttributes(json_encode($attributes));
            $orderProduct->setQuantity($product['quantity']);
            $orderProduct->setUnitPrice($unitPrice);
            $orderProduct->setOrigPrice($origPrice);
            $orderProduct->setTotalPrice($totalPrice);
            $orderProduct->setProductName($product['title']);
            $orderProduct->setOrder($userOrder);
            $orderProduct->setSku($sku);
            $orderProduct->setWeight($productUnit->getWeight());
            $orderProduct->setHeight($productUnit->getHeight());
            $orderProduct->setWidth($productUnit->getWidth());
            $orderProduct->setLength($productUnit->getLength());
            $orderProduct->setDescription($productObj->getDescription());
            $orderProduct->setShortDescription($productObj->getShortDescription());
            $orderProduct->setBrandName($productObj->getBrandName());
            $orderProduct->setIsNotShippable($productObj->getIsNotShippable());

            // set charges
            $orderProduct->setHandlingFee($handlingFee);
            $orderProduct->setYilinkerCharge($yilinkerCharge);
            $orderProduct->setAdditionalCost($additionalCharge);

            if($productUnit->isInhouseProductUnit()){
                $orderProduct->setCommission(
                    $productUnit->getCommission()
                );
            }

            if($productImage){
                $orderProduct->setImage($productImage);
            }

            if($productObj->isInhouseProduct()){
                $orderProduct->setManufacturerProductReference($productObj->getReferenceNumber());
            }

            $this->em->persist($orderProduct);
            $userOrder->addOrderProduct($orderProduct);
        }

        $userOrder->setTotalPrice($orderTotalPrice)
                  ->setHandlingFee($orderTotalHandlingFee)
                  ->setYilinkerCharge($orderTotalYilinkerCharge)
                  ->setAdditionalCost($orderTotalAdditionalCharge);

        $tbUserOrder = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $tbUserOrder->checkIfFlagged($userOrder);

        return $userOrder;
    }

    public function clearSelectedOnCart()
    {
        $cart = $this->getSelectedOnCart();
        foreach ($cart as $product) {
            $this->cartService->updateCart($product['id'], $product['unitId'], 0, $product['itemId'], $product['sellerId']);
        }
        $this->session->set('checkout/cartItems', array());
    }

    public function checkCartIntegrity()
    {
        $selectedOnCart = $this->getSelectedOnCart();
        $this->cartService->refreshCart();
        $selectedOnCartChanges = $this->getSelectedOnCart();
        if ($selectedOnCart != $selectedOnCartChanges) {
            $changes = array();
            foreach ($selectedOnCartChanges as $selectedOnCartChange) {
                $changes[$selectedOnCartChange['unitId']] = $selectedOnCartChange;
            }
            $message = '';
            foreach ($selectedOnCart as $selectedOnCartItem) {
                if (array_key_exists($selectedOnCartItem['unitId'], $changes)) {
                    $quantity = $changes[$selectedOnCartItem['unitId']]['quantity'];
                    if ($quantity != $selectedOnCartItem['quantity']) {
                        $message .= '<br>You tried to order x'.$selectedOnCartItem['quantity'].' of `'.$selectedOnCartItem['title'].'` but stock has only x'.$quantity;
                    }
                }
                else {
                    $message .= '<br>`'.$selectedOnCartItem['title'].'` is either out of stock or inactive';
                }
            }
            throw new YilinkerException($message);
        }
    }

    public function clearConsignee()
    {
        $this->session->remove('checkout/consigneeName');
        $this->session->remove('checkout/consigneeContactNumber');
    }

    public function catchConsignee($validate = false)
    {
        $consigneeName = $this->request->get("consigneeName", null);
        $consigneeName = trim($consigneeName);
        if ($consigneeName) {
            $this->session->set('checkout/consigneeName', $consigneeName);
        }
        else {
            $consigneeName = $this->session->get('checkout/consigneeName', null);
            if (!$consigneeName) {
                $user = $this->getCheckoutUser();
                $consigneeName = $user->getFullName();
                $consigneeName = trim($consigneeName);
                $this->session->set('checkout/consigneeName', $consigneeName);
            }
        }

        $consigneeContactNumber = $this->request->get("consigneeContactNumber", null);
        if ($consigneeContactNumber) {
            $this->session->set('checkout/consigneeContactNumber', $consigneeContactNumber);
        }
        else {
            $consigneeContactNumber = $this->session->get('checkout/consigneeContactNumber', null);
            if (!$consigneeContactNumber) {
                $user = $this->getCheckoutUser();
                $consigneeContactNumber = $user->getContactNumber();
                $this->session->set('checkout/consigneeContactNumber', $consigneeContactNumber);
            }
        }
        if ($validate && !$consigneeName) {
            throw new YilinkerException('Consignee name is required.');
        }
        if ($validate) {
            if (!$consigneeContactNumber) {
                throw new YilinkerException('Consignee number is required.');
            }
            else {
                $validator = $this->container->get('validator');
                $constraint = new ValidContactNumber;
                $error = $validator->validate($consigneeContactNumber, $constraint);
                $error = $error->getIterator()->current();
                if ($error) {
                    $message = $error->getMessage();
                    if ($message) {
                        throw new YilinkerException($message);
                    }
                }
            }
        }

        return compact(
            'consigneeName',
            'consigneeContactNumber'
        );
    }

    public function makeUserOrder($paymentType = PaymentMethod::PAYMENT_METHOD_COD)
    {
        $user = $this->getCheckoutUser();
        $cart = $this->getSelectedOnCart();
        $address = $this->getDeliveryAddress();
        $consignee = $this->catchConsignee(true);
        extract($consignee);

        $this->checkCartIntegrity();

        $paymentMethod =  $this->em->getReference('YilinkerCoreBundle:PaymentMethod', $paymentType);

        $userOrder = new UserOrder;
        $userOrder->setBuyer($user);
        $userOrder->setConsigneeName($consigneeName);
        $userOrder->setConsigneeContactNumber($consigneeContactNumber);
        $userOrder->setAddress($address->getAddressString());
        $userOrder->setConsigneeFirstName($user->getFirstName());
        $userOrder->setConsigneeLastName($user->getLastName());
        $userOrder->setConsigneeLocation($address->getLocation());
        $userOrder->setConsigneeLatitude($address->getLatitude());        
        $userOrder->setConsigneeLongitude($address->getLongitude());

        $userOrder->setIpAddress($this->request->getClientIp());
        $userOrder->setPaymentMethod($paymentMethod);

        if ($this->api) {
            $userOrder->setCheckoutType(UserOrder::CHECKOUT_TYPE_MOBILE);
        }
        
        /**
         * Create user order address data
         */
        $orderConsigneeAddress = new OrderConsigneeAddress();
        $orderConsigneeAddress->setLocation($address->getLocation());
        $orderConsigneeAddress->setDateAdded(new \DateTime());
        $orderConsigneeAddress->setStreetAddress($address->getStreetAddress());
        $orderConsigneeAddress->setLongitude($address->getLongitude());
        $orderConsigneeAddress->setLatitude($address->getLatitude());
        $orderConsigneeAddress->setLandline($address->getLandline());
        $orderConsigneeAddress->setUnitNumber($address->getUnitNumber());
        $orderConsigneeAddress->setBuildingName($address->getBuildingName());
        $orderConsigneeAddress->setStreetNumber($address->getStreetNumber());
        $orderConsigneeAddress->setStreetName($address->getStreetName());
        $orderConsigneeAddress->setSubdivision($address->getSubdivision());
        $orderConsigneeAddress->setZipCode($address->getZipCode());
        $orderConsigneeAddress->setTitle($address->getTitle());
        
        $userOrder->setOrderConsigneeAddress($orderConsigneeAddress);
        $this->em->persist($orderConsigneeAddress);
        $this->em->persist($userOrder);
        $userOrder = $this->makeOrderProducts($userOrder, $cart);
        $this->calculatePaymentMethodCharge($userOrder);
        if (!$this->voucherCode) {
            $this->voucherCode = $this->session->get('voucherCode');
        }
        $this->applyVoucherCode($userOrder, $this->voucherCode);

        $this->em->flush();
        $this->voucherCode = null;
        $this->session->remove('voucherCode');

        return $userOrder;
    }

    public function calculatePaymentMethodCharge($userOrder)
    {
        $paymentCharge = 0.00;
        if ($userOrder->getPaymentMethod()->getPaymentMethodId() == PaymentMethod::PAYMENT_METHOD_DRAGONPAY) {
            $paymentCharge = 20.00;
        }
        elseif ($userOrder->getPaymentMethod()->getPaymentMethodId() == PaymentMethod::PAYMENT_METHOD_PESOPAY) {
            $paymentCharge = 1.12 * (6.00 + (0.0375 * $userOrder->getTotalPrice()));
        }
        $userOrder->setPaymentMethodCharge($paymentCharge);

        $totalPrice = $userOrder->getTotalPrice();
        if ($totalPrice > 0.0) {
            foreach ($userOrder->getOrderProducts() as $orderProduct) {
                $orderProductPaymentCharge = 0.0;
                $orderProductPrice = $orderProduct->getTotalPrice();

                $percentage = $orderProductPrice / $totalPrice;
                $orderProductPaymentCharge = $percentage * $paymentCharge;
                $orderProduct->setPaymentMethodCharge($orderProductPaymentCharge);
            }
        }
    }

    public function paymentCOD()
    {
        $userOrder = $this->makeUserOrder();
        $this->clearSelectedOnCart();

        return $userOrder;
    }

    public function paymentPesopay()
    {
        $pesopayConfig = $this->paymentConfig['pesopay'];

        $userOrder = $this->makeUserOrder(PaymentMethod::PAYMENT_METHOD_PESOPAY);
        if ($userOrder->getTotalPrice() < 20) {
            throw new YilinkerException('A minimum amount of P 20.00 is needed when using Pesopay');
        }

        $params = array(
            'amount'    => $userOrder->getTotalPrice(),
            'orderRef'  => $userOrder->getOrderId(),
            'remark'    => $pesopayConfig['clientname'],
        );

        $defaultParams = $pesopayConfig['params'];
        $params = array_merge($defaultParams, $params);
        $params['orderRef'] = $pesopayConfig['merchantPrefixRef'].$params['orderRef'];
        $params['amount'] = number_format(round($params['amount'], 2), 2, '.', '');

        $data = array(
            $params['merchantId'],
            $params['orderRef'],
            $params['currCode'],
            $params['amount'],
            $params['payType'],
            $pesopayConfig['secureHash']
        );
        $params['secureHash'] = sha1(implode('|', $data));
        $url = $pesopayConfig['url'].'?'.http_build_query($params);

        return $url;
    }

    public function isPesopayPostback()
    {
        $pesopayConfig = $this->paymentConfig['pesopay'];

        $data = [
            $this->request->get('src', ''),
            $this->request->get('prc', ''),
            $this->request->get('successcode'),
            $this->request->get('Ref'),
            $this->request->get('PayRef'),
            $this->request->get('Cur'),
            $this->request->get('Amt'),
            $this->request->get('payerAuth'),
            $pesopayConfig['secureHash']
        ];

        $secureHash = (string) sha1(implode('|', $data));

        return $this->request->get('secureHash') === (string) $secureHash;
    }

    public function postbackPesopay()
    {
        if (!$this->isPesopayPostback()) {
            return false;
        }
        $status = $this->request->get('successcode');
        //successcode equals zero is payment success other values means error
        $isSuccess = $status == 0;
        $isFail = $status != 0;
        if (!$isSuccess && !$isFail) {
            return false;
        }

        $pesopayConfig = $this->paymentConfig['pesopay'];
        $merchantRef = $this->request->get('Ref');
        $userOrderId = str_replace($pesopayConfig['merchantPrefixRef'], '', $merchantRef);
        $tbUserOrder = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $userOrder = $tbUserOrder->find($userOrderId);

        if (!$userOrder) {
            return false;
        }

        if ($isSuccess) {
            $orderStatus = $this->em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_CONFIRMED);
            if ($userOrder->isRejectedForFraud()) {
                $orderHistory = new OrderHistory;
                $orderHistory->setOrderStatus($orderStatus);
                $orderHistory->setOrder($userOrder);
                $this->em->persist($orderHistory);
            }
            else {
                $userOrder->setOrderStatus($orderStatus);
            }
        }
        elseif ($isFail) {
            $orderStatus = $this->em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_FAILED);
            $userOrder->setOrderStatus($orderStatus);
        }
        $this->em->flush();

        return true;
    }

    public function paymentDragonpay()
    {
        $dragonpayConfig = $this->paymentConfig['dragonpay'];
        $userOrder = $this->makeUserOrder(PaymentMethod::PAYMENT_METHOD_DRAGONPAY);

        if ($userOrder->getTotalPrice() < 20) {
            throw new YilinkerException('A minimum amount of P 20.00 is needed when using Dragonpay');
        }
        
        $params = array(
            'amount'    => $userOrder->getTotalPrice(),
            'txnid'     => $userOrder->getOrderId(),
            'email'     => $userOrder->getBuyer()->getEmail(),
            'param1'    => $dragonpayConfig['clientname'],
        );
        
        $defaultParams = $dragonpayConfig['params'];
        $params = array_merge($defaultParams, $params);
        //dragon pay is strict with the amount having two decimal places
        $params['amount'] = number_format(round($params['amount'], 2), 2, '.', '');
        $params['txnid'] = $dragonpayConfig['merchantPrefixRef'].$params['txnid'];

        $data = array(
            $params['merchantid'],
            $params['txnid'],
            $params['amount'],
            $params['ccy'],
            $params['description'],
            $params['email'],
            $dragonpayConfig['password']
        );

        $digest = sha1(implode(':', $data));
        $params['digest'] = $digest;
        $url = $dragonpayConfig['url'].'?'.http_build_query($params);

        return $url;
    }

    public function isDragonpayPostback()
    {
        $dragonpayConfig = $this->paymentConfig['dragonpay'];

        $data = [
            urldecode($this->request->get('txnid')),
            urldecode($this->request->get('refno')),
            urldecode($this->request->get('status')),
            urldecode($this->request->get('message')),
            $dragonpayConfig['password'],
        ];

        $digest = (string) sha1(implode(":", $data));

        return $this->request->get('digest') == $digest;
    }

    public function postbackDragonpay()
    {
        if (!$this->isDragonpayPostback()) {
            return false;
        }
        $status = $this->request->get('status');
        if (!$status) {
            return false;
        }
        $isSuccess = strtolower($status) == 's';
        $isFail = strtolower($status) == 'f';

        $dragonpayConfig = $this->paymentConfig['dragonpay'];
        $merchantRef = $this->request->get('txnid');
        $userOrderId = str_replace($dragonpayConfig['merchantPrefixRef'], '', $merchantRef);
        $tbUserOrder = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $userOrder = $tbUserOrder->find($userOrderId);
        if (!$userOrder) {
            return false;
        }
        $userOrder->setPaymentMethodStatus($status);

        if (!$isSuccess && !$isFail) {
            $this->em->flush();
            return false;
        }

        if ($isSuccess) {
            $orderStatus = $this->em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_CONFIRMED);
            if ($userOrder->isRejectedForFraud()) {
                $orderHistory = new OrderHistory;
                $orderHistory->setOrderStatus($orderStatus);
                $orderHistory->setOrder($userOrder);
                $this->em->persist($orderHistory);
            }
            else {
                $userOrder->setOrderStatus($orderStatus);
            }
        }
        elseif ($isFail) {
            $orderStatus = $this->em->getReference('YilinkerCoreBundle:OrderStatus', OrderStatus::PAYMENT_FAILED);
            $userOrder->setOrderStatus($orderStatus);
        }
        $this->em->flush();

        return true;
    }

    public function getActiveVoucherCode($code, $number_format = false, $entity = true)
    {
        try {
            $user = $this->getCheckoutUser(); 
        } catch (YilinkerException $e) {
            $user = null;
        };

        $tbVoucherCode = $this->em->getRepository('YilinkerCoreBundle:VoucherCode');
        $voucherCode = $tbVoucherCode->getActiveVoucherCode($code, $user);
        $voucher = $voucherCode ? $voucherCode->getVoucher(): null;

        $data = array();
        if ($voucher && $voucher->getMinimumPurchase()) {
            $cart = $this->getSelectedOnCart();
            $totalPrice = 0;
            foreach ($cart as $product) {
                $price = $product['productUnits'][$product['unitId']]['discountedPrice'];
                $totalPrice += $price * $product['quantity'];
            }

            $minimumPurchase = $voucher->getMinimumPurchase();
            if ($minimumPurchase > $totalPrice) {
                $minimumPurchase = number_format($minimumPurchase, 2);

                throw new YilinkerException('Voucher has a minimum purchase of P '.$minimumPurchase);
            }

            $less = 0;
            if ($voucher->isDiscountFixedAmount()) {
                $less = $voucher->getValue();
            }
            elseif ($voucher->isDiscountPercentage()) {
                $less = $totalPrice * ($voucher->getValue() / 100);
            }

            $data['less'] = $number_format ? number_format($less, 2): $less;
            $data['origPrice'] = $number_format ? number_format($totalPrice, 2): $totalPrice;
            $totalPrice -= $less;
            $data['voucherPrice'] = $number_format ? number_format($totalPrice, 2): $totalPrice;
        }

        return $entity ? $voucherCode: $data;
    }

    public function applyVoucherCode($userOrder, $code)
    {
        if (!$code) {
            return;
        }

        $voucherCode = $this->getActiveVoucherCode($code);
        if ($voucherCode) {
            $orderVoucher = new OrderVoucher;
            $orderVoucher->setOrder($userOrder);
            $orderVoucher->setVoucherCode($voucherCode);
            $this->em->persist($orderVoucher);
        }
    }

    public function continueShoppingURL($userOrder)
    {
        $router = $this->container->get('router');
        $url = $router->generate('home_page');

        if (!$userOrder) {
            return $url;
        }

        $orderProducts = $userOrder->getOrderProducts();
        $mostExpensiveOrderProduct = null;
        $highestBasePrice = 0;
        foreach ($orderProducts as $orderProduct) {
            $origPrice = $orderProduct->getOrigPrice();
            if ($origPrice > $highestBasePrice) {
                $mostExpensiveOrderProduct = $orderProduct;
                $highestBasePrice = $origPrice;
            }
        }

        if ($mostExpensiveOrderProduct) {
            try {
                $category = $mostExpensiveOrderProduct->getProduct()->getProductCategory()->getParent();
                if ($category->getParent() && $category->getProductCategoryId() == $category->getParent()->getProductCategoryId()) {
                    $url = $router->generate('all_categories');
                }
                else {
                    $url = $router->generate('get_category', array('slug' => $category->getSlug()));
                }
            } catch (Exception $e) {}
        }

        return $url;
    }

    /**
     * @param   string  $voucherCode
     * @param   boolean $number_format  - whether to apply number formatting to the returned prices
     *
     * @return  mixed   false|array('less' => $less, 'origPrice' => $totalPrice, 'voucherPrice' => $totalPrice - $less)
     */
    public function setVoucherCode($code, $number_format = false)
    {
        $this->session->set('voucherCode', $code);
        $data = $this->getVoucherData($number_format);
        if (!$data) {
            $this->session->remove('voucherCode');
        }

        return $data;
    }

    public function getVoucherData($number_format = false)
    {
        $code = $this->session->get('voucherCode');
        $voucherCode = $this->getActiveVoucherCode($code);
        $data = array();
        if (!$voucherCode) {
            return $data;
        }

        $voucher = $voucherCode->getVoucher();
        if (!$voucher) {
            return $data;
        }

        $cart = $this->getSelectedOnCart();

        $totalPrice = 0;
        foreach ($cart as $product) {
            $unitPrice = $product['productUnits'][$product['unitId']]['discountedPrice'];
            $totalPrice += $product['quantity'] * $unitPrice;
        }

        $less = 0;
        if ($voucher->isDiscountFixedAmount()) {
            $less = $voucher->getValue();
        }
        elseif ($voucher->isDiscountPercentage()) {
            $less = $totalPrice * ($voucher->getValue() / 100);
        }

        $data['origPrice'] = $number_format ? number_format($totalPrice, 2): $totalPrice;
        $totalPrice -= $less;
        $data['voucherPrice'] = $number_format ? number_format($totalPrice, 2): $totalPrice;
        if ($data['voucherPrice'] < 1) {
            $data['voucherPrice'] = 1;
            if ($less) {
                $less--;
            }
        }
        $data['less'] = $number_format ? number_format($less, 2): $less;

        return $data;
    }
}
