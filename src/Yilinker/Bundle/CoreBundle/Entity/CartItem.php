<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CartItem
 */
class CartItem
{

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    private $productUnit;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Cart
     */
    private $cart;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $seller;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return CartItem
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
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return CartItem
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
     * Set productUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit
     * @return CartItem
     */
    public function setProductUnit(\Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * Get productUnit
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductUnit 
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Set cart
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Cart $cart
     * @return CartItem
     */
    public function setCart(\Yilinker\Bundle\CoreBundle\Entity\Cart $cart = null)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Get cart
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Cart 
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Set seller
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $seller
     * @return CartItem
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
        return $this->seller ? $this->seller: new User;
    }
}
