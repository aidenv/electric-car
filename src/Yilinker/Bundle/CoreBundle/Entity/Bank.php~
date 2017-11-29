<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;

/**
 * Bank
 */
class Bank
{
    /**
     * @var integer
     */
    private $bankId;

    /**
     * @var string
     */
    private $bankName;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $bankAccounts;

    /**
     * @var boolean
     */
    private $isEnabled;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bankAccounts = new ArrayCollection();
    }

    /**
     * Get bankId
     *
     * @return integer 
     */
    public function getBankId()
    {
        return $this->bankId;
    }

    /**
     * Set bankName
     *
     * @param string $bankName
     * @return Bank
     */
    public function setBankName($bankName)
    {
        $this->bankName = $bankName;

        return $this;
    }

    /**
     * Get bankName
     *
     * @return string 
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * Add bankAccounts
     *
     * @param BankAccount $bankAccounts
     * @return Bank
     */
    public function addBankAccount(BankAccount $bankAccounts)
    {
        $this->bankAccounts[] = $bankAccounts;

        return $this;
    }

    /**
     * Remove bankAccounts
     *
     * @param BankAccount $bankAccounts
     */
    public function removeBankAccount(BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * Get bankAccounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     * @return Bank
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean 
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }
}
