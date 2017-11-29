<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory;
use Yilinker\Bundle\CoreBundle\Entity\Product;

/**
 * CustomizedCategoryProductLookup
 */
class CustomizedCategoryProductLookup
{
    /**
     * @var integer
     */
    private $customizedCategoryProductLookupId;

    /**
     * @var CustomizedCategory
     */
    private $customizedCategory;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var integer
     */
    private $sortOrder;

    /**
     * Get CustomizedCategoryProductLookupId
     *
     * @return integer 
     */
    public function getCustomizedCategoryProductLookupId()
    {
        return $this->customizedCategoryProductLookupId;
    }

    /**
     * Set customizedCategory
     *
     * @param CustomizedCategory $customizedCategory
     * @return CustomizedCategoryProductLookup
     */
    public function setCustomizedCategory(CustomizedCategory $customizedCategory = null)
    {
        $this->customizedCategory = $customizedCategory;

        return $this;
    }

    /**
     * Get customizedCategory
     *
     * @return CustomizedCategory
     */
    public function getCustomizedCategory()
    {
        return $this->customizedCategory;
    }

    /**
     * Set product
     *
     * @param Product $product
     * @return CustomizedCategoryProductLookup
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set sortOrder
     *
     * @param integer $sortOrder
     * @return CustomizedCategoryProductLookup
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer 
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}
