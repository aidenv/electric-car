<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class EarningType
{
    const SALE = 1;
    
    const AFFILIATE_COMMISSION = 2;
    
    const FOLLOW = 3;

    const COMMENT = 4;

    const BUYER_REGISTRATION = 5;

    const BUYER_TRANSACTION = 6;

    const AFFILIATE_REGISTRATION = 7;

    const AFFILIATE_TRANSACTION = 8;

    const WITHDRAW = 9;

    const PRIVILEGE_LEVEL_BOTH = 0;

    const PRIVILEGE_LEVEL_SELLER = 1;

    const PRIVILEGE_LEVEL_AFFILIATE = 2;

    /**
     * @var integer
     */
    private $earningTypeId;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @var integer
     */
    private $privilegeLevel = '0';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $earnings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->earnings = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get earningTypeId
     *
     * @return integer 
     */
    public function getEarningTypeId()
    {
        return $this->earningTypeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return EarningType
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
     * Add earnings
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earnings
     * @return EarningType
     */
    public function addEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earnings)
    {
        $this->earnings[] = $earnings;

        return $this;
    }

    /**
     * Remove earnings
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earnings
     */
    public function removeEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earnings)
    {
        $this->earnings->removeElement($earnings);
    }

    /**
     * Get earnings
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEarnings()
    {
        return $this->earnings;
    }

    public function __toString()
    {
        switch ($this->name) {
            case 'SALE':
                return 'Sale';
            case 'AFFILIATE_COMMISSION':
                return 'Commission';
            case 'FOLLOW':
                return 'Follow';
            case 'COMMENT':
                return 'Comment';
            case 'BUYER_REGISTRATION':
                return 'Buyer Registration';
            case 'BUYER_TRANSACTION':
                return 'Buyer Transaction';
            case 'AFFILIATE_REGISTRATION':
                return 'Affiliate Registration';
            case 'AFFILIATE_TRANSACTION':
                return 'Affiliate Transaction';
            case 'WITHDRAW':
                return 'Withdraw';
        }

        return (string) $this->name;
    }

    public static function getEarningTypes()
    {
        return array(
            self::SALE => 'Sale',
            self::AFFILIATE_COMMISSION => 'Commission',
            self::FOLLOW => 'Follow',
            self::COMMENT => 'Comment',
            self::BUYER_REGISTRATION => 'Buyer Registration',
            self::BUYER_TRANSACTION => 'Buyer Transaction',
            self::AFFILIATE_REGISTRATION => 'Affiliate Registration',
            self::AFFILIATE_TRANSACTION => 'Affiliate Transaction',
        );
    }

    /**
     * Set privilegeLevel
     *
     * @param integer $privilegeLevel
     * @return EarningType
     */
    public function setPrivilegeLevel($privilegeLevel)
    {
        $this->privilegeLevel = $privilegeLevel;

        return $this;
    }

    /**
     * Get privilegeLevel
     *
     * @return integer 
     */
    public function getPrivilegeLevel()
    {
        return $this->privilegeLevel;
    }
}
