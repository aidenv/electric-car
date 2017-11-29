<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * systemcategory
 */
class systemcategory
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $systemCategoryId = 0;

    /**
     * @var integer
     */
    private $systemCategoryParentId = 0;

    /**
     * @var string
     */
    private $systemCategoryNameCN;

    /**
     * @var string
     */
    private $systemCategoryDescriptionCN = '';

    /**
     * @var \DateTime
     */
    private $updateTime;

    /**
     * @var string
     */
    private $systemCategoryDescriptionUS = '';

    /**
     * @var string
     */
    private $systemCategoryNameUS = '';

    /**
     * @var integer
     */
    private $nestedSetLeft = 0;

    /**
     * @var integer
     */
    private $nestedSetRight = 0;

    /**
     * @var string
     */
    private $systemCategoryNameTH = '';

    /**
     * @var string
     */
    private $systemCategoryNameMS = '';

    /**
     * @var string
     */
    private $systemCategoryNameVI = '';

    /**
     * @var string
     */
    private $systemCategoryNameIDN = '';

    /**
     * @var string
     */
    private $systemCategoryNameCHT = '';

    /**
     * @var string
     */
    private $systemCategoryNameDE = '';

    /**
     * @var string
     */
    private $systemCategoryNameFR = '';

    /**
     * @var string
     */
    private $systemCategoryNameIT = '';

    /**
     * @var string
     */
    private $systemCategoryNameMY = '';

    /**
     * @var string
     */
    private $systemCategoryNameES = '';

    /**
     * @var string
     */
    private $systemCategoryNamePT = '';

    /**
     * @var string
     */
    private $systemCategoryNameRU = '';

    /**
     * @var string
     */
    private $systemCategoryNameJA = '';

    /**
     * @var string
     */
    private $systemCategoryNameKO = '';

    /**
     * @var string
     */
    private $systemCategoryNameAR = '';


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set systemCategoryId
     *
     * @param integer $systemCategoryId
     * @return systemcategory
     */
    public function setSystemCategoryId($systemCategoryId)
    {
        $this->systemCategoryId = $systemCategoryId;

        return $this;
    }

    /**
     * Get systemCategoryId
     *
     * @return integer 
     */
    public function getSystemCategoryId()
    {
        return $this->systemCategoryId;
    }

    /**
     * Set systemCategoryParentId
     *
     * @param integer $systemCategoryParentId
     * @return systemcategory
     */
    public function setSystemCategoryParentId($systemCategoryParentId)
    {
        $this->systemCategoryParentId = $systemCategoryParentId;

        return $this;
    }

    /**
     * Get systemCategoryParentId
     *
     * @return integer 
     */
    public function getSystemCategoryParentId()
    {
        return $this->systemCategoryParentId;
    }

    /**
     * Set systemCategoryNameCN
     *
     * @param string $systemCategoryNameCN
     * @return systemcategory
     */
    public function setSystemCategoryNameCN($systemCategoryNameCN)
    {
        $this->systemCategoryNameCN = $systemCategoryNameCN;

        return $this;
    }

    /**
     * Get systemCategoryNameCN
     *
     * @return string 
     */
    public function getSystemCategoryNameCN()
    {
        return $this->systemCategoryNameCN;
    }

    /**
     * Set systemCategoryDescriptionCN
     *
     * @param string $systemCategoryDescriptionCN
     * @return systemcategory
     */
    public function setSystemCategoryDescriptionCN($systemCategoryDescriptionCN)
    {
        $this->systemCategoryDescriptionCN = $systemCategoryDescriptionCN;

        return $this;
    }

    /**
     * Get systemCategoryDescriptionCN
     *
     * @return string 
     */
    public function getSystemCategoryDescriptionCN()
    {
        return $this->systemCategoryDescriptionCN;
    }

    /**
     * Set updateTime
     *
     * @param \DateTime $updateTime
     * @return systemcategory
     */
    public function setUpdateTime($updateTime)
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return \DateTime 
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * Set systemCategoryDescriptionUS
     *
     * @param string $systemCategoryDescriptionUS
     * @return systemcategory
     */
    public function setSystemCategoryDescriptionUS($systemCategoryDescriptionUS)
    {
        $this->systemCategoryDescriptionUS = $systemCategoryDescriptionUS;

        return $this;
    }

    /**
     * Get systemCategoryDescriptionUS
     *
     * @return string 
     */
    public function getSystemCategoryDescriptionUS()
    {
        return $this->systemCategoryDescriptionUS;
    }

    /**
     * Set systemCategoryNameUS
     *
     * @param string $systemCategoryNameUS
     * @return systemcategory
     */
    public function setSystemCategoryNameUS($systemCategoryNameUS)
    {
        $this->systemCategoryNameUS = $systemCategoryNameUS;

        return $this;
    }

    /**
     * Get systemCategoryNameUS
     *
     * @return string 
     */
    public function getSystemCategoryNameUS()
    {
        return $this->systemCategoryNameUS;
    }

    /**
     * Set nestedSetLeft
     *
     * @param integer $nestedSetLeft
     * @return systemcategory
     */
    public function setNestedSetLeft($nestedSetLeft)
    {
        $this->nestedSetLeft = $nestedSetLeft;

        return $this;
    }

    /**
     * Get nestedSetLeft
     *
     * @return integer 
     */
    public function getNestedSetLeft()
    {
        return $this->nestedSetLeft;
    }

    /**
     * Set nestedSetRight
     *
     * @param integer $nestedSetRight
     * @return systemcategory
     */
    public function setNestedSetRight($nestedSetRight)
    {
        $this->nestedSetRight = $nestedSetRight;

        return $this;
    }

    /**
     * Get nestedSetRight
     *
     * @return integer 
     */
    public function getNestedSetRight()
    {
        return $this->nestedSetRight;
    }

    /**
     * Set systemCategoryNameTH
     *
     * @param string $systemCategoryNameTH
     * @return systemcategory
     */
    public function setSystemCategoryNameTH($systemCategoryNameTH)
    {
        $this->systemCategoryNameTH = $systemCategoryNameTH;

        return $this;
    }

    /**
     * Get systemCategoryNameTH
     *
     * @return string 
     */
    public function getSystemCategoryNameTH()
    {
        return $this->systemCategoryNameTH;
    }

    /**
     * Set systemCategoryNameMS
     *
     * @param string $systemCategoryNameMS
     * @return systemcategory
     */
    public function setSystemCategoryNameMS($systemCategoryNameMS)
    {
        $this->systemCategoryNameMS = $systemCategoryNameMS;

        return $this;
    }

    /**
     * Get systemCategoryNameMS
     *
     * @return string 
     */
    public function getSystemCategoryNameMS()
    {
        return $this->systemCategoryNameMS;
    }

    /**
     * Set systemCategoryNameVI
     *
     * @param string $systemCategoryNameVI
     * @return systemcategory
     */
    public function setSystemCategoryNameVI($systemCategoryNameVI)
    {
        $this->systemCategoryNameVI = $systemCategoryNameVI;

        return $this;
    }

    /**
     * Get systemCategoryNameVI
     *
     * @return string 
     */
    public function getSystemCategoryNameVI()
    {
        return $this->systemCategoryNameVI;
    }

    /**
     * Set systemCategoryNameIDN
     *
     * @param string $systemCategoryNameIDN
     * @return systemcategory
     */
    public function setSystemCategoryNameIDN($systemCategoryNameIDN)
    {
        $this->systemCategoryNameIDN = $systemCategoryNameIDN;

        return $this;
    }

    /**
     * Get systemCategoryNameIDN
     *
     * @return string 
     */
    public function getSystemCategoryNameIDN()
    {
        return $this->systemCategoryNameIDN;
    }

    /**
     * Set systemCategoryNameCHT
     *
     * @param string $systemCategoryNameCHT
     * @return systemcategory
     */
    public function setSystemCategoryNameCHT($systemCategoryNameCHT)
    {
        $this->systemCategoryNameCHT = $systemCategoryNameCHT;

        return $this;
    }

    /**
     * Get systemCategoryNameCHT
     *
     * @return string 
     */
    public function getSystemCategoryNameCHT()
    {
        return $this->systemCategoryNameCHT;
    }

    /**
     * Set systemCategoryNameDE
     *
     * @param string $systemCategoryNameDE
     * @return systemcategory
     */
    public function setSystemCategoryNameDE($systemCategoryNameDE)
    {
        $this->systemCategoryNameDE = $systemCategoryNameDE;

        return $this;
    }

    /**
     * Get systemCategoryNameDE
     *
     * @return string 
     */
    public function getSystemCategoryNameDE()
    {
        return $this->systemCategoryNameDE;
    }

    /**
     * Set systemCategoryNameFR
     *
     * @param string $systemCategoryNameFR
     * @return systemcategory
     */
    public function setSystemCategoryNameFR($systemCategoryNameFR)
    {
        $this->systemCategoryNameFR = $systemCategoryNameFR;

        return $this;
    }

    /**
     * Get systemCategoryNameFR
     *
     * @return string 
     */
    public function getSystemCategoryNameFR()
    {
        return $this->systemCategoryNameFR;
    }

    /**
     * Set systemCategoryNameIT
     *
     * @param string $systemCategoryNameIT
     * @return systemcategory
     */
    public function setSystemCategoryNameIT($systemCategoryNameIT)
    {
        $this->systemCategoryNameIT = $systemCategoryNameIT;

        return $this;
    }

    /**
     * Get systemCategoryNameIT
     *
     * @return string 
     */
    public function getSystemCategoryNameIT()
    {
        return $this->systemCategoryNameIT;
    }

    /**
     * Set systemCategoryNameMY
     *
     * @param string $systemCategoryNameMY
     * @return systemcategory
     */
    public function setSystemCategoryNameMY($systemCategoryNameMY)
    {
        $this->systemCategoryNameMY = $systemCategoryNameMY;

        return $this;
    }

    /**
     * Get systemCategoryNameMY
     *
     * @return string 
     */
    public function getSystemCategoryNameMY()
    {
        return $this->systemCategoryNameMY;
    }

    /**
     * Set systemCategoryNameES
     *
     * @param string $systemCategoryNameES
     * @return systemcategory
     */
    public function setSystemCategoryNameES($systemCategoryNameES)
    {
        $this->systemCategoryNameES = $systemCategoryNameES;

        return $this;
    }

    /**
     * Get systemCategoryNameES
     *
     * @return string 
     */
    public function getSystemCategoryNameES()
    {
        return $this->systemCategoryNameES;
    }

    /**
     * Set systemCategoryNamePT
     *
     * @param string $systemCategoryNamePT
     * @return systemcategory
     */
    public function setSystemCategoryNamePT($systemCategoryNamePT)
    {
        $this->systemCategoryNamePT = $systemCategoryNamePT;

        return $this;
    }

    /**
     * Get systemCategoryNamePT
     *
     * @return string 
     */
    public function getSystemCategoryNamePT()
    {
        return $this->systemCategoryNamePT;
    }

    /**
     * Set systemCategoryNameRU
     *
     * @param string $systemCategoryNameRU
     * @return systemcategory
     */
    public function setSystemCategoryNameRU($systemCategoryNameRU)
    {
        $this->systemCategoryNameRU = $systemCategoryNameRU;

        return $this;
    }

    /**
     * Get systemCategoryNameRU
     *
     * @return string 
     */
    public function getSystemCategoryNameRU()
    {
        return $this->systemCategoryNameRU;
    }

    /**
     * Set systemCategoryNameJA
     *
     * @param string $systemCategoryNameJA
     * @return systemcategory
     */
    public function setSystemCategoryNameJA($systemCategoryNameJA)
    {
        $this->systemCategoryNameJA = $systemCategoryNameJA;

        return $this;
    }

    /**
     * Get systemCategoryNameJA
     *
     * @return string 
     */
    public function getSystemCategoryNameJA()
    {
        return $this->systemCategoryNameJA;
    }

    /**
     * Set systemCategoryNameKO
     *
     * @param string $systemCategoryNameKO
     * @return systemcategory
     */
    public function setSystemCategoryNameKO($systemCategoryNameKO)
    {
        $this->systemCategoryNameKO = $systemCategoryNameKO;

        return $this;
    }

    /**
     * Get systemCategoryNameKO
     *
     * @return string 
     */
    public function getSystemCategoryNameKO()
    {
        return $this->systemCategoryNameKO;
    }

    /**
     * Set systemCategoryNameAR
     *
     * @param string $systemCategoryNameAR
     * @return systemcategory
     */
    public function setSystemCategoryNameAR($systemCategoryNameAR)
    {
        $this->systemCategoryNameAR = $systemCategoryNameAR;

        return $this;
    }

    /**
     * Get systemCategoryNameAR
     *
     * @return string 
     */
    public function getSystemCategoryNameAR()
    {
        return $this->systemCategoryNameAR;
    }
}
