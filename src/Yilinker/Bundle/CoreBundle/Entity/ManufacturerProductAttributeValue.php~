<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerProductAttributeValue
 */
class ManufacturerProductAttributeValue
{
    /**
     * @var integer
     */
    private $manufacturerProductAttributeValueId;

    /**
     * @var string
     */
    private $value = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName
     */
    private $manufacturerProductAttributeName;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    private $manufacturerProductUnit;

    /**
     * @var string
     */
    private $referenceId = '';

    /**
     * Get manufacturerProductAttributeValueId
     *
     * @return integer 
     */
    public function getManufacturerProductAttributeValueId()
    {
        return $this->manufacturerProductAttributeValueId;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return ManufacturerProductAttributeValue
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
     * Set manufacturerProductAttributeName
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName $manufacturerProductAttributeName
     * @return ManufacturerProductAttributeValue
     */
    public function setManufacturerProductAttributeName(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName $manufacturerProductAttributeName = null)
    {
        $this->manufacturerProductAttributeName = $manufacturerProductAttributeName;

        return $this;
    }

    /**
     * Get manufacturerProductAttributeName
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeName 
     */
    public function getManufacturerProductAttributeName()
    {
        return $this->manufacturerProductAttributeName;
    }

    /**
     * Set manufacturerProductUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit
     * @return ManufacturerProductAttributeValue
     */
    public function setManufacturerProductUnit(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit = null)
    {
        $this->manufacturerProductUnit = $manufacturerProductUnit;

        return $this;
    }

    /**
     * Get manufacturerProductUnit
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit 
     */
    public function getManufacturerProductUnit()
    {
        return $this->manufacturerProductUnit;
    }

    /**
     * Convert the entity into an array
     *
     * @return mixed
     */
    public function toArray()
    {
        $attributeName = $this->getManufacturerProductAttributeName();

        return array(
            'manufacturerProductAttributeValueId'   => $this->manufacturerProductAttributeValueId,
            'manufacturerProductAttributeNameId'    => $attributeName->getManufacturerProductAttributeNameId(),
            'manufactureProductUnitId '             => $this->manufacturerProductUnit->getManufacturerProductUnitId(),
            'value'                                 => $this->value,
            'name'                                  => $attributeName->getName(),
        );
    }

    /**
     * Set referenceId
     *
     * @param string $referenceId
     * @return ManufacturerProductAttributeValue
     */
    public function setReferenceId($referenceId)
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    /**
     * Get referenceId
     *
     * @return string 
     */
    public function getReferenceId()
    {
        return $this->referenceId;
    }
}
