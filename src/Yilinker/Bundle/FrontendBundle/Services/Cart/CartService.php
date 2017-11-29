<?php

namespace Yilinker\Bundle\FrontendBundle\Services\Cart;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Yilinker\Bundle\CoreBundle\Entity\Cart;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\FrontendBundle\Services\Product\ProductService;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;

class CartService
{
    const API = 'api';

    protected $requestStack;
    protected $entityManager;
    protected $request;
    protected $session;
    protected $mode;
    protected $tokenStorage;
    protected $user;
    protected $productService;
    public $latestItem;

    public function __construct(RequestStack $requestStack, EntityManager $entityManager, TokenStorage $tokenStorage, ProductService $productService)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->request = $this->requestStack->getCurrentRequest();
        $this->session = $this->request->getSession();
        $this->tokenStorage = $tokenStorage;
        $this->productService = $productService;
        $this->user = $tokenStorage->getToken() ? $tokenStorage->getToken()->getUser(): null;
        $this->user = ($this->user instanceof User) ? $this->user : null;
        $this->tbCart = $this->entityManager->getRepository('YilinkerCoreBundle:Cart');
        $this->tbCartItem = $this->entityManager->getRepository('YilinkerCoreBundle:CartItem');
        $this->tbProduct = $this->entityManager->getRepository('YilinkerCoreBundle:Product');
        $this->tbProductUnit = $this->entityManager->getRepository('YilinkerCoreBundle:ProductUnit');
    }

    public function apiMode($switch)
    {
        $this->mode = $switch ? self::API : null;

        return $this;
    }

    public function updateCartSession($productId = null, $unitId = null, $quantity = null, $itemId = 0, $sellerId = null)
    {
        $productId = $productId ? $productId : $this->request->get('productId');
        $unitId = $unitId ? $unitId : $this->request->get('unitId');
        $sellerId = $sellerId ? $sellerId : $this->request->get('sellerId');
        $quantity = $quantity ? $quantity : $this->request->get('quantity', 0);
        $quantity = $this->tbProductUnit->trueQuantity($unitId, $quantity);
        if (!$itemId) {
            $itemId = strtotime('now');
        }
        $updateItem = compact('productId', 'unitId', 'sellerId', 'quantity');

        $cart = $this->session->get('cart', array());
        $target = null;
        $oldTarget = null;
        foreach ($cart as $product) {
            if ($product['productId'] == $productId && $product['unitId'] == $unitId) {
                $target = $product;
            }
            elseif ($product['itemId'] == $itemId) {
                $oldTarget = $product;
            }
        }

        $spliceKey = null;
        $oldTargetSpliceKey = null;
        foreach ($cart as $key => &$product) {
            if (($product == $target)) {
                if (is_null($oldTarget)) {
                    $product['productId'] = $productId;
                    $product['unitId'] = $unitId;
                    $product['sellerId'] = $sellerId;
                    $product['quantity'] = $quantity;
                }
                else {
                    $product['quantity'] += $oldTarget['quantity'];
                    $product['quantity'] = $this->tbProductUnit->trueQuantity($unitId, $product['quantity']);
                }
                $spliceKey = $key;
            }
            if ($oldTarget == $product) {
                if (is_null($target)) {
                    $product['productId'] = $productId;
                    $product['unitId'] = $unitId;
                    $product['sellerId'] = $sellerId;
                    $product['quantity'] = $quantity;
                }
                else {
                    $oldTargetSpliceKey = $key;
                }
                $spliceKey = $key;
            }
        }
        if (is_null($target) && is_null($oldTarget) && $quantity) {
            $updateItem['itemId'] = $itemId;
            $cart[] = $updateItem;
            $this->latestItem = $updateItem;
        }
        if (!$quantity) {
            array_splice($cart, $spliceKey, 1);
        }
        if ($oldTargetSpliceKey > -1) {
            array_splice($cart, $oldTargetSpliceKey, 1);
        }

        $this->session->set('cart', $cart);
    }

    public function getCartSession()
    {
        $products = array();
        $cart = $this->session->get('cart', array());

        foreach ($cart as $item) {
            $this->tbProduct->qb()->andWhere("this.productId = ".$item['productId']);
            if ($item['unitId']) {
                $this->tbProduct
                     ->innerJoin('this.units', 'units')
                     ->andWhere('units.productUnitId = :unitId')
                     ->setParameter('unitId', $item['unitId']);
            }
            $product = $this->tbProduct->getQB()->getQuery()->getResult();
            $product = array_shift($product);
            if (!$product) continue;
            if ($this->mode == self::API) {
                $product = $product->getDetails(true, false);

                if (is_array($product)) {
                    $product['itemId'] = $item['itemId'];
                    $product['unitId'] = $item['unitId'];
                    $product['sellerId'] = $item['sellerId'];
                    $product['quantity'] = $item['quantity'];
                    $product['shippingCost'] = $product['quantity'] * $product['shippingCost'];
                }
                elseif ($product instanceof Product) {
                    $product->itemId = $item['itemId'];
                    $product->unitId = $item['unitId'];
                    $product->sellerId = $item['sellerId'];
                    $product->quantity = $item['quantity'];
                }
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param bool $metadata
     * @return array $cartProducts[] Yilinker\Bundle\CoreBundle\Entity\Product
     */
    public function getCart($metadata = false)
    {
        if (!$this->user) {
            $cartProducts = $this->getCartSession();
        }
        else {
            $cart = $this->tbCart->getActiveCartOfUser($this->user->getUserId());
            $cartProducts = $cart ? $this->tbProduct->fromCart($cart, ($this->mode == self::API)) : array();
        }

        foreach ($cartProducts as $key => $cartProduct){
            $filteredUnits = array();
            foreach($cartProduct['productUnits'] as $index=>$unit){
                if(
                    // $unit['status'] == ProductUnit::STATUS_ACTIVE &&
                    $unit['quantity'] > 0
                ){
                    $filteredUnits[$index] = $unit;
                }
            }

            if(!empty($filteredUnits)){
                $cartProducts[$key]['productUnits'] = $filteredUnits;
            }
            else{
                unset($cartProducts[$key]);
            }
        }

        $this->applyChangeOnBulkPromo($cartProducts);

        if ($metadata) {
            return $this->addMetaData($cartProducts);
        }

        return $cartProducts;
    }

    public function updateCart($productId, $unitId, $quantity, $itemId = 0, $sellerId = null, $retain = false, $user = null)
    {
        $user = $user ? $user: $this->user;
        if (!$user) {
            return $this->updateCartSession($productId, $unitId, $quantity, $itemId, $sellerId);
        }

        if ($this->session) {
            $this->session->remove('voucherCode');
        }
        
        $quantity = $this->tbProductUnit->trueQuantity($unitId, $quantity);

        $cart = $this->tbCart->getActiveCartOfUser($user->getUserId());

        $addCartItem = false; 
        if (!$cart) {
            $cart = new Cart;
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
            $addCartItem = true;
        }
        $cartItem = $this->tbCartItem->updateItem(
            $cart,
            $productId,
            $unitId,
            $quantity,
            $addCartItem,
            $itemId,
            $retain,
            $sellerId
        );
        $this->latestItem = $cartItem;
    }

    public function refreshCart()
    {
        $cart = $this->getCart();
        if ($this->session) {
            $voucherCode = $this->session->get('voucherCode');
        }

        foreach ($cart as $item) {
            $quantity = 0;
            if ($item['status'] == Product::ACTIVE && array_key_exists($item['unitId'], $item['productUnits'])) {
                $productUnit = $item['productUnits'][$item['unitId']];
                // if ($productUnit['status'] == ProductUnit::STATUS_ACTIVE) {
                    $quantity = $item['quantity'];
                // }
            }

            $this->updateCart($item['id'], $item['unitId'], $quantity, $item['itemId'], $item['sellerId']);
        }
        if ($this->session) {
            $this->session->set('voucherCode', $voucherCode);
        }
    }

    public function updateWishlist($productId, $unitId = null, $quantity = 1, $itemId = 0, $sellerId = null)
    {
        if (!$this->user) {
            throw new \Exception('You are not logged in.');
        }

        $wishlist = $this->tbCart->getWishlist($this->user->getUserId());
        $addCartItem = false; 
        if (!$wishlist) {
            $wishlist = new Cart;
            $wishlist->setUser($this->user);
            $wishlist->setStatus(Cart::WISHLIST);
            $this->entityManager->persist($wishlist);
            $this->entityManager->flush();
            $addCartItem = true;
        }

        if (is_null($unitId)) {
            $product = $this->tbProduct->find($productId);
            $unitId = $product->getDefaultUnit()->getProductUnitId();
        }
        $this->tbCartItem->updateItem($wishlist, $productId, $unitId, $quantity, $addCartItem, $itemId, false, $sellerId);
    }

    public function getWishlist($metadata = false)
    {
        if (!$this->user) {
            throw new \Exception('You are not logged in.');
        }
        $wishlist = $this->tbCart->getWishlist($this->user->getUserId());
        if ($wishlist) {
            $this->entityManager->refresh($wishlist);
        }
        $products = $wishlist ? $this->tbProduct->fromCart($wishlist, ($this->mode == self::API)) : array();

        if ($metadata) {
            return $this->addMetaData($products);
        }

        return $products;
    }

    public function inWishlist($unitId)
    {
        if (!$this->user) {
            return false;
        }

        $wishlist = $this->tbCart->getWishlist($this->user->getUserId());
        if ($wishlist) {
            foreach ($wishlist->getCartItems() as $item) {
                if (!$item->getProductUnit()) continue;
                $itemUnitId = $item->getProductUnit()->getProductUnitId();
                if ($itemUnitId == $unitId) {
                    return true;
                }
            }
        }

        return false;
    }

    public function wishlistToCart($cartItemIds)
    {
        if (!$this->user) {
            throw new \Exception("You are not logged in.", 1);
        }

        $cart = $this->tbCart->getActiveCartOfUser($this->user->getUserId());
        $wishlistItems = $this->tbCartItem->findById($cartItemIds);

        foreach ($wishlistItems as $wishlistItem) {

            $this->updateCart(
                $wishlistItem->getProduct()->getProductId(),
                $wishlistItem->getProductUnit()->getProductUnitId(),
                $wishlistItem->getQuantity(),
                0,
                $wishlistItem->getSeller()->getUserId(),
                true
            );
            $this->entityManager->remove($wishlistItem);
        }

        $this->entityManager->flush();
    }

    /**
     * replaces the users cart saved in db
     * with one in the session
     */
    public function cartSessionToDB($user = null)
    {
        $user = $user ? $user: $this->user;
        $cart = $this->session->get('cart', array());
        if (!$cart) return array();

        $userCart = $this->tbCart->getActiveCartOfUser($user->getUserId());
        if ($userCart) {
            $userCart->setStatus(Cart::ARCHIVE);
            $this->entityManager->flush();
        }

        $cartSession = array();
        foreach ($cart as $item) {
            $this->updateCart($item['productId'], $item['unitId'], $item['quantity'], 0, $item['sellerId'], false, $user);
            $cartSession[$item['itemId']] = $this->latestItem;
        }

        return $cartSession;
    }

    /**
     * @param $product array result from getDetails with the unitId on the getCart
     * @return array
     */
    public function getProductAttributes($product)
    {
        $attributes = array();
        foreach ($product['productUnits'][$product['unitId']]['combination'] as $key => $attributeValueId) {
            if (array_key_exists($key, $product['attributes'])) {
                foreach ($product['attributes'][$key]['items'] as $attributeValue) {
                    if ($attributeValue['id'] == $attributeValueId) {
                        $attributes[$product['attributes'][$key]['groupName']] = $attributeValue['name'];
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * if some products cannot be paid using COD then the whole cart
     * cannot be paid via COD
     */
    public function canCOD($cart = null)
    {
        if (is_null($cart)) {
            $cart = $this->getCart();
        }
        
        $productUnits = $this->getCartProductUnits($cart);
        foreach ($productUnits as $productUnit) {
            if (!$productUnit->hasCOD()) {
                return false;
            }
        }

        return true;
    }

    public function getCartProductUnits($cart = null)
    {
        if (is_null($cart)) {
            $cart = $this->getCart();
        }

        $data = array();
        $tbProductUnit = $this->entityManager->getRepository('YilinkerCoreBundle:ProductUnit');
        foreach ($cart as $item) {
            $productUnit = $tbProductUnit->find($item['unitId']);
            $data[] = $productUnit;
        }

        return $data;
    }

    private function addMetaData($products)
    {
        $total = 0;
        $totalAmount = 0;
        foreach ($products as $product) {
            $total += $product['quantity'];
            $totalAmount += ($product['quantity'] * $product['productUnits'][$product['unitId']]['discountedPrice']);
        }
        $totalAmountRaw = $totalAmount;
        $totalAmount = number_format($totalAmount, 2);

        return array(
            'items'         => $products,
            'total'         => $total,
            'totalAmount'   => $totalAmount,
            'totalAmountRaw'=> $totalAmountRaw
        );
    }

    private function applyChangeOnBulkPromo(&$cartProducts)
    {
        $productService = $this->productService;

        foreach($cartProducts as $index => &$product){
            $quantity = $product["quantity"];
            $productUnitId = $product["unitId"];
            $productUnit = &$product["productUnits"][$productUnitId];

            switch($productUnit["promoTypeId"]){
                case 2:
                    $productUnit = $productService->setProductUnitDiscount($productUnit, $quantity);
                    break;
            }

            if(!is_null($productUnit["appliedDiscountPrice"])){
                $productUnit["price"] = $productUnit["appliedBaseDiscountPrice"];
                $productUnit["discountedPrice"] = $productUnit["appliedDiscountPrice"];
            }
        }
    }
}