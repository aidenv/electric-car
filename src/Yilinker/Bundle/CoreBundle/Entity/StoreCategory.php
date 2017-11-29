<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StoreCategory
 */
class StoreCategory
{

    /**
     * @var integer
     */
    private $storeCategoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Store
     */
    private $store;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $productCategory;


    /**
     * Get storeCategoryId
     *
     * @return integer 
     */
    public function getStoreCategoryId()
    {
        return $this->storeCategoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return StoreCategory
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
     * @return StoreCategory
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
     * Set store
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Store $store
     * @return StoreCategory
     */
    public function setStore(\Yilinker\Bundle\CoreBundle\Entity\Store $store = null)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Store 
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set productCategory
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory
     * @return StoreCategory
     */
    public function setProductCategory(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCategory 
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }
}
