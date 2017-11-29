<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Package
 */
class PackageDetail
{

    /**
     * @var integer
     */
    private $packageDetailId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Package
     */
    private $package;
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\OrderProduct
     */
    private $orderProduct;
    
    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * Get packageDetailId
     *
     * @return integer 
     */
    public function getPackageDetailId()
    {
        return $this->packageDetailId;
    }

    /**
     * Set package
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Package $package
     * @return PackageDetail
     */
    public function setPackage(\Yilinker\Bundle\CoreBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Package 
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set orderProduct
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct
     * @return PackageDetail
     */
    public function setOrderProduct(\Yilinker\Bundle\CoreBundle\Entity\OrderProduct $orderProduct = null)
    {
        $this->orderProduct = $orderProduct;

        return $this;
    }

    /**
     * Get orderProduct
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\OrderProduct 
     */
    public function getOrderProduct()
    {
        return $this->orderProduct;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return PackageDetail
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
     * @var integer
     */
    private $quantity;


    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return PackageDetail
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
}
