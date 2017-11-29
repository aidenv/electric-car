<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\NoResultException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;

/**
 * Class CartApiController
 * @package Yilinker\Bundle\FrontendBundle\Controller\Api
 */
class CartApiController extends Controller
{
    protected $cartService = null;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->cartService = $this->get('yilinker_front_end.service.cart');
        $this->cartService->apiMode(true);
    }

     /**
     * UpdateCart
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="CART",
     *     parameters={
     *         {"name"="productId", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="unitId", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="quantity", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="itemId", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="wishlist", "dataType"="string", "required"=false, "description"=""},
     *     },

     * )
     */
    public function updateCartAction(Request $request)
    {
        $productId = $request->get('productId');
        $unitId = $request->get('unitId');
        $quantity = $request->get('quantity', 0);
        $itemId = $request->get('itemId', 0);
        $wishlist = $request->get('wishlist', 0);

        try {
            $tbProduct = $this->getDoctrine()->getRepository('YilinkerCoreBundle:Product');
            $product = $tbProduct->getProductUnit($productId, $unitId);

        } catch (NoResultException $e) {
            $data = array(
                'isSuccessful'  => false,
                'data'          => array(),
                'message'       => 'The product does not exist.'
            );

            return new JsonResponse($data);
        }

        if ($wishlist) {
            $quantity = $request->get('quantity', 1);
            $this->cartService->updateWishlist($productId, $unitId, $quantity, $itemId);
            $cart = $this->cartService->getWishlist(true);
        }
        else {
            $this->cartService->updateCart($productId, $unitId, $quantity, $itemId);
            $cart = $this->cartService->getCart(true);
        }

        foreach ($cart['items'] as &$product) {
            $product['productUnits'] = array_values($product['productUnits']);
        }

        $data = array(
            'isSuccessful' => true,
            'data'         => $cart,
            'message'      => ''
        );

        return new JsonResponse($data);
    }

    /**
     * Get Cart
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="CART",
     *     parameters={
     *         {"name"="wishlist", "dataType"="string", "required"=false, "description"=""},
     *     },

     * )
     */
    public function getCartAction(Request $request)
    {
        $wishlist = $request->get('wishlist', 0);

        if ($wishlist) {
            $cart = $this->cartService->getWishlist(true);
        }
        else {
            $cart = $this->cartService->getCart(true);
        }

        foreach ($cart['items'] as $key=>&$product) {
            $productUnits = array_values($product['productUnits']);
            $filteredUnits = array();
            foreach ($productUnits as &$productUnit) {
                if($productUnit['status'] == ProductUnit::STATUS_ACTIVE){
                    $productUnit['price'] = number_format($productUnit['price'], 2);
                    $productUnit['discountedPrice'] = number_format($productUnit['discountedPrice'], 2);
                    $productUnit['appliedBaseDiscountPrice'] = number_format($productUnit['appliedBaseDiscountPrice'], 2);
                    $productUnit['appliedDiscountPrice'] = number_format($productUnit['appliedDiscountPrice'], 2);

                    array_push($filteredUnits, $productUnit);
                }

            }

            $product['productUnits'] = $filteredUnits;

            if(empty($product['productUnits'])){
                unset($cart['items'][$key]);
            }
        }

        $data = array(
            'isSuccessful' => true,
            'data'         => $cart,
            'message'      => ''
        );

        return new JsonResponse($data);
    }

    /**
     * transferWishlistToCart
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="CART",
     *     parameters={
     *         {"name"="itemIds", "dataType"="string", "required"=true, "description"=""},
     *     },

     * )
     */
    public function transferWishlistToCartAction(Request $request)
    {
        $itemIds = $request->get('itemIds');
        $message = '';
        if (!$itemIds) {
            $message = 'There are no items to transfer';
        }
        elseif (!$wishlist = $this->cartService->getWishlist()){
            $message = 'There are no items to transfer';   
        }

        $this->cartService->wishlistToCart($itemIds);
        $cart = $this->cartService->getCart();
        $wishlist = $this->cartService->getWishlist();

        $data = array(
            'isSuccessful' => !$message,
            'message' => $message,
            'data' => compact(
                'cart',
                'wishlist'
            )
        );

        return new JsonResponse($data);
    }
}