<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Yilinker\Bundle\CoreBundle\Entity\Product;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\Utility\YilinkerTranslatable as Translatable;
/**
 * Productcategory
 */

class ProductCategory extends Translatable
{

    const ROOT_CATEGORY_ID = 1;

    const SHIPPING_FEE_COMPUTE_AS_PERCENTAGE = false;
    const YILINKER_CHARGE_COMPUTE_AS_PERCENTAGE = true;
    const ADDITIONAL_COST_COMPUTE_AS_PERCENTAGE = true;

    /**
     * @var integer
     */
    private $productCategoryId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     *
     * Cannot be initialized for the GEDMO sluggable behavior to work.
     */
    private $slug;

    /**
     * @var integer
     */
    private $sortOrder = '0';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    private $parent;

    /**
     * @var string
     */
    private $image = '';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $products;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productCategoryTranslations;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\CategoryNestedSet
     */
    private $categoryNestedSet;

    /**
     * @var string
     */
    private $icon = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->productCategoryTranslations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get productCategoryId
     *
     * @return integer
     */
    public function getProductCategoryId()
    {
        return $this->productCategoryId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ProductCategory
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
     * Set slug
     *
     * @param string $slug
     * @return ProductCategory
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set sortOrder
     *
     * @param integer $sortOrder
     * @return ProductCategory
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
     * Set description
     *
     * @param string $description
     * @return ProductCategory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set parent
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $parent
     * @return ProductCategory
     */
    public function setParent(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ProductCategory
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return ProductCategory
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Add children
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $children
     * @return ProductCategory
     */
    public function addChild(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategory $children
     */
    public function removeChild(\Yilinker\Bundle\CoreBundle\Entity\ProductCategory $children)
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
     * Get Active children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActiveChildren()
    {
        $eq = Criteria::expr()->eq("isDelete", false);
        $neq = Criteria::expr()->neq("productCategoryId", self::ROOT_CATEGORY_ID);
        $criteria = Criteria::create()->andWhere($eq)->andWhere($neq);

        return $this->getChildren()->matching($criteria);
    }

    /**
     * Add products
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $products
     * @return ProductCategory
     */
    public function addProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $products)
    {
        $this->products[] = $products;

        return $this;
    }

    /**
     * Remove products
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $products
     */
    public function removeProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $products)
    {
        $this->products->removeElement($products);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Get User Products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserProducts($user, $status = Product::ACTIVE)
    {
        $products = $this->getProducts()->filter(
            function($product) use ($user, $status){
                if($product->getUser() === $user && $product->getStatus() == $status){
                    return true;
                }

                return false;
            }
        );

        return $products;
    }

    /**
     * Convert the object to an array
     */
    public function toArray()
    {
        return array(
            'id'           => $this->getProductCategoryId(),
            'name'         => $this->getName(),
            'parent'       => !is_null($this->getParent())? $this->getParent()->getProductCategoryId() : null,
            'description'  => $this->getDescription(),
            'hasChildren'  => $this->getActiveChildren()->count()? true : false
        );
    }

    /**
     * @var string
     */
    private $referenceNumber;


    /**
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return ProductCategory
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    /**
     * Get referenceNumber
     *
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }
    /**
     * @var boolean
     */
    private $isDelete = false;


    /**
     * Set isDelete
     *
     * @param boolean $isDelete
     * @return ProductCategory
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
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;


    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ProductCategory
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
     * @return ProductCategory
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
     * Add productCategoryTranslations
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategoryTranslation $productCategoryTranslations
     * @return ProductCategory
     */
    public function addProductCategoryTranslation(ProductCategoryTranslation $productCategoryTranslations)
    {
        $this->productCategoryTranslations[] = $productCategoryTranslations;

        return $this;
    }

    /**
     * Remove productCategoryTranslations
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ProductCategoryTranslation $productCategoryTranslations
     */
    public function removeProductCategoryTranslation(ProductCategoryTranslation $productCategoryTranslations)
    {
        $this->productCategoryTranslations->removeElement($productCategoryTranslations);
    }

    /**
     * Get productCategoryTranslations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductCategoryTranslations()
    {
        return $this->productCategoryTranslations;
    }

    /**
     * Get single language translation
     *
     * @param Language $language
     * @return ProductCategoryLanguage
     */
    public function getProductCategoryTranslation(Language $language)
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("language", $language));

        return $this->productCategoryTranslations->matching($criteria)->first();
    }

    /**
     * Set icon
     *
     * @param string $icon
     * @return ProductCategory
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set categoryNestedSet
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\CategoryNestedSet $categoryNestedSet
     * @return ProductCategory
     */
    public function setCategoryNestedSet(\Yilinker\Bundle\CoreBundle\Entity\CategoryNestedSet $categoryNestedSet = null)
    {
        $this->categoryNestedSet = $categoryNestedSet;

        return $this;
    }

    /**
     * Get categoryNestedSet
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\CategoryNestedSet
     */
    public function getCategoryNestedSet()
    {
        return $this->categoryNestedSet;
    }

    public function __toString()
    {
        return $this->getName();
    }
    /**
     * @var string
     */
    private $yilinkerCharge = '0';

    /**
     * @var string
     */
    private $additionalCost = '0';

    /**
     * @var string
     */
    private $handlingFee = '0';

    /**
     * Set yilinkerCharge
     *
     * @param string $yilinkerCharge
     * @return ProductCategory
     */
    public function setYilinkerCharge($yilinkerCharge)
    {
        $this->yilinkerCharge = $yilinkerCharge;

        return $this;
    }

    /**
     * Get yilinkerCharge
     *
     * @return string 
     */
    public function getYilinkerCharge()
    {
        return $this->yilinkerCharge;
    }

    /**
     * Set additionalCost
     *
     * @param string $additionalCost
     * @return ProductCategory
     */
    public function setAdditionalCost($additionalCost)
    {
        $this->additionalCost = $additionalCost;

        return $this;
    }

    /**
     * Get additionalCost
     *
     * @return string 
     */
    public function getAdditionalCost()
    {
        return $this->additionalCost;
    }

    /**
     * Set handlingFee
     *
     * @param string $handlingFee
     * @return ProductCategory
     */
    public function setHandlingFee($handlingFee)
    {
        $this->handlingFee = $handlingFee;

        return $this;
    }

    /**
     * Get handlingFee
     *
     * @return string 
     */
    public function getHandlingFee()
    {
        return $this->handlingFee;
    }
}
