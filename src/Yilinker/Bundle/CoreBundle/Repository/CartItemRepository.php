<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Entity\CartItem;
use Yilinker\Bundle\CoreBundle\Entity\Cart;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;

/**
 * Class CartRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class CartItemRepository extends EntityRepository
{
    public function updateItem($cart, $productId, $unitId, $quantity, $addCartItem = false, $itemId = 0, $retain = false, $sellerId = null)
    {
        $cartId = $cart->getId();
        $em = $this->getEntityManager();
        $this->qb()
             ->innerJoin('YilinkerCoreBundle:ProductUnit', 'pu', Join::WITH, 'this.productUnit = pu')
             ->andWhere('this.cart = :cartId')->setParameter('cartId', $cartId)
             ->andWhere('this.product = :productId')->setParameter('productId', $productId)
             ->andWhere('this.productUnit = :unitId')->setParameter('unitId', $unitId)
             ->andWhere('pu.status = :status')->setParameter('status', ProductUnit::STATUS_ACTIVE)
        ;
        if ($sellerId) {
            $this->andWhere('this.seller = :sellerId')->setParameter('sellerId', $sellerId);
        }

        $cartItems = $this->getQB()->getQuery()->getResult();
        $cartItem = array_shift($cartItems);

        if ($retain && $cartItem) {
            return $cartItem;
        }
        $oldCartItem = $this->qb()->findOneById($itemId);

        if ($cartItem) { 
            if (!$itemId || $cartItem->getId() == $itemId) {
                if ($quantity) {
                    $cartItem->setQuantity($quantity);
                }
                else {
                    $em->remove($cartItem);
                }
            }
            elseif ($oldCartItem) {
                $mergedQuantity = $cartItem->getQuantity() + $oldCartItem->getQuantity();
                $tbProductUnit = $em->getRepository('YilinkerCoreBundle:ProductUnit');
                $mergedQuantity = $tbProductUnit->trueQuantity($unitId, $mergedQuantity);
                $cartItem->setQuantity($mergedQuantity);
                $em->remove($oldCartItem);
            }
        }
        elseif ($oldCartItem) {
            if ($quantity) {
                $oldCartItem->setCart($cart);
                $oldCartItem->setProduct($em->getReference('Yilinker\Bundle\CoreBundle\Entity\Product', $productId));
                $oldCartItem->setProductUnit($em->getReference('Yilinker\Bundle\CoreBundle\Entity\ProductUnit', $unitId));
                if ($sellerId) {
                    $oldCartItem->setSeller($em->getReference('Yilinker\Bundle\CoreBundle\Entity\User', $sellerId));
                }
                $oldCartItem->setQuantity($quantity);
                $em->persist($oldCartItem);
            }
            else {
                $em->remove($oldCartItem);
            }
        }
        elseif ($quantity) {

            $queryBuilder = $this->_em->createQueryBuilder();
            $queryBuilder->select("pu")
                         ->from("YilinkerCoreBundle:ProductUnit", "pu")
                         ->innerJoin("YilinkerCoreBundle:Product", "p", Join::WITH, "pu.product = p")
                         ->where("pu.productUnitId = :productUnitId")
                         ->andWhere("p.productId = :productId")
                         ->setParameter(":productId", $productId)
                         ->setParameter(":productUnitId", $unitId);
            if ($sellerId) {
                $queryBuilder
                    ->innerJoin('YilinkerCoreBundle:InhouseProductUser', 'ipu', Join::WITH, 'ipu.product = p')
                    ->andWhere('ipu.user = :seller')
                    ->setParameter('seller', $sellerId)
                ;
            }

            $productUnit = $queryBuilder->getQuery()->getOneOrNullResult();

            if(
                $productUnit->getQuantity() > 0 &&
                $productUnit->getStatus() == ProductUnit::STATUS_ACTIVE
            ){
                $cartItem = new CartItem;
                $cartItem->setCart($cart);
                $cartItem->setProduct($em->getReference('Yilinker\Bundle\CoreBundle\Entity\Product', $productId));
                $cartItem->setProductUnit($em->getReference('Yilinker\Bundle\CoreBundle\Entity\ProductUnit', $unitId));
                if ($sellerId) {
                    $cartItem->setSeller($em->getReference('Yilinker\Bundle\CoreBundle\Entity\User', $sellerId));
                }
                $cartItem->setQuantity($quantity);
                $em->persist($cartItem);
            }
        }

        try {
            $em->flush();
            if ($addCartItem) {
                $cart->addCartItem($cartItem);
            }
        } catch (\Exception $e) {
            return false;
        }

        return $cartItem;
    }

    public function getWishlistInstances(Product $product)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("ci")
                     ->from("YilinkerCoreBundle:CartItem", "ci")
                     ->innerJoin("YilinkerCoreBundle:Cart", "c")
                     ->where($queryBuilder->expr()->eq("ci.product", ":product"))
                     ->andWhere($queryBuilder->expr()->eq("c.status", ":status"))
                     ->setParameter(":product", $product)
                     ->setParameter(":status", Cart::WISHLIST);

        return $queryBuilder->getQuery()->getResult();
    }
}