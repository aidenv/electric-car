<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductRemarks
 */
class ProductRemarks
{

    /**
     * @var integer
     */
    private $productRemarksId;

    /**
     * @var string
     */
    private $remarks;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var integer
     */
    private $productStatus;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Product
     */
    private $product;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;

    /**
     * @var string
     */
    private $countryCode = 'ph';

    /**
     * Get productRemarksId
     *
     * @return integer 
     */
    public function getProductRemarksId()
    {
        return $this->productRemarksId;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     * @return ProductRemarks
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string 
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ProductRemarks
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
     * Set productStatus
     *
     * @param integer $productStatus
     * @return ProductRemarks
     */
    public function setProductStatus($productStatus)
    {
        $this->productStatus = $productStatus;

        return $this;
    }

    /**
     * Get productStatus
     *
     * @return integer 
     */
    public function getProductStatus()
    {
        return $this->productStatus;
    }

    /**
     * Set product
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $product
     * @return ProductRemarks
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

    /**
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return ProductRemarks
     */
    public function setAdminUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser = null)
    {
        $this->adminUser = $adminUser;

        return $this;
    }

    /**
     * Get adminUser
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getAdminUser()
    {
        return $this->adminUser;
    }

    /**
     * format to array
     *
     * @return array
     */
    public function toArray ()
    {
        return array(
            'productRemarksId'   => $this->getProductRemarksId(),
            'remarks'            => $this->getRemarks(),
            'adminUserFullName'  => $this->getAdminUser()->getFullName(),
            'formattedDateAdded' => $this->getDateAdded()->format('h:i:s, Y/m/d'),
            'productStatus'      => $this->getProductStatus()
        );
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     * @return ProductRemarks
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }
}
