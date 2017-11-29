<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ManufacturerProductCountry
 */
class ManufacturerProductCountry
{
    /**
     * @var integer
     */
    private $manufacturerProductCountryId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    private $country;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Manufacturerproduct
     */
    private $manufacturerProduct;

    /**
     * @var string
     */
    private $referenceId = '';
    
    /**
     * Get manufacturerProductCountryId
     *
     * @return integer 
     */
    public function getManufacturerProductCountryId()
    {
        return $this->manufacturerProductCountryId;
    }

    /**
     * Set country
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country $country
     * @return ManufacturerProductCountry
     */
    public function setCountry(\Yilinker\Bundle\CoreBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set manufacturerProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Manufacturerproduct $manufacturerProduct
     * @return ManufacturerProductCountry
     */
    public function setManufacturerProduct(\Yilinker\Bundle\CoreBundle\Entity\Manufacturerproduct $manufacturerProduct = null)
    {
        $this->manufacturerProduct = $manufacturerProduct;

        return $this;
    }

    /**
     * Get manufacturerProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Manufacturerproduct 
     */
    public function getManufacturerProduct()
    {
        return $this->manufacturerProduct;
    }

    /**
     * Set referenceId
     *
     * @param string $referenceId
     * @return ManufacturerProductCountry
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
