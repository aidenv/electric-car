<?php
namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CustomizedCategoryApiController extends YilinkerBaseController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomCategoriesAction(Request $request)
    {
        $sellerId = $request->get("sellerId", 0);
        $queryString = $request->get("queryString", "");

        $key = 'store-categories-api-'.$sellerId;
        $content = $this->getCacheValue($key, true, false);

        $data = $content;

        if(!$content){

            $entityManager = $this->getDoctrine()->getManager();
            $userRepository = $entityManager->getRepository("YilinkerCoreBundle:User");
            $user = $userRepository->findOneBy(array(
                "userId"   => $sellerId,
                "userType" => User::USER_TYPE_SELLER
            ));

            if(is_null($user) || $user->getUserType() != User::USER_TYPE_SELLER){
              return new JsonResponse(array(
                  "isSuccessful" => false,
                  "message" => "User not found.",
                  "data" => array()
              ), 404);
            }

            if($user->getStore()->getHasCustomCategory()){
                $customizedCategoryService = $this->get('yilinker_core.service.customized_category.customized_category');
                $data = $customizedCategoryService->getFullCustomCategoryHierarchy($user, true, $queryString);
            }
            else{
                $assetsHelper = $this->get("templating.helper.assets");
                $categories = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory")
                                            ->getCategoriesOfUserProducts($user, Product::ACTIVE);

                $data = array();
                foreach ($categories as $category) {
                    $products = $category->getUserProducts($user);

                    $categoryProducts = array();
                    foreach ($products as $product) {
                        array_push($categoryProducts, array(
                          "productId" => $product->getProductId(),
                          "productName" => $product->getName(),
                          "image" => $assetsHelper->getUrl($product->getDefaultUnit()->getPrimaryImageLocation(), "product")
                        ));
                    }

                    array_push($data, array(
                        "name" => $category->getName(),
                        "parentId" => !is_null($category->getParent())? $category->getParent()->getProductCategoryId() : null,
                        "sortOrder" => $category->getSortOrder(),
                        "products" => $categoryProducts,
                        "subcategories" => array()
                    ));
                }
            }

            $this->setCacheValue($key, $data);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Custom categories collection.",
            "data" =>  $data
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
