<?php
namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class CustomizedCategoryApiController extends Controller
{
    /**
     * custom categories 
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Custom Category",
     *       parameters={
     *         {"name"="sellerId", "dataType"="string", "required"=true, "description"="sellerId"},
     *         {"name"="queryString", "dataType"="string", "required"=true, "description"="queryString"},
     *     }
     * )
     */
    public function getCustomCategoriesAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $userId = $request->get("sellerId", 0);
        $queryString = $request->get("queryString", "");

        $forBuyer = false;

        if(is_null($userId) || $userId == 0){
            $user = $this->getAuthenticatedUser();
        }
        else{
            $forBuyer = true;
            $userRepository = $entityManager->getRepository("YilinkerCoreBundle:User");
            $user = $userRepository->findOneBy(array(
                "userId" => $userId,
                "userType" => User::USER_TYPE_SELLER
            ));
        }

        if(is_null($user) || $user->getUserType() != User::USER_TYPE_SELLER){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "User not found.",
                "data" => array()
            ), 404);
        }

        $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Custom categories collection.",
            "data" =>  $customizedCategoryService->getFullCustomCategoryHierarchy($user, $forBuyer, $queryString)
        ), 200);
    }
    
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addCustomCategoryAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        $categoryName = $request->request->get("categoryName", null);
        $parentId = $request->request->get("parentId", 0);
        $products = json_decode(trim($request->request->get("products", "[]")), true);
        $subcategories = json_decode(trim($request->request->get("subcategories", "[]")), true);

        if(($parentId != 0 AND !empty($subcategories)) OR trim($categoryName) == ""){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid inputs.",
                "data" => array()
            ), 400);
        }

        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $response = $customCategoryService->addCategory($authenticatedUser, $categoryName, $products, $parentId, $subcategories);

        $categories = array();
        if($response["isSuccessful"] AND !$hasCustomCategory){
            $categories = $customCategoryService->getScalarCustomCategories($authenticatedUser);
        }

        return new JsonResponse(array(
            "isSuccessful" => $response["isSuccessful"],
            "message" => $response["message"],
            "data" => array(
                "categories" => $categories,
                "hasCustom" => $hasCustomCategory
            )
        ), $response["isSuccessful"]? 200 : 400);
    }

    public function deleteCustomCategoryAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        $categoryId = $request->request->get("categoryId", 0);

        if($categoryId == 0){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid Category.",
                "data" => array()
            ), 400);
        }

        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $response = $customCategoryService->deleteCustomCategory($authenticatedUser, $categoryId, $hasCustomCategory);

        $categories = array();
        if($response["isSuccessful"] AND !$hasCustomCategory){
            $categories = $customCategoryService->getScalarCustomCategories($authenticatedUser);
        }

        return new JsonResponse(array(
            "isSuccessful" => $response["isSuccessful"],
            "message" => $response["message"],
            "data" => array(
                "categories" => $categories,
                "hasCustom" => $hasCustomCategory
            )
        ), $response["isSuccessful"]? 200 : 400);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCustomCategoryAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        $response = array(
            "isSuccessful" => false,
            "message" => "Invalid Category.",
            "data" => array()
        );

        $categories = json_decode($request->request->get("categories", "[]"), true);

        if(is_null($categories)){
            $response["message"] = "Invalid JSON format.";
            return new JsonResponse($response, 400);
        }

        $entityManager->beginTransaction();
        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        foreach($categories as $category){
            $categoryId = array_key_exists("categoryId", $category)? $category["categoryId"] : null;
            $categoryName = array_key_exists("categoryName", $category)? $category["categoryName"] : null;
            $parentId = array_key_exists("parentId", $category)? $category["parentId"] : 0;
            $products = array_key_exists("products", $category)? $category["products"] : array();
            $subcategories = array_key_exists("subcategories", $category)? $category["subcategories"] : array();

            if(($parentId != 0 AND !empty($subcategories)) OR trim($categoryName) == ""){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Invalid Category",
                    "data" => array(
                        "line" => 127
                    )
                ), 400);
            }

            $response = $customCategoryService->updateCategory($authenticatedUser, $categoryId, $categoryName, $products, $parentId, $subcategories);
            if(!$response["isSuccessful"]){
                $entityManager->rollback();
                return new JsonResponse($response, 400);
            }
        }

        $entityManager->commit();

        $categories = array();
        if($response["isSuccessful"] AND !$hasCustomCategory){
            $categories = $customCategoryService->getScalarCustomCategories($authenticatedUser);
        }

        return new JsonResponse(array(
            "isSuccessful" => $response["isSuccessful"],
            "message" => $response["message"],
            "data" => array(
                "categories" => $categories,
                "hasCustom" => $hasCustomCategory
            )
        ), $response["isSuccessful"]? 200 : 400);
    }

    public function checkIfCategoryExistsAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();
        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $categoryName = $request->request->get('categoryName', "");
        $categoryId = $request->request->get('categoryId', null);

        if(trim($categoryName) == "" OR is_null($categoryId))
        {
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Category name is invalid",
                "data" => array(
                    "isValid" => false
                )
            ), 400);
        }

        $isValid = $customCategoryService->checkIfCategoryExists($authenticatedUser, $categoryId, $categoryName, $hasCustomCategory);

        if(!empty($isValid)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Category name is invalid",
                "data" => array(
                    "isValid" => false
                )
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Category name is valid",
            "data" => array(
                "isValid" => true
            )
        ), 200);
    }

    public function getAllCategoryProductsAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();
        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $categoryId = $request->request->get("categoryId", 0);

        return $customCategoryService->getAllCategoryProducts($authenticatedUser, $categoryId, $hasCustomCategory);
    }

    public function sortParentCategoriesAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $store = $authenticatedUser->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();

        $response = array(
            "isSuccessful" => false,
            "message" => "Invalid Category.",
            "data" => array()
        );

        $categories = json_decode($request->request->get("categories", array()), true);

        if(is_null($categories)){
            $response["message"] = "Invalid JSON format.";
            return new JsonResponse($response);
        }

        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');
        $customCategoryService->sortParentCategories($authenticatedUser, $categories, $hasCustomCategory);

        $categories = array();
        if(!$hasCustomCategory){
            $categories = $customCategoryService->getScalarCustomCategories($authenticatedUser);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Categories sorted.",
            "data" => array(
                "categories" => $categories,
                "hasCustom" => $hasCustomCategory
            )
        ), 400);
    }

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $postData
     * @return \Symfony\Component\Form\Form
     */
    private function transactForm($formType, $entity, $postData)
    {
        $form = $this->createForm($formType, $entity);
        $form->submit($postData);

        return $form;
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
