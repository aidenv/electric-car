<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable as Translatable;

/**
 * ProductAttributeValue
 */
class ProductAttributeValue extends Translatable
{

    /**
     * @var integer
     */
    private $productAttributeValueId;

    /**
     * @var string
     */
    private $value = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName
     */
    private $productAttributeName;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    private $productUnit;

    /**
     * @var integer
     */
    private $productUnitId;

    /**
     * Get productAttributeValueId
     *
     * @return integer 
     */
    public function getProductAttributeValueId()
    {
        return $this->productAttributeValueId;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return ProductAttributeValue
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
     * Set productAttributeName
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName $productAttributeName
     * @return ProductAttributeValue
     */
    public function setProductAttributeName(\Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName $productAttributeName = null)
    {
        $this->productAttributeName = $productAttributeName;

        return $this;
    }

    /**
     * Get productAttributeName
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductAttributeName 
     */
    public function getProductAttributeName()
    {
        return $this->productAttributeName;
    }

    /**
     * Set productUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit
     * @return ProductAttributeValue
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
     * Set productUnitId
     *
     * @param integer $productUnitId
     * @return ProductAttributeValue
     */
    public function setProductUnitId($productUnitId)
    {
        $this->productUnitId = $productUnitId;

        return $this;
    }

    /**
     * Get productUnitId
     *
     * @return integer 
     */
    public function getProductUnitId()
    {
        return $this->productUnitId;
    }

    public function toArray()
    {
        $attributeName =  $this->getProductAttributeName();
        
        $data = array(
            'productAttributeValueId'   => $this->getProductAttributeValueId(),
            'productAttributeNameId'    => $attributeName->getProductAttributeNameId(),
            'productUnitId'             => $this->productUnit->getProductUnitId(),
            'value'                     => $this->getValue(),
            'name'                      => $attributeName->getName(),
        );

        return $data;
    }
}
