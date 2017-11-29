<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Product;

/**
 * Class CustomizedCategoryController
 *
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class CustomizedCategoryController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderCustomizedCategoryAction()
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $customizedCategories = $customizedCategoryService->getCustomCategoriesHierarchy($authenticatedUser);
        $hasCustomCategory = $authenticatedUser->getStore()->getHasCustomCategory();

        $productService = $this->get("yilinker_core.service.product.product");
        $products = $productService->getProductList($authenticatedUser, "", Product::ACTIVE, false);

        return $this->render('YilinkerMerchantBundle:CustomizedCategory:customized_category.html.twig',
                    compact('customizedCategories', 'hasCustomCategory', 'products'));
    }

    public function getParentCategoriesAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $categories = $customizedCategoryService->getScalarCustomCategories($authenticatedUser);

        $customCategories = array();

        foreach($categories as $category){
            if(is_null($category["parentId"])){
                array_push($customCategories, $category);
            }
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Parent categories",
            "data" => $customCategories
        ), 200);
    }

    public function getCustomCategoriesHierarchyAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $categories = $customizedCategoryService->getFullCustomCategoryHierarchy($authenticatedUser);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Custom categories",
            "data" => $categories
        ), 200);
    }

    public function updateCustomCategoryAction(Request $request)
    {
        $categoryId = $request->request->get("categoryId", 0);
        $categoryName = $request->request->get("categoryName", "");
        $parentId = $request->request->get("parentId", 0);
        $products = $request->request->get("products", "[]");

        if(trim($categoryName) == ""){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Category name cannot be null",
                "data" => array()
            ), 400);
        }

        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');
        $categoryExists = $customizedCategoryService->checkIfCategoryExists($authenticatedUser, $categoryId, $categoryName, $hasCustomCategory);

        $matches = $customizedCategoryService->checkIfCategoryExists($authenticatedUser, $categoryId, $categoryName, $hasCustomCategory);

        if(!empty($matches) && $hasCustomCategory){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Category already exists.",
                "data" => array(
                    "errors" => "Category already exists."
            ), 400));
        }

        $entityManager = $this->getDoctrine()->getManager();

        if($hasCustomCategory){
            $repository = $entityManager->getRepository("YilinkerCoreBundle:CustomizedCategory");
        }
        else{
            $repository = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory");
        }

        $category = $repository->find($categoryId);
        $customizedCategoryService->updateCategoryWithParent($authenticatedUser, $category, $categoryName, $products, $parentId, $repository, $hasCustomCategory);

        $productObjectPersister = $this->get("fos_elastica.object_persister.yilinker_online.product");

        if($hasCustomCategory){
            $productsLookup = $category->getProductsLookup();

            foreach ($productsLookup as $productLookup) {
                $product = $productLookup->getProduct();
                $productObjectPersister->insertOne($product);
            }
        }
        else{
            $customizedCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:CustomizedCategory");
            $customizedCategory = $customizedCategoryRepository->findOneBy(array(
                                        "productCategory" => $category,
                                        "user" => $authenticatedUser
                                  ));

            $productsLookup = $customizedCategory->getProductsLookup();

            foreach ($productsLookup as $productLookup) {
                $product = $productLookup->getProduct();
                $productObjectPersister->insertOne($product);
            }
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Category has been updated",
            "data" => array()
        ), 200);
    }

    public function deleteCustomCategoriesAction(Request $request)
    {
        $categoryIds = json_decode($request->request->get("categoryIds", "[]"), true);
        $authenticatedUser = $this->getAuthenticatedUser();
        $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        if(!$categoryIds){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed deleting categories",
                "data" => array()
            ), 400);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $store = $authenticatedUser->getStore();

        $customizedCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:CustomizedCategory");

        if(!$store->getHasCustomCategory()){
            $customizedCategoryService->copyProductCategories($authenticatedUser);
            $productCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory");
            $productCategories = $productCategoryRepository->loadProductCategoriesIn($categoryIds);
            $customizedCategoryRepository->deleteCustomCategoriesByProductCategoryIn($authenticatedUser, $productCategories);
            $customizedCategoryService->resortParentCategories($authenticatedUser, $customizedCategoryRepository, null);

            $store->setHasCustomCategory(true);
            $entityManager->flush();
        }
        else{
            $entityManager->beginTransaction();

            foreach($categoryIds as $categoryId){
                $response = $customizedCategoryService->deleteCustomCategory($authenticatedUser, $categoryId, true);

                if(!$response["isSuccessful"]){
                    $entityManager->rollback();
                    return new JsonResponse($response, 400);
                }
            }

            $entityManager->commit();
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Success deleting category",
            "data" => array()
        ), 200);
    }

    public function getCategoryDetailsAndParentsAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();
        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $categoryId = $request->request->get('categoryId', 0);

        if(is_null($categoryId))
        {
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Category is invalid",
                "data" => array()
            ), 400);
        }

        $categoryDetails = $customCategoryService->getCategoryDetails($authenticatedUser, $categoryId, $hasCustomCategory);
        $parentCategories = $customCategoryService->getScalarCustomCategories($authenticatedUser);

        $customCategories = array();

        foreach($parentCategories as $category){
            if(is_null($category["parentId"])){
                array_push($customCategories, $category);
            }
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Modal details",
            "data" => array(
                "parentCategories" => $customCategories,
                "categoryDetails" => $categoryDetails
            )
        ), 200);
    }

    public function sortCustomCategoriesAction(Request $request)
    {
        $hierarchicalCategories = json_decode($request->request->get("categories", "[]"), true);

        if($hierarchicalCategories === false){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid JSON format.",
                "data" => array()
            ), 400);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();
        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');
        $customCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:CustomizedCategory");

        $categories = array();
        foreach($hierarchicalCategories as $hierarchicalCategory){
            $subcategories = array();
            if(!empty($hierarchicalCategory["subcategories"])){
                foreach($hierarchicalCategory["subcategories"] as $subcategory){
                    array_push($subcategories, array_shift($hierarchicalCategory["subcategories"]));
                }
            }

            array_push($categories, $hierarchicalCategory);
            if(!empty($subcategories)){
                foreach ($subcategories as $subcategory) {
                    array_push($categories, array_shift($subcategories));
                }
            }
        }

        try{
            $entityManager->beginTransaction();

            if(!$hasCustomCategory){

                $categoryIds = array();
                foreach ($categories as $category) {
                    array_push($categoryIds, $category["categoryId"]);
                }

                $customCategoryService->copyProductCategories($authenticatedUser);

                if(!empty($categoryIds)){
                    $productCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory");
                    $productCategories = $productCategoryRepository->loadProductCategoriesIn($categoryIds);
                    $customCategories = $customCategoryRepository->loadCustomCategoriesByProductCategoryIn($authenticatedUser, $productCategories);

                    foreach($customCategories as $customCategory){
                        $productCategory = $customCategory->getProductCategory();
                        if(!is_null($productCategory)){
                            $productCategoryId = $productCategory->getProductCategoryId();
                            $categoriesIndex = array_search($productCategoryId, $categoryIds);

                            $categoryDetails = $categories[$categoriesIndex];

                            $parentProductCategory = $productCategoryRepository->find($categoryDetails["parentId"]);
                            $parentCustomCategory = $customCategoryRepository->loadCustomCategoryByProductCategory($authenticatedUser, $parentProductCategory);

                            if(!is_null($parentCustomCategory)){
                                $customCategory->setParent($parentCustomCategory);
                            }

                            $customCategory->setSortOrder($categoryDetails["sortOrder"]);
                        }
                    }
                }

                $store->setHasCustomCategory(true);
                $entityManager->flush();
            }
            else{

                $categoryIds = array();
                foreach ($categories as $category) {
                    array_push($categoryIds, $category["categoryId"]);
                }


                $customCategories = $customCategoryRepository->loadCustomCategoriesIn($authenticatedUser, $categoryIds);

                foreach($categories as $category){
                    $customCategoryId = $category["categoryId"];
                    if(array_key_exists($customCategoryId, $customCategories)){
                        $parentId = $category["parentId"];
                        $parent = $parentId === 0? null : array_key_exists($parentId, $customCategories)? $customCategories[$parentId] : null;

                        $customCategory = $customCategories[$customCategoryId];

                        $customCategory->setParent($parent)
                                       ->setSortOrder($category["sortOrder"]);
                    }
                }

               $entityManager->flush();
            }
        }
        catch(\Exception $e){
            $entityManager->rollback();
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "An error occured",
                "data" => array()
            ), 400);
        }


        $entityManager->commit();

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Categories sorted",
            "data" => array()
        ), 200);
    }

    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }
}
