<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductWarehouse
 */
class ProductWarehouse
{
    const DEFAULT_PRIORITY = 1;

    const SECONDARY_PRIORITY = 2;

    /**
     * @var integer
     */
    private $productWarehouseId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse
     */
    private $userWarehouse;

    /**
     * @var string
     */
    private $countryCode = 'ph';

    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Get productWarehouseId
     *
     * @return integer 
     */
    public function getProductWarehouseId()
    {
        return $this->productWarehouseId;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductWarehouse
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set userWarehouse
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $userWarehouse
     * @return ProductWarehouse
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

    /**
     * @var integer
     */
    private $priority = 1;


    /**
     * Set priority
     *
     * @param integer $priority
     * @return ProductWarehouse
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer 
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     * @return ProductWarehouse
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ProductWarehouse
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
     * @var boolean
     */
    private $isCod = false;

    /**
     * @var string
     */
    private $handlingFee = '0';


    /**
     * Set isCod
     *
     * @param boolean $isCod
     * @return ProductWarehouse
     */
    public function setIsCod($isCod)
    {
        $this->isCod = $isCod;

        return $this;
    }

    /**
     * Get isCod
     *
     * @return boolean 
     */
    public function getIsCod()
    {
        return $this->isCod;
    }

    /**
     * Set handlingFee
     *
     * @param string $handlingFee
     * @return ProductWarehouse
     */
    public function setHandlingFee($handlingFee)
    {
        $this->handlingFee = $handlingFee;

        return $this;
    }

    /**
     * Get handlingFee
     *
     * @return string 
     */
    public function getHandlingFee()
    {
        return $this->handlingFee;
    }
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Logistics
     */
    private $logistics;


    /**
     * Set logistics
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Logistics $logistics
     * @return ProductWarehouse
     */
    public function setLogistics(\Yilinker\Bundle\CoreBundle\Entity\Logistics $logistics = null)
    {
        $this->logistics = $logistics;

        return $this;
    }

    /**
     * Get logistics
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Logistics 
     */
    public function getLogistics()
    {
        return $this->logistics;
    }


    public function toArray()
    {
        return array(
            'id' => $this->getProductWarehouseId(),
            'user_warehouse' =>  $this->getUserWarehouse() ? $this->getUserWarehouse()->toArray() : null,
            'priority'  => $this->getPriority(),
            'logistic' => $this->getLogistics() ? $this->getLogistics()->toArray() : null,
            'is_cod'    => $this->getIsCod(),
            'handlingFee' => $this->getHandlingFee(),
        );
    }
}
