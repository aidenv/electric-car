<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShippingCategoryCountry
 */
class ShippingCategoryCountry
{
    /**
     * @var integer
     */
    private $shippingCategoryCountryId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    private $country;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ShippingCategory
     */
    private $shippingCategory;


    /**
     * Get shippingCategoryCountryId
     *
     * @return integer 
     */
    public function getShippingCategoryCountryId()
    {
        return $this->shippingCategoryCountryId;
    }

    /**
     * Set country
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country $country
     * @return ShippingCategoryCountry
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
     * Set shippingCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ShippingCategory $shippingCategory
     * @return ShippingCategoryCountry
     */
    public function setShippingCategory(\Yilinker\Bundle\CoreBundle\Entity\ShippingCategory $shippingCategory = null)
    {
        $this->shippingCategory = $shippingCategory;

        return $this;
    }

    /**
     * Get shippingCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ShippingCategory 
     */
    public function getShippingCategory()
    {
        return $this->shippingCategory;
    }
    /**
     * @var string
     */
    private $yilinkerCharge = '0';

    /**
     * @var string
     */
    private $additionalCost = '0';

    /**
     * @var string
     */
    private $handlingFee = '0';


    /**
     * Set yilinkerCharge
     *
     * @param string $yilinkerCharge
     * @return ShippingCategoryCountry
     */
    public function setYilinkerCharge($yilinkerCharge)
    {
        $this->yilinkerCharge = $yilinkerCharge;

        return $this;
    }

    /**
     * Get yilinkerCharge
     *
     * @return string 
     */
    public function getYilinkerCharge()
    {
        return $this->yilinkerCharge;
    }

    /**
     * Set additionalCost
     *
     * @param string $additionalCost
     * @return ShippingCategoryCountry
     */
    public function setAdditionalCost($additionalCost)
    {
        $this->additionalCost = $additionalCost;

        return $this;
    }

    /**
     * Get additionalCost
     *
     * @return string 
     */
    public function getAdditionalCost()
    {
        return $this->additionalCost;
    }

    /**
     * Set handlingFee
     *
     * @param string $handlingFee
     * @return ShippingCategoryCountry
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
}
