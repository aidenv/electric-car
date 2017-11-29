<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryAttributeName
 */
class CategoryAttributeName
{
    /**
     * @var integer
     */
    private $categoryAttributeNameId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;

    /**
     * Get categoryAttributeNameId
     *
     * @return integer 
     */
    public function getCategoryAttributeNameId()
    {
        return $this->categoryAttributeNameId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CategoryAttributeName
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
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return CategoryAttributeName
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
