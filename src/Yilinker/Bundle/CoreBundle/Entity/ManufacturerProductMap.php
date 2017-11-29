<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Manufacturer
 */
class ManufacturerProductMap
{

    /**
     * @var integer
     */
    private $manufacturerProductMapId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct
     */
    private $manufacturerProduct;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;


    /**
     * Get manufacturerProductMapId
     *
     * @return integer 
     */
    public function getManufacturerProductMapId()
    {
        return $this->manufacturerProductMapId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ManufacturerProductMap
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set manufacturerProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct
     * @return ManufacturerProductMap
     */
    public function setManufacturerProduct(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct = null)
    {
        $this->manufacturerProduct = $manufacturerProduct;

        return $this;
    }

    /**
     * Get manufacturerProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct 
     */
    public function getManufacturerProduct()
    {
        return $this->manufacturerProduct;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ManufacturerProductMap
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
}
