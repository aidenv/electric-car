<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCategoryTranslation
 */
class ProductCategoryTranslation
{

    /**
     * @var integer
     */
    private $productCategoryTranslationId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Language
     */
    private $language;


    /**
     * Get productCategoryTranslationId
     *
     * @return integer 
     */
    public function getProductCategoryTranslationId()
    {
        return $this->productCategoryTranslationId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ProductCategoryTranslation
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
     * Set description
     *
     * @param string $description
     * @return ProductCategoryTranslation
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return ProductCategoryTranslation
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

    /**
     * Set language
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Language $language
     * @return ProductCategoryTranslation
     */
    public function setLanguage(\Yilinker\Bundle\CoreBundle\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Language 
     */
    public function getLanguage()
    {
        return $this->language;
    }
}
