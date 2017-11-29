<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductVisit
 */
class ProductVisit
{
    /**
     * @var integer
     */
    private $productVisitId;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;


    /**
     * Get productVisitId
     *
     * @return integer 
     */
    public function getProductVisitId()
    {
        return $this->productVisitId;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return ProductVisit
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ProductVisit
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
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductVisit
     */
    public function setProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }
}
