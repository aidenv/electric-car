<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

class Voucher
{
    CONST INACTIVE = 0;
    CONST ACTIVE = 1;

    const ONE_TIME_USE_PER_USER = 0;
    
    const MULTI_USE = 1;
    
    const ONE_TIME_USE = 2;

    private static $usageTypes = array(
        'One-Time Use Per User',
        'Multi Use',
        'One-Time Use'
    );

    CONST FIXED_AMOUNT = 0;
    CONST PERCENTAGE = 1;
    private static $discountTypes = array(
        'Fixed Amount',
        'Percentage'
    );

    /**
     * @var integer
     */
    private $voucherId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $usageType = 0;

    /**
     * @var integer
     */
    private $quantity = 0;

    /**
     * @var integer
     */
    private $discountType = 0;

    /**
     * @var string
     */
    private $value = '0.00';

    /**
     * @var string
     */
    private $minimumPurchase = '0.00';

    /**
     * @var boolean
     */
    private $isActive;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $voucherCodes;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $voucherProducts;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $voucherProductCategories;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $voucherStores;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->voucherCodes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get voucherId
     *
     * @return integer 
     */
    public function getVoucherId()
    {
        return $this->voucherId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Voucher
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set usageType
     *
     * @param integer $usageType
     * @return Voucher
     */
    public function setUsageType($usageType)
    {
        $this->usageType = $usageType;

        return $this;
    }

    /**
     * Get usageType
     *
     * @return integer 
     */
    public function getUsageType()
    {
        return $this->usageType;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return Voucher
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
     * Set discountType
     *
     * @param integer $discountType
     * @return Voucher
     */
    public function setDiscountType($discountType)
    {
        $this->discountType = $discountType;

        return $this;
    }

    /**
     * Get discountType
     *
     * @return integer 
     */
    public function getDiscountType()
    {
        return $this->discountType;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Voucher
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue($withUnit = false)
    {
        if ($withUnit) {
            return $this->isDiscountPercentage() ? $this->value.' %': 'PHP '.number_format($this->value, 2);
        }

        return $this->value;
    }

    /**
     * Set minimumPurchase
     *
     * @param string $minimumPurchase
     * @return Voucher
     */
    public function setMinimumPurchase($minimumPurchase)
    {
        $this->minimumPurchase = $minimumPurchase ? $minimumPurchase: 0;

        return $this;
    }

    /**
     * Get minimumPurchase
     *
     * @return string 
     */
    public function getMinimumPurchase()
    {
        return $this->minimumPurchase;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Voucher
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Voucher
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Voucher
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Voucher
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

    // custom functions

    public static function usageTypes()
    {
        return self::$usageTypes;
    }

    public function usageTypeTxt()
    {
        if (array_key_exists($this->usageType, self::$usageTypes)) {
            return self::$usageTypes[$this->usageType];
        }

        return '';
    }

    public static function discountTypes()
    {
        return self::$discountTypes;
    }

    public function discountTypeTxt()
    {
        if (array_key_exists($this->discountType, self::$discountTypes)) {
            return self::$discountTypes[$this->discountType];
        }

        return '';
    }

    public function isDiscountFixedAmount()
    {
        return $this->getDiscountType() == self::FIXED_AMOUNT;
    }

    public function isDiscountPercentage()
    {
        return $this->getDiscountType() == self::PERCENTAGE;
    }

    /**
     * Add voucherCodes
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherCode $voucherCodes
     * @return Voucher
     */
    public function addVoucherCode(\Yilinker\Bundle\CoreBundle\Entity\VoucherCode $voucherCodes)
    {
        $this->voucherCodes[] = $voucherCodes;

        return $this;
    }

    /**
     * Remove voucherCodes
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherCode $voucherCodes
     */
    public function removeVoucherCode(\Yilinker\Bundle\CoreBundle\Entity\VoucherCode $voucherCodes)
    {
        $this->voucherCodes->removeElement($voucherCodes);
    }

    /**
     * Get voucherCodes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getVoucherCodes()
    {
        return $this->voucherCodes;
    }

    public function getVoucherCodeTexts()
    {
        if (!isset($this->voucherCodeTexts)) {
            $voucherCodes = $this->getVoucherCodes()->toArray();
            if (!$voucherCodes) {
                return array();
            }

            $codes = array_map(function($voucherCode) {
                return $voucherCode->getCode();
            }, $voucherCodes);

            $this->voucherCodeTexts = $codes;
        }

        return $this->voucherCodeTexts;
    }

    public function getUsageCount()
    {
        $total = 0;
        foreach ($this->getVoucherCodes() as $voucherCode) {
            $total += $voucherCode->getOrderVouchers()->count();
        }

        return $total;
    }

    /**
     * Convert the voucher to an array
     */
    public function toArray()
    {        
        return array(
            'name'         => $this->name,
            'discountType' => array(
                'value' => $this->discountType,
                'name'  => self::$discountTypes[$this->discountType],
            ),
            'usageType' => array(
                'value' => $this->usageType,
                'name'  => self::$usageTypes[$this->usageType],
            ),            
        );
    }
    /**
     * @var boolean
     */
    private $includeAffiliates = false;


    /**
     * Set includeAffiliates
     *
     * @param boolean $includeAffiliates
     * @return Voucher
     */
    public function setIncludeAffiliates($includeAffiliates)
    {
        $this->includeAffiliates = $includeAffiliates;

        return $this;
    }

    /**
     * Get includeAffiliates
     *
     * @return boolean 
     */
    public function getIncludeAffiliates()
    {
        return $this->includeAffiliates;
    }

    /**
     * Add voucherProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherProduct $voucherProducts
     * @return Voucher
     */
    public function addVoucherProduct(\Yilinker\Bundle\CoreBundle\Entity\VoucherProduct $voucherProducts)
    {
        $this->voucherProducts[] = $voucherProducts;

        return $this;
    }

    /**
     * Remove voucherProducts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherProduct $voucherProducts
     */
    public function removeVoucherProduct(\Yilinker\Bundle\CoreBundle\Entity\VoucherProduct $voucherProducts)
    {
        $this->voucherProducts->removeElement($voucherProducts);
    }

    /**
     * Get voucherProducts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getVoucherProducts()
    {
        return $this->voucherProducts;
    }

    /**
     * Add voucherStores
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherStore $voucherStores
     * @return Voucher
     */
    public function addVoucherStore(\Yilinker\Bundle\CoreBundle\Entity\VoucherStore $voucherStores)
    {
        $this->voucherStores[] = $voucherStores;

        return $this;
    }

    /**
     * Remove voucherStores
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherStore $voucherStores
     */
    public function removeVoucherStore(\Yilinker\Bundle\CoreBundle\Entity\VoucherStore $voucherStores)
    {
        $this->voucherStores->removeElement($voucherStores);
    }

    /**
     * Get voucherStores
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getVoucherStores()
    {
        return $this->voucherStores;
    }

    /**
     * Add voucherProductCategories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherProductCategory $voucherProductCategories
     * @return Voucher
     */
    public function addVoucherProductCategory(\Yilinker\Bundle\CoreBundle\Entity\VoucherProductCategory $voucherProductCategories)
    {
        $this->voucherProductCategories[] = $voucherProductCategories;

        return $this;
    }

    /**
     * Remove voucherProductCategories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\VoucherProductCategory $voucherProductCategories
     */
    public function removeVoucherProductCategory(\Yilinker\Bundle\CoreBundle\Entity\VoucherProductCategory $voucherProductCategories)
    {
        $this->voucherProductCategories->removeElement($voucherProductCategories);
    }

    /**
     * Get voucherProductCategories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getVoucherProductCategories()
    {
        return $this->voucherProductCategories;
    }
}
