<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Yilinker\Bundle\CoreBundle\Entity\Product;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCountry
 */
class ProductCountry
{
    /**
     * @var integer
     */
    private $productCountryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var integer
     */
    private $status = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    private $country;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;


    /**
     * Get productCountryId
     *
     * @return integer
     */
    public function getProductCountryId()
    {
        return $this->productCountryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ProductCountry
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
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return ProductCountry
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ProductCountry
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set country
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country $country
     * @return ProductCountry
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
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductCountry
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
