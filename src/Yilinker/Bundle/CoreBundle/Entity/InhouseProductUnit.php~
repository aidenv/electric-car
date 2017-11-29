<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class InhouseProductUnit extends ProductUnit
{
    
    /**
     * @var string
     */
    private $referenceId = '';

    /**
     * @var string
     */
    private $retailPrice;

    /**
     * @var string
     */
    private $unitPrice = '0.00';

    /**
     * @var integer
     */
    private $moq = '0';

    /**
     * @var string
     */
    private $shippingFee = '0.00';

    /**
     * @var boolean
     */
    private $isInventoryConfirmed = false;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit
     */
    private $manufacturerProductUnit;

    /**
     * Set referenceId
     *
     * @param string $referenceId
     * @return InhouseProductUnit
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

    /**
     * Set retailPrice
     *
     * @param string $retailPrice
     * @return InhouseProductUnit
     */
    public function setRetailPrice($retailPrice)
    {
        $this->retailPrice = $retailPrice;

        return $this;
    }

    /**
     * Get retailPrice
     *
     * @return string 
     */
    public function getRetailPrice()
    {
        return $this->retailPrice;
    }

    /**
     * Set unitPrice
     *
     * @param string $unitPrice
     * @return InhouseProductUnit
     */
    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * Get unitPrice
     *
     * @return string 
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * Set moq
     *
     * @param integer $moq
     * @return InhouseProductUnit
     */
    public function setMoq($moq)
    {
        $this->moq = $moq;

        return $this;
    }

    /**
     * Get moq
     *
     * @return integer 
     */
    public function getMoq()
    {
        return $this->moq;
    }

    /**
     * Set shippingFee
     *
     * @param string $shippingFee
     * @return InhouseProductUnit
     */
    public function setShippingFee($shippingFee)
    {
        $this->shippingFee = $shippingFee;

        return $this;
    }

    /**
     * Get shippingFee
     *
     * @return string 
     */
    public function getShippingFee()
    {
        return $this->shippingFee;
    }

    /**
     * Set isInventoryConfirmed
     *
     * @param boolean $isInventoryConfirmed
     * @return InhouseProductUnit
     */
    public function setIsInventoryConfirmed($isInventoryConfirmed)
    {
        $this->isInventoryConfirmed = $isInventoryConfirmed;

        return $this;
    }

    /**
     * Get isInventoryConfirmed
     *
     * @return boolean 
     */
    public function getIsInventoryConfirmed()
    {
        return $this->isInventoryConfirmed;
    }

    /**
     * Set manufacturerProductUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit $manufacturerProductUnit
     * @return InhouseProductUnit
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


    public function toArray(
        $isFormatted = false,
        $showBulkDiscount = true,
        $getWarehouses = false,
        $activeUnits = false
    ){        
        $data = parent::toArray($isFormatted,$showBulkDiscount,$getWarehouses,$activeUnits);

        $data['discountedPrice'] = $this->getDiscountedPrice();

        return $data;
    }    

    public function getAppliedDiscountPrice()
    {
        $appliedDiscountPrice = $this->singleAppliedDiscountPrice();
        if(is_null($appliedDiscountPrice)){
            return $this->getDiscountedPrice();
        }
        else{
            return $appliedDiscountPrice;
        }
    }

    public function getDiscountedPrice($useParent = false)
    {
        if ($this->getRetailPrice())
            $price = $this->getRetailPrice();
        else if ($this->getUnitPrice())
            $price = $this->getUnitPrice();
        else
            $price = parent::getDiscountedPrice();

        return $useParent ? parent::getDiscountedPrice() : $price;
    }

    public function getDiscounted()
    {
        return $this->getDiscountedPrice(true);
    }

    public function setDiscounted($discounted)
    {
        return $this->setDiscountedPrice($discounted);
    }
}
