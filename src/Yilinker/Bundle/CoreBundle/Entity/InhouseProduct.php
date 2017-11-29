<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;

class InhouseProduct extends Product
{
    
    /**
     * @var string
     */
    private $referenceNumber = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Manufacturer
     */
    private $manufacturer;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct
     */
    private $manufacturerProduct;

    /**
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return InhouseProduct
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    /**
     * Get referenceNumber
     *
     * @return string 
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * Set manufacturer
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Manufacturer $manufacturer
     * @return InhouseProduct
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
     * Set manufacturerProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct $manufacturerProduct
     * @return InhouseProduct
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

    public function isSelectedBy($user)
    {
        $crit = Criteria::create()->andWhere(Criteria::expr()->eq('user', $user));
        $inhouseProductUsers = $this->getInhouseProductUsers()->matching($crit);

        return (bool) $inhouseProductUsers->count();
    }
}
