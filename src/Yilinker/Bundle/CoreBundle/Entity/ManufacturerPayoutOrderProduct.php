<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerOrderProduct
 */
class ManufacturerPayoutOrderProduct
{

    /**
     * @var integer
     */
    private $manufacturerPayoutOrderProductId;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateModified;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Manufacturer
     */
    private $manufacturer;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout
     */
    private $manufacturerPayout;


    /**
     * Get manufacturerPayoutOrderProductId
     *
     * @return integer 
     */
    public function getManufacturerPayoutOrderProductId()
    {
        return $this->manufacturerPayoutOrderProductId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return ManufacturerPayoutOrderProduct
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
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return ManufacturerPayoutOrderProduct
     */
    public function setDateModified($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime 
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return ManufacturerPayoutOrderProduct
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return ManufacturerPayoutOrderProduct
     */
    public function setOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct = null)
    {
        $this->orderProduct = $orderProduct;

        return $this;
    }

    /**
     * Get orderProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProduct 
     */
    public function getOrderProduct()
    {
        return $this->orderProduct;
    }

    /**
     * Set manufacturer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Manufacturer $manufacturer
     * @return ManufacturerPayoutOrderProduct
     */
    public function setManufacturer(\Yilinker\Bundle\CoreBundle\Entity\Manufacturer $manufacturer = null)
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    /**
     * Get manufacturer
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Manufacturer 
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * Set manufacturerPayout
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout $manufacturerPayout
     * @return ManufacturerPayoutOrderProduct
     */
    public function setManufacturerPayout(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout $manufacturerPayout = null)
    {
        $this->manufacturerPayout = $manufacturerPayout;

        return $this;
    }

    /**
     * Get manufacturerPayout
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout 
     */
    public function getManufacturerPayout()
    {
        return $this->manufacturerPayout;
    }
}
