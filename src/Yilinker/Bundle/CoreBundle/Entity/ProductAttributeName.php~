<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable as Translatable;

/**
 * ProductAttributeName
 */
class ProductAttributeName extends Translatable
{

    /**
     * @var integer
     */
    private $productAttributeNameId;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productAttributeValues;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get productAttributeNameId
     *
     * @return integer 
     */
    public function getProductAttributeNameId()
    {
        return $this->productAttributeNameId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ProductAttributeName
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductAttributeName
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
     * Add productAttributeValues
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue $productAttributeValues
     * @return ProductAttributeName
     */
    public function addProductAttributeValue(\Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue $productAttributeValues)
    {
        $this->productAttributeValues[] = $productAttributeValues;

        return $this;
    }

    /**
     * Remove productAttributeValues
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue $productAttributeValues
     */
    public function removeProductAttributeValue(\Yilinker\Bundle\CoreBundle\Entity\ProductAttributeValue $productAttributeValues)
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

    public function __toString()
    {
        return $this->name;
    }
}
