<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerProductUnitMap
 */
class ManufacturerProductUnitMap
{
    /**
     * @var integer
     */
    private $manufacturerProductUnitMapId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    private $manufacturerProductUnit;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    private $productUnit;


    /**
     * Get manufacturerProductUnitMapId
     *
     * @return integer 
     */
    public function getManufacturerProductUnitMapId()
    {
        return $this->manufacturerProductUnitMapId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ManufacturerProductUnitMap
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set manufacturerProductUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit
     * @return ManufacturerProductUnitMap
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
     * Set productUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit
     * @return ManufacturerProductUnitMap
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
}
