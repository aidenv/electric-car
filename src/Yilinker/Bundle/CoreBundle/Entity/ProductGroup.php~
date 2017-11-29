<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductGroup
 */
class ProductGroup
{
    /**
     * @var integer
     */
    private $productGroupId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserProductGroup
     */
    private $userProductGroup;


    /**
     * Get productGroupId
     *
     * @return integer 
     */
    public function getProductGroupId()
    {
        return $this->productGroupId;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductGroup
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
     * Set userProductGroup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserProductGroup $userProductGroup
     * @return ProductGroup
     */
    public function setUserProductGroup(\Yilinker\Bundle\CoreBundle\Entity\UserProductGroup $userProductGroup = null)
    {
        $this->userProductGroup = $userProductGroup;

        return $this;
    }

    /**
     * Get userProductGroup
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserProductGroup 
     */
    public function getUserProductGroup()
    {
        return $this->userProductGroup;
    }

    public function __toString()
    {
        if ($this->userProductGroup) {
            return $this->userProductGroup->getName();
        }

        return '';
    }
}
