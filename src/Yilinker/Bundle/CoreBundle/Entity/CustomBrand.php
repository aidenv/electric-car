<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CustomBrand
 */
class CustomBrand
{
    /**
     * @var integer
     */
    private $customBrandId;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;


    /**
     * Get customBrandId
     *
     * @return integer 
     */
    public function getCustomBrandId()
    {
        return $this->customBrandId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CustomBrand
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
     * @return CustomBrand
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
