<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CustomizedCategoryApiController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
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

    public function getCategoryDetailsAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $userId = $request->get('sellerId' , 0);

        if(is_null($userId) || $userId == 0){
            $user = $this->getAuthenticatedUser();
        }
        else{
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

        $store = $user->getStore();
        $hasCustomCategory = $store->getHasCustomCategory();
        $customCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');

        $categoryId = $request->get('categoryId', 0);

        if(is_null($categoryId) || $categoryId == 0)
        {
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Category is invalid",
                "data" => array()
            ), 400);
        }

        $categoryDetails = $customCategoryService->getCategoryDetails($user, $categoryId, $hasCustomCategory);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Category details.",
            "data" => $categoryDetails
        ), 200);
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

