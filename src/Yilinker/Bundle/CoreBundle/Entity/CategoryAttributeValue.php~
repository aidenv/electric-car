<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CategoryAttributeValue
 */
class CategoryAttributeValue
{
    /**
     * @var integer
     */
    private $categoryAttributeId;

    /**
     * @var string
     */
    private $value;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\CategoryAttributeName
     */
    private $categoryAttributeName;


    /**
     * Get categoryAttributeId
     *
     * @return integer 
     */
    public function getCategoryAttributeId()
    {
        return $this->categoryAttributeId;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return CategoryAttributeValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set categoryAttributeName
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CategoryAttributeName $categoryAttributeName
     * @return CategoryAttributeValue
     */
    public function setCategoryAttributeName(\Yilinker\Bundle\CoreBundle\Entity\CategoryAttributeName $categoryAttributeName = null)
    {
        $this->categoryAttributeName = $categoryAttributeName;

        return $this;
    }

    /**
     * Get categoryAttributeName
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\CategoryAttributeName 
     */
    public function getCategoryAttributeName()
    {
        return $this->categoryAttributeName;
    }
}
