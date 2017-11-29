<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerProductUnitInventoryHistory
 */
class ManufacturerProductUnitInventoryHistory
{

    /**
     * @var integer
     */
    private $manufacturerProductUnitInventoryHistoryId;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var integer
     */
    private $quantity = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    private $manufacturerProductUnit;


    /**
     * Get manufacturerProductUnitInventoryHistoryId
     *
     * @return integer 
     */
    public function getManufacturerProductUnitInventoryHistoryId()
    {
        return $this->manufacturerProductUnitInventoryHistoryId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return ManufacturerProductUnitInventoryHistory
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return ManufacturerProductUnitInventoryHistory
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set manufacturerProductUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit
     * @return ManufacturerProductUnitInventoryHistory
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
}
