<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * ManufacturerProductAttributeName
 */
class ManufacturerProductAttributeName
{
    /**
     * @var integer
     */
    private $manufacturerProductAttributeNameId;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct
     */
    private $manufacturerProduct;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $manufacturerProductAttributeValues;

    /**
     * @var string
     */
    private $referenceId;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->manufacturerProductAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get manufacturerProductAttributeNameId
     *
     * @return integer 
     */
    public function getManufacturerProductAttributeNameId()
    {
        return $this->manufacturerProductAttributeNameId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ManufacturerProductAttributeName
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
     * Set manufacturerProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct
     * @return ManufacturerProductAttributeName
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
     * Add manufacturerProductAttributeValues
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues
     * @return ManufacturerProductAttributeName
     */
    public function addManufacturerProductAttributeValue(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues)
    {
        $this->manufacturerProductAttributeValues[] = $manufacturerProductAttributeValues;

        return $this;
    }

    /**
     * Remove manufacturerProductAttributeValues
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues
     */
    public function removeManufacturerProductAttributeValue(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductAttributeValue $manufacturerProductAttributeValues)
    {
        $this->manufacturerProductAttributeValues->removeElement($manufacturerProductAttributeValues);
    }

    /**
     * Get manufacturerProductAttributeValues
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getManufacturerProductAttributeValues()
    {
        return $this->manufacturerProductAttributeValues;
    }

    /**
     * Set referenceId
     *
     * @param string $referenceId
     * @return ManufacturerProductAttributeName
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
