<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductUnitImage
 */
class ProductUnitImage
{
    
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    private $productUnit;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductImage
     */
    private $productImage;


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
     * Set productUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit
     * @return ProductUnitImage
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
     * Set productImage
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductImage $productImage
     * @return ProductUnitImage
     */
    public function setProductImage(\Yilinker\Bundle\CoreBundle\Entity\ProductImage $productImage = null)
    {
        $this->productImage = $productImage;

        return $this;
    }

    /**
     * Get productImage
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductImage 
     */
    public function getProductImage()
    {
        return $this->productImage;
    }
}
