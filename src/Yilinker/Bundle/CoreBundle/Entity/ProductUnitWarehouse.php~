<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductUnitWarehouse
 */
class ProductUnitWarehouse
{
    /**
     * @var integer
     */
    private $productUnitWarehouseId;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    private $productUnit;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse
     */
    private $userWarehouse;


    /**
     * Get productUnitWarehouseId
     *
     * @return integer 
     */
    public function getProductUnitWarehouseId()
    {
        return $this->productUnitWarehouseId;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return ProductUnitWarehouse
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
     * Set productUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit
     * @return ProductUnitWarehouse
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
     * Set userWarehouse
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $userWarehouse
     * @return ProductUnitWarehouse
     */
    public function setUserWarehouse(\Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $userWarehouse = null)
    {
        $this->userWarehouse = $userWarehouse;

        return $this;
    }

    /**
     * Get userWarehouse
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse 
     */
    public function getUserWarehouse()
    {
        return $this->userWarehouse;
    }
}
