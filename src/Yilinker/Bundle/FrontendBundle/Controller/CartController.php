<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class CartController extends Controller
{
    protected $cartService = null;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->cartService = $this->get('yilinker_front_end.service.cart');
        $this->cartService->apiMode(true);
    }

    /**
     * Add item to cart or wishlist depending on mode
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function addItemToCartAction(Request $request)
    {
        $productId = $request->get('productId');
        $unitId = $request->get('unitId');
        $sellerId = $request->get('sellerId');
        $quantity = $request->get('quantity', 1);
        $itemId = $request->get('itemId', 0);
        $mode = $request->get('mode', 'cart');

        $tbProductUnit = $this->getDoctrine()->getRepository('YilinkerCoreBundle:ProductUnit');
        $unit = $tbProductUnit->findOneBy(array(
            'productUnitId' => $unitId,
            'product'       => $productId
        ));

        if (!$unit) {
            throw new \Exception('Product Unit does not exist.');
        }

        $cart = array();
        if ($mode == 'cart') {
            $this->cartService->updateCart($productId, $unitId, $quantity, $itemId, $sellerId);
            $cart = $this->cartService->getCart();
            $html = $this->renderView('YilinkerFrontendBundle:Base:cart.html.twig', compact('cart'));
        }
        else {
            $this->cartService->updateWishlist($productId, $unitId, $quantity, $itemId, $sellerId);
            $cart = $this->cartService->getWishlist();
            $html = $this->renderView('YilinkerFrontendBundle:Base:wishlist.html.twig', array('wishlist' => $cart));
        }
        
        return new JsonResponse(compact(
            'mode',
            'cart',
            'html'
        ));
    }

    public function transferWishlistToCartAction(Request $request)
    {
        $itemIds = $request->get('itemIds');
        if (!$itemIds) {
            throw new \Exception('There are no items to transfer.');
        }

        $this->cartService->wishlistToCart($itemIds);
        $cart = $this->cartService->getCart();
        $wishlist = $this->cartService->getWishlist();

        $cartHtml = $this->renderView('YilinkerFrontendBundle:Base:cart.html.twig', compact('cart'));
        $wishlistHtml = $this->renderView('YilinkerFrontendBundle:Base:wishlist.html.twig', compact('wishlist'));

        return new JsonResponse(compact(
            'cart',
            'cartHtml',
            'wishlist',
            'wishlistHtml'
        ));
    }
 }
