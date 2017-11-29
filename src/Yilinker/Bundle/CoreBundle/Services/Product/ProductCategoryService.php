<?php

namespace Yilinker\Bundle\CoreBundle\Services\Product;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;

class ProductCategoryService
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getChildren($productCategory, $isDelete = false)
    {
        $categories = array();

        if($isDelete){
            $children = $productCategory->getChildren();
        }
        else{
            $children = $productCategory->getActiveChildren();
        }

        foreach($children as $category){

            if($isDelete){
                $hasChildren = $category->getChildren()->count()? true : false;
            }
            else{
                $hasChildren = $category->getActiveChildren()->count()? true : false;
            }

            $categories[$category->getProductCategoryId()] = array(
                "id"                => $category->getProductCategoryId(),
                "name"              => $category->getName(),
                "parent"            => $category->getParent()->getProductCategoryId() == Productcategory::ROOT_CATEGORY_ID ? 
                                            null : $category->getParent()->getProductCategoryId(),
                "hasChildren"       => $hasChildren
            );
        }

        return $categories;
    }

    private function getParentCategory(ProductCategory $productCategory)
    {
        $parent = $productCategory->getParent();

        if(!is_null($parent) && $parent->getProductCategoryId() != ProductCategory::ROOT_CATEGORY_ID){

            return array(
                "id"                => $parent->getProductCategoryId(),
                "name"              => $parent->getName(),
                "parent"            => $this->getParentCategory($parent),
            );
        }

        return null;
    }

    public function generateBreadcrumbs(Product $product)
    {
        $breadcrumbs = array();
        $productCategory = $product->getProductCategory(); 

        if($productCategory){
            $parentCategoryHeirarchy = $this->getParentCategory($productCategory);

            $breadcrumbs = array_reverse($this->iterateHeirarchy($parentCategoryHeirarchy, array(), false));

            array_unshift($breadcrumbs, "All Categories");
            array_push($breadcrumbs, $productCategory->getName());
        }

        return $breadcrumbs;
    }

    public function iterateHeirarchy($category, $categories = array(), $isObject = false)
    {
        if(is_array($category)){
            array_push($categories, $category["name"]);

            if(!is_null($category["parent"])){
                $categories = $this->iterateHeirarchy($category["parent"], $categories);
            }
        }
        else if(
            is_object($category) && 
            $category->getProductCategoryId() != ProductCategory::ROOT_CATEGORY_ID
        ){
            $details = $category->toArray();
            array_push($categories, $details);

            if(!is_null($details["parent"])){
                $categories = $this->iterateHeirarchy($category->getParent(), $categories, true);
            }
        }

        return $categories;
    } 
}
