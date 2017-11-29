<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryNestedSet
 */
class CategoryNestedSet
{

    /**
     * @var integer
     */
    private $categoryNestedSetId;

    /**
     * @var integer
     */
    private $left;

    /**
     * @var integer
     */
    private $right;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;


    /**
     * Get categoryNestedSetId
     *
     * @return integer 
     */
    public function getCategoryNestedSetId()
    {
        return $this->categoryNestedSetId;
    }

    /**
     * Set left
     *
     * @param integer $left
     * @return CategoryNestedSet
     */
    public function setLeft($left)
    {
        $this->left = $left;

        return $this;
    }

    /**
     * Get left
     *
     * @return integer 
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * Set right
     *
     * @param integer $right
     * @return CategoryNestedSet
     */
    public function setRight($right)
    {
        $this->right = $right;

        return $this;
    }

    /**
     * Get right
     *
     * @return integer 
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return CategoryNestedSet
     */
    public function setProductCategory(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCategory 
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }
}
