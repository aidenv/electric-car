<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\Bank;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * BankAccount
 */
class BankAccount
{

    const STATUS_DELETED = 1;

    const STATUS_ACTIVE = 0;

    /**
     * @var integer
     */
    private $bankAccountId;

    /**
     * @var string
     */
    private $accountName;

    /**
     * @var Bank
     */
    private $bank;

    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $accountTitle;

    /**
     * @var integer
     */
    private $accountNumber;

    /**
     * @var boolean
     */
    private $isDefault;

    /**
     * @var boolean
     */
    private $isDelete = '0';

    /**
     * Get bankAccountId
     *
     * @return integer 
     */
    public function getBankAccountId()
    {
        return $this->bankAccountId;
    }

    /**
     * Set accountName
     *
     * @param string $accountName
     * @return BankAccount
     */
    public function setAccountName($accountName)
    {
        $this->accountName = $accountName;

        return $this;
    }

    /**
     * Get accountName
     *
     * @return string 
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * Set bank
     *
     * @param Bank $bank
     * @return BankAccount
     */
    public function setBank(Bank $bank = null)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get bank
     *
     * @return Bank
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return BankAccount
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set accountTitle
     *
     * @param string $accountTitle
     * @return BankAccount
     */
    public function setAccountTitle($accountTitle)
    {
        $this->accountTitle = $accountTitle;

        return $this;
    }

    /**
     * Get accountTitle
     *
     * @return string 
     */
    public function getAccountTitle()
    {
        return $this->accountTitle;
    }

    /**
     * Set accountNumber
     *
     * @param integer $accountNumber
     * @return BankAccount
     */
    public function setAccountNumber($accountNumber)
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Get accountNumber
     *
     * @return integer 
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return BankAccount
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set isDelete
     *
     * @param boolean $isDelete
     * @return BankAccount
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete
     *
     * @return boolean 
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * Convert object to an array
     */
    public function toArray()
    {
        $bank = $this->getBank();
        return array(
            'isDelete'      => $this->getIsDelete(),
            'isDefault'     => $this->getIsDefault(),
            'accountNumber' => $this->getAccountNumber(),
            'accountName'   => $this->getAccountName(),
            'bankName'      => $bank->getBankName(),
            'bankId'        => $bank->getBankId(),
            'bankAccountId' => $this->getBankAccountId(),
            'accountTitle'  => $this->getAccountTitle(),
        );
    }

}
