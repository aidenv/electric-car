<?php
namespace Yilinker\Bundle\CoreBundle\Services\CustomizedCategory;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategory;
use Yilinker\Bundle\CoreBundle\Entity\CustomizedCategoryProductLookup;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;

class CustomizedCategoryService
{
    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    public function __construct(EntityManager $entityManager, AssetsHelper $assetsHelper)
    {
        $this->em = $entityManager;
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * @param User $user
     * @return array
     */
    public function getCustomCategoriesHierarchy(User $user, $queryString = "")
    {
        $store = $user->getStore();

        if (!$store->getHasCustomCategory()) {
            $repository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
            $categoriesArray = $repository->getParentCategoriesWithProductsByUser($user->getUserId(), $queryString);

            $categoryIds = array();
            foreach ($categoriesArray as $category) {
                array_push($categoryIds, $category["productCategoryId"]);
            }

            $categories = $repository->loadProductCategoriesIn($categoryIds);

        } else {
            $repository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $categories = $repository->loadParentCustomCategories($user, $queryString);
        }

        return $categories;
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getScalarCustomCategories(User $user)
    {
        $customCategories = array();
        $categories = $this->getCustomCategoriesHierarchy($user, "");

        foreach ($categories as $category) {
            $categoryId = $category instanceof ProductCategory ? $category->getProductCategoryId() : $category->getCustomizedCategoryId();
            $parent = !is_null($category->getParent()) ? $category->getParent() : null;
            $parentId = null;

            if (!is_null($parent) && $parent instanceof ProductCategory) {
                $parentId = null;
            } elseif (!is_null($parent) && $parent instanceof CustomizedCategory) {
                $parentId = $parent->getCustomizedCategoryId();
            }

            array_push($customCategories, array(
                "categoryId" => $categoryId,
                "name" => $category->getName(),
                "parentId" => $parentId,
                "sortOrder" => $category->getSortOrder()
            ));
        }

        return $customCategories;
    }

    public function getFullCustomCategoryHierarchy(User $user, $forBuyer = false, $queryString = "")
    {
        $customCategories = array();
        $categories = $this->getCustomCategoriesHierarchy($user, $queryString);
        $hasCustomCategory = $user->getStore()->getHasCustomCategory();

        foreach ($categories as $index => $category) {

            $details = array(
                "categoryId"    => $hasCustomCategory? $category->getCustomizedCategoryId() : $category->getProductCategoryId(),
                "name"          => $category->getName(),
                "parentId"      => null,
                "sortOrder"     => $hasCustomCategory? $category->getSortOrder() : $index,
                "products"      => array(),
                "subcategories" => array()
            );

            if($hasCustomCategory){
                $productsLookup = $category->getProductsLookupBySortOrder($forBuyer);

                foreach ($productsLookup as $productLookup) {
                    $product = $productLookup->getProduct();
                    $imageLocation = $product->getPrimaryImageLocation();

                    $productImageUrl = "";
                    if($imageLocation != ""){
                        $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
                    }

                    array_push($details["products"], array(
                        "productId" => $product->getProductId(),
                        "productName" => $product->getName(),
                        "image" => $productImageUrl
                    ));
                }

                $subcategories = $category->getChildrenBySortOrder();
                if(!empty($subcategories)){
                    foreach ($subcategories as $subcategory) {
                        $subcategoryDetails = array(
                            "categoryId"    => $subcategory->getCustomizedCategoryId(),
                            "name"          => $subcategory->getName(),
                            "parentId"      => $category->getCustomizedCategoryId(),
                            "sortOrder"     => $subcategory->getSortOrder(),
                            "products"      => array(),
                            "subcategories" => array()
                        );

                        $productsLookup = $category->getProductsLookupBySortOrder($forBuyer);

                        foreach ($productsLookup as $productLookup) {
                            $product = $productLookup->getProduct();
                            $imageLocation = $product->getPrimaryImageLocation();

                            $productImageUrl = "";
                            if($imageLocation != ""){
                                $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
                            }

                            array_push($subcategoryDetails["products"], array(
                                "productId" => $product->getProductId(),
                                "productName" => $product->getName(),
                                "image" => $productImageUrl
                            ));
                        }

                        array_push($details["subcategories"], $subcategoryDetails);
                    }
                }
            }

            array_push($customCategories, $details);
        }

        return $customCategories;
    }

    public function addCategory(User $user, $categoryName, $products, $parentId, $subcategories)
    {
        $store = $user->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        if ($hasCustomCategory) {
            $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $category = $customCategoryRepository->findOneBy(array("name" => $categoryName, "user" => $user));
        } else {
            $productCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
            $parent = $productCategoryRepository->find(ProductCategory::ROOT_CATEGORY_ID);
            $category = $productCategoryRepository->findOneBy(array(
                "name" => $categoryName,
                "parent" => $parent
            ));
        }

        if (!is_null($category)) {
            return array(
                "isSuccessful" => false,
                "message" => "Category already exists.",
                "data" => array(
                    "line" => 141
                )
            );
        }


        $this->em->beginTransaction();

        if (!empty($subcategories) && ($parentId == 0 OR is_null($parentId))) {
            $data = array();
            $stashedCategoryNames = array();

            foreach($subcategories as $subcategory){
                $decodedData = $subcategory;

                $subcategoryId = array_key_exists("categoryId", $decodedData)? $decodedData["categoryId"] : 0;
                $subcategoryName = array_key_exists("categoryName", $decodedData)? $decodedData["categoryName"] : null;

                $matches = $this->checkIfCategoryExists($user, $subcategoryId, $subcategoryName, $hasCustomCategory);

                array_push($data, $decodedData);

                if(!empty($matches) OR trim($subcategoryName) == ""){
                    return array(
                        "isSuccessful" => false,
                        "message" => "Category already exists.",
                        "data" => array(
                            "line" => 168
                        )
                    );
                }

                if(!in_array($subcategoryName, $stashedCategoryNames)){
                    array_push($stashedCategoryNames, $subcategoryName);
                }
                else{
                    return array(
                        "isSuccessful" => false,
                        "message" => "Category already exists.",
                        "data" => array(
                            "line" => 181
                        )
                    );
                }
            }

            $response = $this->addCategoryWithSubcategories($user, $categoryName, $products, $data, $hasCustomCategory);
        } else {
            $response = $this->addCategoryWithParent($user, $categoryName, $products, $parentId, $hasCustomCategory);
        }

        if($response["isSuccessful"]){
            $this->em->commit();
        }
        else{
            $this->em->rollback();
        }

        return $response;
    }

    public function updateCategory(User $user, $categoryId, $categoryName, $products, $parentId, $subcategories)
    {
        $store = $user->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        if ($hasCustomCategory) {
            $repository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $args = array("customizedCategoryId" => $categoryId, "user" => $user);
        } else {
            $repository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
            $args = array("productCategoryId" => $categoryId);
        }

        $category = $repository->findOneBy($args);

        if ($category instanceof CustomizedCategory) {
            $matches = $repository->getCustomizedCategoryByName($categoryId, $categoryName, $user);

            if (!empty($matches)) {
                return array(
                    "isSuccessful" => false,
                    "message" => "Category already exists.",
                    "data" => array(
                        "line" => 225
                    )
                );
            }
        } elseif ($category instanceof ProductCategory) {
            $matches = $repository->getProductCategoryByName($categoryId, $categoryName);

            if (!empty($matches)) {
                return array(
                    "isSuccessful" => false,
                    "message" => "Category already exists.",
                    "data" => array(
                        "line" => 237
                    )
                );
            }
        }

        if (!is_null($category)) {
            $childrenCount = count($category->getChildren());
            if (!empty($subcategories) OR $childrenCount > 0 && ($parentId == 0 || is_null($parentId))){
                $data = array();
                $stashedCategoryNames = array();
                foreach($subcategories as $subcategory){

                    $subcategoryId = array_key_exists("categoryId", $subcategory)? $subcategory["categoryId"] : 0;
                    $subcategoryName = array_key_exists("categoryName", $subcategory)? $subcategory["categoryName"] : null;

                    $matches = $this->checkIfCategoryExists($user, $subcategoryId, $subcategoryName, $hasCustomCategory);

                    array_push($data, $subcategory);

                    if(!empty($matches) OR trim($subcategoryName) == ""){
                        return array(
                            "isSuccessful" => false,
                            "message" => "Category already exists.",
                            "data" => array(
                                "line" => 262
                            )
                        );
                    }

                    if(!in_array($subcategoryName, $stashedCategoryNames)){
                        array_push($stashedCategoryNames, $subcategoryName);
                    }
                    else{
                        return array(
                            "isSuccessful" => false,
                            "message" => "Category already exists.",
                            "data" => array(
                                "line" => 275
                            )
                        );
                    }
                }

                return $this->updateCategoryWithSubcategories($user, $category, $categoryName, $products, $data, $repository, $hasCustomCategory);
            } else {
                return $this->updateCategoryWithParent($user, $category, $categoryName, $products, $parentId, $repository, $hasCustomCategory);
            }
        }

        return array(
            "isSuccessful" => false,
            "message" => "Invalid Category.",
            "data" => array(
                "line" => 277
            )
        );
    }

    public function addCategoryWithSubcategories(User $user, $categoryName, $products, $subcategories, $hasCustomCategory)
    {
        $store = $user->getStore();
        $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");

        if ($hasCustomCategory) {
            //add new
            $customCategory = $this->addCustomCategory($user, $categoryName, null, null, 0);
            $addedSubcategoriesResponse = $this->assignParentToCustomCategory($user, $customCategory, $subcategories);

            if(!$addedSubcategoriesResponse["isSuccessful"]){
                return $addedSubcategoriesResponse;
            }

            $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customCategories = $customCategoryRepository->loadParentCustomCategories($user);

            $sortOrder = count($customCategories) - 1;
            $customCategory->setSortOrder($sortOrder);

            if (!empty($products)) {
                $response = $this->assignProductsToCategory($productRepository, $customCategory, $user, $products);
                if (!$response["isSuccessful"]) {
                    return $response;
                }
            }

            $addedSubcategories = $addedSubcategoriesResponse["data"]["categories"];
            $childrenCount = count($addedSubcategories);
            foreach($subcategories as $subcategory){
                $productCategoryId = array_key_exists("categoryId", $subcategory)? $subcategory["categoryId"] : null;
                if(array_key_exists($productCategoryId, $addedSubcategories)){
                    if (!empty($products)) {
                        $response = $this->assignProductsToCategory($productRepository, $addedSubcategories[$productCategoryId], $user, $subcategory["products"]);
                        if (!$response["isSuccessful"]) {
                            return $response;
                        }
                    }
                }
                elseif(is_null($productCategoryId)){
                    $addedSubcategory = $this->addCustomCategory($user, $subcategory["categoryName"], null, $customCategory, $childrenCount);
                    if (!empty($products)) {
                        $response = $this->assignProductsToCategory($productRepository, $addedSubcategory, $user, $subcategory["products"]);
                        if (!$response["isSuccessful"]) {
                            return $response;
                        }
                    }
                    $childrenCount++;
                }
            }

            $this->em->persist($customCategory);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category added.",
                "data" => array()
            );
        } else {
            //add new with copy
            $customCategory = $this->addCustomCategory($user, $categoryName, null, null, 0);

            $subcategoryData = $this->constructSubcategoryData($subcategories);
            $addedSubcategoriesResponse = $this->copyProductCategoriesWithSubcategories($user, $subcategoryData, $customCategory);

            $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customCategories = $customCategoryRepository->loadParentCustomCategories($user);
            $sortOrder = count($customCategories) - 1;
            $customCategory->setSortOrder($sortOrder);

            if (!$addedSubcategoriesResponse["isSuccessful"]) {
                return $addedSubcategoriesResponse;
            }

            if (!empty($products)) {
                $response = $this->assignProductsToCategory($productRepository, $customCategory, $user, $products);
                if (!$response["isSuccessful"]) {
                    return $response;
                }
            }

            $addedSubcategories = $addedSubcategoriesResponse["data"]["categories"];
            $childrenCount = count($addedSubcategories);
            foreach($subcategories as $subcategory){
                $productCategoryId = array_key_exists("categoryId", $subcategory)? $subcategory["categoryId"] : null;
                if(array_key_exists($productCategoryId, $addedSubcategories) && !empty($subcategory["products"])){
                    $response = $this->assignProductsToCategory($productRepository, $addedSubcategories[$productCategoryId], $user, $subcategory["products"]);
                    if (!$response["isSuccessful"]) {
                        return $response;
                    }
                }
                elseif(is_null($productCategoryId)){
                    $addedSubcategory = $this->addCustomCategory($user, $subcategory["categoryName"], null, $customCategory, $childrenCount);
                    if (!empty($products)) {
                        $response = $this->assignProductsToCategory($productRepository, $addedSubcategory, $user, $subcategory["products"]);
                        if (!$response["isSuccessful"]) {
                            return $response;
                        }
                    }
                    $childrenCount++;
                }
            }

            $store->setHasCustomCategory(true);
            $this->em->persist($store);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category added.",
                "data" => array()
            );
        }
    }

    public function addCategoryWithParent(User $user, $categoryName, $products, $parentId, $hasCustomCategory)
    {
        $store = $user->getStore();

        if ($hasCustomCategory) {
            //add new
            $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");

            $parent = null;
            if (!is_null($parentId)) {
                $parent = $customCategoryRepository->find($parentId);
            }

            if(!is_null($parent)){
                $siblings = $parent->getChildrenBySortOrder();
                $sortOrder = count($siblings);
            }
            else{
                $customCategories = $customCategoryRepository->loadParentCustomCategories($user);
                $sortOrder = count($customCategories);
            }

            $customCategory = $this->addCustomCategory($user, $categoryName, null, $parent, $sortOrder);

            if (!empty($products)) {
                $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
                $response = $this->assignProductsToCategory($productRepository, $customCategory, $user, $products);

                if (!$response["isSuccessful"]) {
                    return $response;
                }
            }

            return array(
                "isSuccessful" => true,
                "message" => "Category added.",
                "data" => array()
            );
        } else {
            //add new with copy
            $parent = null;
            if (!is_null($parentId)) {
                $productCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
                $parent = $productCategoryRepository->find($parentId);
            }

            $response = $this->copyProductCategories($user, null, $parent, true);

            if (!$response["isSuccessful"]) {
                return $response;
            }

            if(!is_null($parent)){
                $customCategory = $this->addCustomCategory($user, $categoryName, null, $response["data"]["parent"], 0);
            }
            else{
                $customCategory = $this->addCustomCategory($user, $categoryName, null, $response["data"]["parent"], $response["data"]["parentCategoriesCount"]);
            }

            if (!empty($products)) {
                $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
                $response = $this->assignProductsToCategory($productRepository, $customCategory, $user, $products);

                if (!$response["isSuccessful"]) {
                    return $response;
                }
            }

            $store->setHasCustomCategory(true);
            $this->em->persist($store);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category added.",
                "data" => array()
            );
        }
    }

    public function deleteCustomCategory(User $user, $categoryId, $hasCustomCategory)
    {
        $store = $user->getStore();
        $productCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
        $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");

        if($hasCustomCategory){
            $customCategory = $customCategoryRepository->find($categoryId);

            if(is_null($customCategory) OR $customCategory->getUser() != $user){
                return array(
                    "isSuccessful" => false,
                    "message" => "Category not found",
                    "data" => array(
                        "line" => 496
                    )
                );
            }

            foreach($customCategory->getProductsLookup() as $productLookup){
                $customCategory->removeProductsLookup($productLookup);
                $this->em->remove($productLookup);
            }

            $children = $customCategory->getChildrenBySortOrder();
            $parent = $customCategory->getParent();
            if(count($children) > 0){
                $customCategories = $customCategoryRepository->loadParentCustomCategories($user);
                $customCategoriesCount = count($customCategories);

                $this->removeExcludedCategories($children, $customCategory, array(), $customCategoriesCount);
            }
            elseif(!is_null($parent)){
                $parentChildren = $parent->getChildrenBySortOrder();
                $this->resortSiblings($parentChildren, $customCategory);
            }

            $this->resortParentCategories($user, $customCategoryRepository, $customCategory);

            $this->em->remove($customCategory);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category deleted.",
                "data" => array()
            );
        }
        else{
            //add new with copy
            $productCategory = $productCategoryRepository->find($categoryId);

            $response = $this->copyProductCategories($user, $productCategory, null, true);

            if (!$response["isSuccessful"]) {
                return $response;
            }

            $this->resortParentCategories($user, $customCategoryRepository, null);

            $store->setHasCustomCategory(true);
            $this->em->persist($store);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category deleted.",
                "data" => array()
            );
        }
    }

    public function updateCategoryWithSubcategories(User $user, $category, $categoryName, $products, $subcategories, $repository, $hasCustomCategory)
    {
        $store = $user->getStore();
        $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
        $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");

        if ($hasCustomCategory) {

            $subcategoryIds = $this->getSubcategoryIds($subcategories);
            $categoryChildren = $category->getChildrenBySortOrder();
            $customCategories = $customCategoryRepository->loadParentCustomCategories($user);
            $parentSubcategories = $customCategoryRepository->loadParentCustomCategoriesIn($user, $subcategoryIds);

            $customCategoriesCount = count($customCategories);

            $category->setName($categoryName);

            $subcategoryData = $this->constructSubcategoryData($subcategories);

            //remove subcategories
            $this->removeExcludedCategories($categoryChildren, $category, $subcategoryData, $customCategoriesCount);

            //add new subcategories
            $this->addSubcategories($parentSubcategories, $categoryChildren, $category);

            //resort
            $this->resortSubcategories($category, $subcategoryData);

            $this->em->persist($category);
            $this->em->flush();

            //resort parents
            $this->resortParentCategories($user, $customCategoryRepository, null);

            $products = is_string($products)? json_decode($products, true) : $products;
            $userProducts = $productRepository->loadUserProductsIn($user, $products);

            $this->updateCategoryProducts($category, $userProducts, $products);

            $childrenCount = count($categoryChildren);
            foreach($subcategories as $index => $subcategory){
                $subcategoryProducts = array_key_exists("products", $subcategory)? $subcategory["products"] : array();
                if (array_key_exists("categoryId", $subcategory) && $subcategory["categoryId"] != 0) {
                    $userProducts = $productRepository->loadUserProductsIn($user, $subcategoryProducts);
                    $customSubcategory = $customCategoryRepository->find($subcategory["categoryId"]);
                    $customSubcategory->setSortOrder($index);

                    $this->updateCategoryProducts($customSubcategory, $userProducts, $subcategoryProducts);
                    $this->em->persist($customSubcategory);
                }
                else{
                    $customSubcategory = null;
                    if(!array_key_exists('categoryId', $subcategory) OR $subcategory["categoryId"] == 0){
                        $customSubcategory = $this->addCustomCategory($user, $subcategory["categoryName"], null, $category, $index);
                    }
                    else{
                        $customSubcategory = $customCategoryRepository->find($subcategory["categoryId"]);
                        $customSubcategory->setName($subcategory["categoryName"])
                                          ->setParent($category);

                        $this->em->persist($customSubcategory);
                        $this->em->flush();
                    }

                    if(!is_null($customSubcategory)){
                        $response = $this->assignProductsToCategory($productRepository, $customSubcategory, $user, $subcategoryProducts);
                        if (!$response["isSuccessful"]) {
                            return $response;
                        }

                        $childrenCount++;
                    }
                }
            }

            return array(
                "isSuccessful" => true,
                "message" => "Category has been updated",
                "data" => array()
            );
        } else {
            $rootNode = $repository->find(ProductCategory::ROOT_CATEGORY_ID);
            $productCategories = $repository->loadParentProductCategories($rootNode);

            $sortOrder = array_search($category, $productCategories);

            $subcategoryData = $this->constructSubcategoryData($subcategories);

            //add category
            $customCategory = $this->addCustomCategory($user, $categoryName, $category, null, $sortOrder);

            //copy the subcategories. set this as excluded
            $addedSubcategoriesResponse = $this->copyProductCategoriesWithSubcategories($user, $subcategoryData, $customCategory);

            if (!$addedSubcategoriesResponse["isSuccessful"]) {
                return $addedSubcategoriesResponse;
            }

            //resort
            $this->resortParentCategories($user, $customCategoryRepository, null);

            if (!empty($products)) {
                $response = $this->assignProductsToCategory($productRepository, $customCategory, $user, $products);
                if (!$response["isSuccessful"]) {
                    return $response;
                }
            }

            $addedSubcategories = $addedSubcategoriesResponse["data"]["categories"];
            $childrenCount = count($addedSubcategories);
            foreach($subcategories as $subcategory){
                $productCategoryId = array_key_exists("categoryId", $subcategory)? $subcategory["categoryId"] : null;
                if(array_key_exists($productCategoryId, $addedSubcategories) && !empty($subcategory["products"])){
                    $response = $this->assignProductsToCategory($productRepository, $addedSubcategories[$productCategoryId], $user, $subcategory["products"]);
                    if (!$response["isSuccessful"]) {
                        return $response;
                    }
                }
                elseif(is_null($productCategoryId) OR $productCategoryId == 0){
                    $addedSubcategory = $this->addCustomCategory($user, $subcategory["categoryName"], null, $customCategory, $childrenCount);
                    if (!empty($products)) {
                        $response = $this->assignProductsToCategory($productRepository, $addedSubcategory, $user, $subcategory["products"]);
                        if (!$response["isSuccessful"]) {
                            return $response;
                        }
                    }

                    //for sort order
                    $childrenCount++;
                }
            }

            $store->setHasCustomCategory(true);
            $this->em->persist($store);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category updated.",
                "data" => array()
            );
        }
    }


    public function updateCategoryWithParent(User $user, $category, $categoryName, $products, $parentId, $repository, $hasCustomCategory)
    {
        $store = $user->getStore();

        $parent = null;
        $customCategories = null;
        $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");

        if (!is_null($parentId)) {
            $parent = $repository->find($parentId);
        }

        if ($hasCustomCategory) {
            $category->setName($categoryName);

            //if parent is not equal to parent
            if ($parent != $category->getParent()) {
                if(!is_null($parent)){
                    //if has current parent
                    $previousParent = $category->getParent();
                    $children = $category->getChildrenBySortOrder();

                    if(!$children->isEmpty()){
                        $customCategories = $customCategoryRepository->loadParentCustomCategories($user);
                        $this->removeExcludedCategories($children, $category, array(), count($customCategories));
                    }

                    //resort siblings from previous parent
                    if(!is_null($previousParent)){
                        $this->resortSiblings($previousParent->getChildrenBySortOrder(), $category);
                    }

                    //change parent
                    $category->setParent($parent)
                             ->setSortOrder(count($parent->getChildrenBySortOrder()));
                    $this->resortParentCategories($user, $customCategoryRepository, $category);
                }
                else{
                    //remove parent
                    $categoryParent = $category->getParent();
                    $parentCustomCategories = $customCategoryRepository->loadParentCustomCategories($user);

                    $children = $categoryParent->getChildrenBySortOrder();
                    $category->setParent(null)
                             ->setSortOrder(count($parentCustomCategories));

                    $this->resortSiblings($children, $category);
                }
            }

            $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");

            $products = is_string($products)? json_decode($products) : $products;
            $userProducts = $productRepository->loadUserProductsIn($user, $products);

            $this->updateCategoryProducts($category, $userProducts, $products);

            $this->em->persist($category);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category has been updated",
                "data" => array()
            );
        } else {
            //copy product categories
            $response = $this->copyProductCategories($user, $category, $parent, false);

            if (!$response["isSuccessful"]) {
                return $response;
            }

            //add category
            $customCategory = $this->addCustomCategory($user, $categoryName, $category, $response["data"]["parent"], 0);

            if(!is_null($parent)){
                //resort if have parent
                $this->resortParentCategories($user, $customCategoryRepository, $customCategory);
            }
            else{
                //stashed sort order = current position in sort order
                $customCategory->setSortOrder($response["data"]["stashedSortOrder"]);
            }

            if (!empty($products)) {
                //add products to category
                $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
                $response = $this->assignProductsToCategory($productRepository, $customCategory, $user, $products);

                if (!$response["isSuccessful"]) {
                    return $response;
                }
            }

            $store->setHasCustomCategory(true);
            $this->em->persist($store);
            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category has been updated",
                "data" => array()
            );
        }
    }

    private function addCustomCategory(User $user, $categoryName, $category = null, $parent = null, $sortOrder = null)
    {
        $customCategory = new CustomizedCategory();
        $customCategory->setProductCategory($category)
                       ->setUser($user)
                       ->setName($categoryName)
                       ->setDateCreated(Carbon::now())
                       ->setDateLastModified(Carbon::now())
                       ->setSortOrder($sortOrder);

        !is_null($parent) ? $customCategory->setParent($parent) : $customCategory->setParent(null);

        $this->em->persist($customCategory);
        $this->em->flush();

        return $customCategory;
    }

    public function assignProductsToCategory($repository, $customCategory, $user, $products)
    {
        $products = is_string($products)? json_decode($products) : $products;
        $lookupProducts = $repository->loadProductsOfCustomCategoryIn($customCategory, $user, $products);
        $userProducts = $repository->loadUserProductsIn($user, $products);

        return $this->addProductToCategory($customCategory, $userProducts, $lookupProducts, $products);
    }

    private function addProductToCategory(CustomizedCategory $category, $userProducts, $lookupProducts, $productIds)
    {
        try {
            foreach ($productIds as $index => $productId) {
                if (array_key_exists($productId, $userProducts) && !array_key_exists($productId, $lookupProducts)) {
                    $product = $userProducts[$productId];
                    $customCategoryProduct = new CustomizedCategoryProductLookup();
                    $customCategoryProduct->setCustomizedCategory($category)
                                          ->setProduct($product)
                                          ->setSortOrder($index);

                    $this->em->persist($customCategoryProduct);
                }
            }

            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Products successfully added",
                "data" => array()
            );
        } catch (Exception $e) {
            return array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array(
                    "line" => 843
                )
            );
        }
    }

    private function getSubcategoryIds($subcategories)
    {
        $subcategoryIds = array();

        foreach($subcategories as $subcategory){
            if(array_key_exists("categoryId", $subcategory)){
                array_push($subcategoryIds, $subcategory["categoryId"]);
            }
        }

        return $subcategoryIds;
    }

    //some functions is easier if data is hydrated
    private function constructSubcategoryData($subcategories)
    {
        $subcategoryData = array();

        foreach($subcategories as $subcategory){
            if(array_key_exists("categoryId", $subcategory)){
                $subcategoryData[$subcategory["categoryId"]] = $subcategory;
            }
        }

        return $subcategoryData;
    }

    public function assignParentToCustomCategory(User $user, CustomizedCategory $customizedCategory, array $subcategories)
    {
        $customCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
        $subcategoryIds = $this->getSubcategoryIds($subcategories);
        $customCategories = $customCategoryRepository->loadParentCustomCategoriesIn($user, $subcategoryIds);
        $parentCategoriesCtr = count($customCategoryRepository->loadParentCustomCategories($user));
        $addedCustomCategories = array();

        try {
            foreach ($customCategories as $customCategory) {
                $customCategoryId = $customCategory->getCustomizedCategoryId();
                $sortOrder = array_search($customCategoryId, $subcategoryIds);

                $children = $customCategory->getChildrenBySortOrder();

                if(!$children->isEmpty()){
                    foreach($children as $child){
                        $child->setParent(null);
                        $child->setSortOrder($parentCategoriesCtr);
                        $this->em->persist($child);

                        $parentCategoriesCtr++;
                    }
                }

                $customCategory->setName($subcategories[$customCategoryId]["categoryName"]);
                $customCategory->setParent($customizedCategory);
                $customCategory->setSortOrder($sortOrder);

                $this->em->persist($customCategory);

                $addedCustomCategories[$customCategoryId] = $customCategory;
            }

            $this->em->flush();

            $this->resortParentCategories($user, $customCategoryRepository, $customizedCategory);

            return array(
                "isSuccessful" => true,
                "message" => "Category has been added",
                "data" => array(
                    "categories" => $addedCustomCategories
                )
            );
        } catch (Exception $e) {
            return array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array(
                    "line" => 925
                )
            );
        }
    }

    public function resortParentCategories($user, $repository, $exclude)
    {
        $parentCategories = $repository->loadParentCustomCategories($user);
        $sortOrder = 0;

        foreach($parentCategories as $parentCategory){
            if($parentCategory != $exclude){
                $parentCategory->setSortOrder($sortOrder);
                $sortOrder++;

                $this->em->persist($parentCategory);
            }
        }

        $this->em->flush();
    }

    private function resortSiblings($siblings, $exclude)
    {
        $sortOrder = 0;

        foreach($siblings as $sibling){
            if($sibling != $exclude){
                $sibling->setSortOrder($sortOrder);
                $sortOrder++;

                $this->em->persist($sibling);
            }
        }

        $this->em->flush();
    }

    private function resortSubcategories($category, $subcategories)
    {
        $subcategoryIds = array_keys($subcategories);
        foreach($category->getChildren() as $child){
            $customizedCategoryId = $child->getCustomizedCategoryId();
            if(array_key_exists($customizedCategoryId, $subcategories)){
                $position = array_search($customizedCategoryId, $subcategoryIds);
                $subcategory = $subcategories[$subcategoryIds[$position]];
                $child->setName($subcategory["categoryName"])
                      ->setSortOrder($position)
                      ->setParent($category);
                $this->em->persist($child);
            }
        }

        $this->em->flush();
    }

    private function addSubcategories($parentSubcategories, $categoryChildren, $category)
    {
        foreach($parentSubcategories as $parentSubcategory){
            if(!$categoryChildren->contains($parentSubcategory)){
                $category->addChild($parentSubcategory);
            }
        }
    }

    private function removeExcludedCategories($categoryChildren, $category, $subcategories, $customCategoriesCount)
    {
        foreach($categoryChildren as $categoryChild){
            $categoryChildId = $categoryChild->getCustomizedCategoryId();
            if(!array_key_exists($categoryChildId, $subcategories)){
                $category->removeChild($categoryChild);
                $categoryChild->setParent(null)
                              ->setSortOrder($customCategoriesCount);

                $customCategoriesCount++;
                $this->em->persist($categoryChild);
            }
        }

        $this->em->flush();
    }

    /**
     * Copy parent product categories to custom category table
     *
     * @param CustomizedCategory $category
     * @param array $userProducts
     * @param array $lookupProducts
     * @param array $products
     * @return array
     */
    private function updateCategoryProducts(CustomizedCategory $category, array $userProducts, array $products)
    {
        //remove products then add product id in array if exists
        $categoryProductIds = array();

        $categoryProducts = $category->getProductsLookup();
        foreach ($categoryProducts as $index => $categoryProduct) {
            $product = $categoryProduct->getProduct();
            if(!in_array($product->getProductId(), $products)){
                $categoryProducts->remove($index);
                $this->em->remove($categoryProduct);
            }
            else{
                array_push($categoryProductIds, $product->getProductId());
            }
        }

        //add to product
        foreach ($products as $index => $productId) {

            if(!in_array($productId, $categoryProductIds) && array_key_exists($productId, $userProducts)){
                $categoryProduct = new CustomizedCategoryProductLookup();
                $categoryProduct->setCustomizedCategory($category)
                                ->setProduct($userProducts[$productId])
                                ->setSortOrder($index);

                $categoryProducts->add($categoryProduct);
                $this->em->persist($categoryProduct);
            }
        }

        $this->em->flush();
        $this->resortProducts($categoryProducts, $products);

        return array(
            "isSuccessful" => true,
            "message" => "Products successfully updated",
            "data" => array()
        );
    }

    public function resortProducts($userCustomProducts, $products){

        foreach($userCustomProducts as $userCustomProduct){
            $productId = $userCustomProduct->getProduct()->getProductId();

            if(in_array($productId, $products)){
                $sortOrder = array_search($productId, $products);
                $userCustomProduct->setSortOrder($sortOrder);
            }

            $this->em->persist($userCustomProduct);
        }

        $this->em->flush();
    }

    /**
     * Copy parent product categories to custom category table
     *
     * @param User $user
     * @param null $exclude
     * @param null $parent
     * @param bool $isAdd
     * @return bool|int
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function copyProductCategories(User $user, $exclude = null, $parent = null, $isAdd = false)
    {
        $productCategories = $this->getCustomCategoriesHierarchy($user);
        $stashedSortOrder = null;
        $parentCustomCategory = null;
        $parentId = $parent instanceof ProductCategory ? $parent->getProductCategoryId() : null;

        try {
            foreach ($productCategories as $index => $productCategory) {
                if ($productCategory != $exclude) {
                    $customCategory = new CustomizedCategory();
                    $customCategory->setProductCategory($productCategory)
                                   ->setUser($user)
                                   ->setParent(null)
                                   ->setName($productCategory->getName())
                                   ->setSortOrder($index)
                                   ->setDateCreated(Carbon::now())
                                   ->setDateLastModified(Carbon::now());
                    $productCategoryId = $productCategory->getProductCategoryId();

                    if ($productCategoryId == $parentId) {
                        $parentCustomCategory = $customCategory;
                    }

                    $this->em->persist($customCategory);

                    $products = $user->getProductsByCategoryAndStatus($productCategory, Product::ACTIVE);

                    foreach ($products as $productIndex => $product) {
                        $customizedCategoryLookup = new CustomizedCategoryProductLookup();

                        $customizedCategoryLookup->setCustomizedCategory($customCategory)
                                                 ->setProduct($product)
                                                 ->setSortOrder($productIndex);

                        $this->em->persist($customizedCategoryLookup);
                    }
                }
                else{
                    $stashedSortOrder = $index;
                }
            }

            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Products has been added",
                "data" => array(
                    "parent" => $parentCustomCategory,
                    "stashedSortOrder" => $stashedSortOrder,
                    "parentCategoriesCount" => count($productCategories),
                )
            );
        } catch (Exception $e) {
            return array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array(
                    "line" => 1106
                )
            );
        }
    }

    /**
     * Copy parent product categories to custom category table with subcategories
     *
     * @param User $user
     * @param array $subcategories
     * @param CustomizedCategory $customizedCategory
     * @return bool|int
     * @throws \Doctrine\DBAL\ConnectionException
     */
    private function copyProductCategoriesWithSubcategories(User $user, array $subcategories, CustomizedCategory $customizedCategory)
    {
        $productCategories = $this->getCustomCategoriesHierarchy($user);
        $parentSortOrder = 0;
        $childSortOrder = 0;
        $stashedCustomCategories = array();
        $stashedSortOrder = array();
        $customCategories = array();

        try {
            foreach ($productCategories as $index => $productCategory) {
                $customizedProductCategory = $customizedCategory->getProductCategory();

                $productCategoryId = $productCategory->getProductCategoryId();

                $customizedProductCategoryId = null;
                if (!is_null($customizedProductCategory)) {
                    $customizedProductCategoryId = $customizedProductCategory->getProductCategoryId();
                }

                if ($productCategoryId != $customizedProductCategoryId) {

                    $name = array_key_exists($productCategoryId, $subcategories)? $subcategories[$productCategoryId]["categoryName"] : $productCategory->getName();

                    $customCategory = new CustomizedCategory();
                    $customCategory->setProductCategory($productCategory)
                                   ->setUser($user)
                                   ->setName($name)
                                   ->setDateCreated(Carbon::now())
                                   ->setSortOrder($parentSortOrder)
                                   ->setDateLastModified(Carbon::now());

                    if (array_key_exists($productCategoryId, $subcategories)) {
                        $customCategory->setParent($customizedCategory);
                        $stashedCustomCategories[$productCategoryId] = $customCategory;
                        $customCategories[$productCategoryId] = $customCategory;
                        array_push($stashedSortOrder, $childSortOrder);
                        $childSortOrder++;
                    } else {
                        $customCategory->setParent(null);
                        $parentSortOrder++;
                    }

                    $this->em->persist($customCategory);

                    $products = $user->getProductsByCategoryAndStatus($productCategory, Product::ACTIVE);

                    foreach ($products as $productIndex => $product) {
                        $customizedCategoryLookup = new CustomizedCategoryProductLookup();

                        $customizedCategoryLookup->setCustomizedCategory($customCategory)
                                                 ->setProduct($product)
                                                 ->setSortOrder($productIndex);

                        $this->em->persist($customizedCategoryLookup);
                    }

                }
            }

            foreach ($subcategories as $subcategoryId => $subcategory) {
                if (array_key_exists((int)$subcategoryId, $stashedCustomCategories)) {
                    $subcategory = $stashedCustomCategories[$subcategoryId];
                    $subcategory->setSortOrder(array_shift($stashedSortOrder));
                }
            }

            $this->em->flush();

            return array(
                "isSuccessful" => true,
                "message" => "Category has been added",
                "data" => array(
                    "sortOrder" => 0,
                    "categories" => $customCategories
                )
            );
        } catch (Exception $e) {
            return array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array(
                    "line" => 1190
                )
            );
        }
    }

    public function getCategoryDetails(User $user, $categoryId, $hasCustomCategory)
    {
        $categoryDetails = array(
            "categoryId" => null,
            "categoryName" => null,
            "parentId" => null,
            "sortOrder" => null,
            "subcategories" => array(),
            "products" => array()
        );

        if($hasCustomCategory){
            $customizedCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customCategoryProductLookupRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategoryProductLookup");
            $customizedCategory = $customizedCategoryRepository->find($categoryId);

            if(is_null($customizedCategory) OR $customizedCategory->getUser() != $user){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Category not found",
                    "data" => array(
                        "line" => 1217
                    )
                ), 400);
            }

            $categoryDetails["categoryId"] = $customizedCategory->getCustomizedCategoryId();
            $categoryDetails["categoryName"] = $customizedCategory->getName();
            $categoryDetails["sortOrder"] = $customizedCategory->getSortOrder();
            $categoryDetails["parentId"] = $customizedCategory->getParent()? $customizedCategory->getParent()->getCustomizedCategoryId() : null;

            $children = $customizedCategory->getChildrenBySortOrder();

            foreach($children as $child){
                $subcategoryProducts = $this->getCutomizedCategoryProducts($child);

                array_push($categoryDetails["subcategories"], array(
                    "categoryId" => $child->getCustomizedCategoryId(),
                    "categoryName" => $child->getName(),
                    "products" => $subcategoryProducts,
                    "sortOrder" => $child->getSortOrder()
                ));
            }

            $categoryDetails["products"] = $this->getCutomizedCategoryProducts($customizedCategory);
        }
        else{
            $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");
            $productCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
            $categoryNestedSetRepository = $this->em->getRepository("YilinkerCoreBundle:CategoryNestedSet");

            $productCategory = $productCategoryRepository->find($categoryId);

            $categoryNestedSet = $categoryNestedSetRepository->findOneBy(array("productCategory" => $productCategory));

            $categoryDetails["categoryId"] = $productCategory->getProductCategoryId();
            $categoryDetails["categoryName"] = $productCategory->getName();

            $products = $productRepository->loadUserProductsByParentCategory(
                $user,
                Product::ACTIVE,
                $categoryNestedSet->getLeft(),
                $categoryNestedSet->getRight()
            );

            foreach ($products as $product){
                array_push($categoryDetails["products"], $this->getProductDetail($product));
            }
        }

        return $categoryDetails;
    }

    public function getCutomizedCategoryProducts($customizedCategory)
    {
        $customCategoryProductLookupRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategoryProductLookup");
        $customCategoryProducts = $customCategoryProductLookupRepository->loadCustomCategoryUserProducts($customizedCategory);

        $categoryProducts = array();
        foreach($customCategoryProducts as $customCategoryProduct){
            $product = $customCategoryProduct->getProduct();

            if($product->getStatus() == Product::ACTIVE){
                array_push($categoryProducts, $this->getProductDetail($product));
            }
        }

        return $categoryProducts;
    }

    public function getProductDetail($product){

        $imageLocation = $product->getPrimaryImageLocation();

        $productImageUrl = "";
        if($imageLocation != ""){
            $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
        }

        return array(
            "productId" => $product->getProductId(),
            "productName" => $product->getName(),
            "image" => $productImageUrl
        );
    }

    public function getAllCategoryProducts(User $user, $categoryId, $hasCustomCategory)
    {
        $products = array();

        $productRepository = $this->em->getRepository("YilinkerCoreBundle:Product");

        if($hasCustomCategory){
            $customizedCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customizedCategory = $customizedCategoryRepository->find($categoryId);

            if(is_null($customizedCategory) OR $customizedCategory->getUser() != $user){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Category not found",
                    "data" => array(
                        "line" => 1281
                    )
                ), 400);
            }

            $userProducts = $productRepository->loadAllProductsOfCustomCategory($customizedCategory, $user);

            foreach($userProducts as $product){
                $imageLocation = $product->getPrimaryImageLocation();

                $productImageUrl = "";
                if($imageLocation != ""){
                    $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
                }

                $customProductLookup = $product->getCustomizedCategoryLookup();
                $inCustomCategory = false;
                foreach($customProductLookup as $lookup){
                    if($lookup->getCustomizedCategory() == $customizedCategory){
                        $inCustomCategory = true;
                        break;
                    }
                }

                $productDetails = array(
                    "productId" => $product->getProductId(),
                    "productName" => $product->getName(),
                    "image" => $productImageUrl,
                    "inCustomCategory" => $inCustomCategory,
                );

                if(!in_array($productDetails, $products)){
                    array_push($products, $productDetails);
                }
            }
        }
        else{
            $userProducts = $productRepository->loadUserProducts($user);

            foreach($userProducts as $product){
                $imageLocation = $product->getPrimaryImageLocation();

                $productImageUrl = "";
                if($imageLocation != ""){
                    $productImageUrl = $this->assetsHelper->getUrl($imageLocation, 'product');
                }

                array_push($products, array(
                    "productId" => $product->getProductId(),
                    "productName" => $product->getName(),
                    "image" => $productImageUrl,
                    "inCustomCategory" => false
                ));
            }
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Product list.",
            "data" => array(
                "products" => $products
            )
        ) ,200);
    }

    public function sortParentCategories(User $user, $categories, $hasCustomCategory)
    {
        if($hasCustomCategory){
            $customizedCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customizedCategories = $customizedCategoryRepository->loadParentCustomCategories($user);

            foreach($categories as $category){
                $categoryId = $category["categoryId"];
                if(array_key_exists($categoryId, $customizedCategories)){
                    $customCategory = $customizedCategories[$categoryId];
                    $customCategory->setSortOrder($category["sortOrder"]);

                    $this->em->persist($customCategory);
                }
            }
        }
        else{
            $this->copyProductCategories($user, null, null, true);
            $customizedCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customizedCategories = $customizedCategoryRepository->loadParentCustomCategories($user);

            foreach($customizedCategories as $customCategory){
                $customCategory = $customCategory->getProductCategory();
                if(!is_null($customCategory)){
                    $productCategoryId = $customCategory->getProductCategoryId();

                    foreach($categories as $category){
                        if($category["categoryId"] == $productCategoryId){
                            $customCategory->setSortOrder($category["sortOrder"]);
                        }
                    }
                }

                $this->em->persist($customCategory);
            }
        }

        $store = $user->getStore();
        $store->setHasCustomCategory(true);

        $this->em->persist($store);
        $this->em->flush();
    }

    public function checkIfCategoryExists(User $user, $categoryId, $categoryName, $hasCustomCategory)
    {
        if ($hasCustomCategory) {
            $customizedCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:CustomizedCategory");
            return $customizedCategoryRepository->getCustomizedCategoryByName($categoryId, $categoryName, $user);
        } else {
            $productCategoryRepository = $this->em->getRepository("YilinkerCoreBundle:ProductCategory");
            return $productCategoryRepository->getProductCategoryByName($categoryId, $categoryName);
        }
    }
}
