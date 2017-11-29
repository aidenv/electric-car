<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApiAccessLog
 */
class ApiAccessLog
{    
    const API_TYPE_TRADING_BRAND = "010";

    const API_TYPE_TRADING_SUPPLIER = "020";

    const API_TYPE_TRADING_PRODUCT = "030";

    const API_TYPE_TRADING_CATEGORY = "040";

    const API_TYPE_TRADING_COUNTRY = "050";

    const API_TYPE_EXPRESS_PRODUCT = "060";

    /**
     * @var int
     */
    private $apiAccessLogId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var integer
     */
    private $apiType;

    /**
     * @var string
     */
    private $data = '';


    /**
     * Get apiAccessLogId
     *
     * @return \int 
     */
    public function getApiAccessLogId()
    {
        return $this->apiAccessLogId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ApiAccessLog
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

    /**
     * Set apiType
     *
     * @param integer $apiType
     * @return ApiAccessLog
     */
    public function setApiType($apiType)
    {
        $this->apiType = $apiType;

        return $this;
    }

    /**
     * Get apiType
     *
     * @return integer 
     */
    public function getApiType()
    {
        return $this->apiType;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return ApiAccessLog
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        return $this->data;
    }
}
