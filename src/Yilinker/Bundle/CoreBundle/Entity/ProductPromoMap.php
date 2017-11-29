<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;

/**
 * ProductPromoMap
 */
class ProductPromoMap
{
    /**
     * @var integer
     */
    private $productPromoMapId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductUnit
     */
    private $productUnit;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\PromoInstance
     */
    private $promoInstance;
    
    /**
     * @var integer
     */
    private $sortOrder = '0';

    /**
     * @var integer
     */
    private $maxQuantity = '0';
    
    /**
     * @var string
     */
    private $discountedPrice = '0.00';

    /**
     * @var string
     */
    private $minimumPercentage = '0';

    /**
     * @var string
     */
    private $percentPerHour = '0';

    /**
     * @var integer
     */
    private $quantityRequired = '0';
    
    /**
     * @var string
     */
    private $maximumPercentage = '0';

    /**
     * Get productPromoMapId
     *
     * @return integer 
     */
    public function getProductPromoMapId()
    {
        return $this->productPromoMapId;
    }

    /**
     * Set productUnit
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductUnit $productUnit
     * @return ProductPromoMap
     */
    public function setProductUnit(ProductUnit $productUnit = null)
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
     * Set promoInstance
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\PromoInstance $promoInstance
     * @return ProductPromoMap
     */
    public function setPromoInstance(PromoInstance $promoInstance = null)
    {
        $this->promoInstance = $promoInstance;

        return $this;
    }

    /**
     * Get promoInstance
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\PromoInstance 
     */
    public function getPromoInstance()
    {
        return $this->promoInstance;
    }

    /**
     * Set sortOrder
     *
     * @param integer $sortOrder
     * @return ProductPromoMap
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer 
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set maxQuantity
     *
     * @param integer $maxQuantity
     * @return ProductPromoMap
     */
    public function setMaxQuantity($maxQuantity)
    {
        $this->maxQuantity = $maxQuantity;

        return $this;
    }

    /**
     * Get maxQuantity
     *
     * @return integer 
     */
    public function getMaxQuantity()
    {
        return $this->maxQuantity;
    }

    /**
     * Set discountedPrice
     *
     * @param string $discountedPrice
     * @return ProductPromoMap
     */
    public function setDiscountedPrice($discountedPrice)
    {
        $this->discountedPrice = $discountedPrice;

        return $this;
    }

    /**
     * Get discountedPrice
     *
     * @return string 
     */
    public function getDiscountedPrice()
    {
        return $this->discountedPrice;
    }

    /**
     * Set minimumPercentage
     *
     * @param string $minimumPercentage
     * @return ProductPromoMap
     */
    public function setMinimumPercentage($minimumPercentage)
    {
        $this->minimumPercentage = $minimumPercentage;

        return $this;
    }

    /**
     * Get minimumPercentage
     *
     * @return string 
     */
    public function getMinimumPercentage()
    {
        return $this->minimumPercentage;
    }

    /**
     * Set percentPerHour
     *
     * @param string $percentPerHour
     * @return ProductPromoMap
     */
    public function setPercentPerHour($percentPerHour)
    {
        $this->percentPerHour = $percentPerHour;

        return $this;
    }

    /**
     * Get percentPerHour
     *
     * @return string 
     */
    public function getPercentPerHour()
    {
        return $this->percentPerHour;
    }

    /**
     * Set quantityRequired
     *
     * @param integer $quantityRequired
     * @return ProductPromoMap
     */
    public function setQuantityRequired($quantityRequired)
    {
        $this->quantityRequired = $quantityRequired;

        return $this;
    }

    /**
     * Get quantityRequired
     *
     * @return integer 
     */
    public function getQuantityRequired()
    {
        return $this->quantityRequired;
    }

    /**
     * Set maximumPercentage
     *
     * @param string $maximumPercentage
     * @return ProductPromoMap
     */
    public function setMaximumPercentage($maximumPercentage)
    {
        $this->maximumPercentage = $maximumPercentage;

        return $this;
    }

    /**
     * Get maximumPercentage
     *
     * @return string 
     */
    public function getMaximumPercentage()
    {
        return $this->maximumPercentage;
    }
}
