<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;

/**
 * CustomizedCategory
 */
class CustomizedCategory
{
    /**
     * @var integer
     */
    private $customizedCategoryId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var ProductCategory
     */
    private $productCategory;

    /**
     * @var User
     */
    private $user;

    /**
     * @var integer
     */
    private $sortOrder;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productsLookup;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productsLookup = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get customizedCategoryId
     *
     * @return integer 
     */
    public function getCustomizedCategoryId()
    {
        return $this->customizedCategoryId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CustomizedCategory
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
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return CustomizedCategory
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return CustomizedCategory
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
     * Set productCategory
     *
     * @param ProductCategory $productCategory
     * @return CustomizedCategory
     */
    public function setProductCategory(ProductCategory $productCategory = null)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * Get productCategory
     *
     * @return ProductCategory
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return CustomizedCategory
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
     * Set sortOrder
     *
     * @param integer $sortOrder
     * @return CustomizedCategory
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer 
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set parent
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory $parent
     * @return CustomizedCategory
     */
    public function setParent(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory $children
     * @return CustomizedCategory
     */
    public function addChild(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory $children
     */
    public function removeChild(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildrenBySortOrder()
    {
        $criteria = Criteria::create()
                            ->orderBy(array("sortOrder" => "ASC"));

        $children = $this->getChildren()->matching($criteria);

        return $children;
    }

    /**
     * Add productsLookup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $productsLookup
     * @return CustomizedCategory
     */
    public function addProductsLookup(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $productsLookup)
    {
        $this->productsLookup[] = $productsLookup;

        return $this;
    }

    /**
     * Remove productsLookup
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $productsLookup
     */
    public function removeProductsLookup(\Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup $productsLookup)
    {
        $this->productsLookup->removeElement($productsLookup);
    }

    /**
     * Get productsLookup
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductsLookup()
    {
        return $this->productsLookup;
    }

    /**
     * Get productsLookup by sort order
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductsLookupBySortOrder($forBuyer = false)
    {
        $productsLookup = $this->getProductsLookup();

        if($forBuyer){
            $productsLookup = $productsLookup->filter(
                function($productLookup){
                    $product = $productLookup->getProduct();
                    return $product->getStatus() === Product::ACTIVE;
                }
            );
        }
        
        $criteria = Criteria::create()
                            ->orderBy(array("sortOrder" => "ASC"));

        $productsLookup = $productsLookup->matching($criteria);
        return $productsLookup;
    }
}
