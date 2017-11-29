<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ProductReviewApiController extends Controller
{

    /**
     * Adds a product review
     *
     * @param Request $request 
     *
     * @ApiDoc(
     *     section="Product Review",
     *     parameters={
     *         {"name"="orderProductId", "dataType"="string", "required"=false, "description"="query String"},
     *         {"name"="productId", "dataType"="string", "required"=false},
     *         {"name"="review", "dataType"="string", "required"=false},
     *         {"name"="title", "dataType"="string", "required"=false},
     *         {"name"="rating", "dataType"="string", "required"=false},
     *     }
     * )
     */
    public function addProductReviewAction(Request $request)
    {
        $orderProductId = $request->request->get("orderProductId", 0);
        $productId = $request->request->get("productId", 0);
        $authenticatedUser = $this->getAuthenticatedUser();
        $productService = $this->get('yilinker_frontend.service.product.product');

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $product = $entityManager->getRepository('YilinkerCoreBundle:Product')->find($productId);

        $orderProductRepository = $entityManager->getRepository('YilinkerCoreBundle:OrderProduct');

        $orderProduct = $orderProductRepository->find((int)$orderProductId);
        $isReviewable = $orderProductRepository->isReviewable($orderProduct);

        if(!$isReviewable){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "You are not allowed to review this product",
                "data" => array("errors" => "You are not allowed to review this product")
            ), 400);
        }
        else{

            if(is_null($product)){
                return $productService->throwProductNotFound();
            }

            $hasRated = $entityManager->getRepository('YilinkerCoreBundle:ProductReview')
                                      ->findBy(array(
                                          "product" => $product,
                                          "reviewer" => $authenticatedUser,
                                          "orderProduct" => $orderProduct
                                      ));

            if(count($hasRated) > 0){
                return $productService->throwInvalidFields(array("User already reviewed this product."));
            }

            $postData = array(
                "productId" => $request->request->get('productId', 0),
                "title" => $request->request->get('title', null),
                "review" => $request->request->get('review', null),
                "rating" => number_format(floatval($request->request->get('rating', '1.00')), 2)
            );

            if($postData["rating"] > 5.00 || $postData["rating"] < 1.00){
                return $productService->throwInvalidFields(array("Invalid rating."));
            }

            $productService->setAuthenticatedUser($authenticatedUser);
            return $productService->addProductReview($product, $postData, $orderProduct);
        }
    }

    /**
     * Get product review
     *
     * @param Request $request
     *
     * @ApiDoc(
     *     section="Product Review",
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="query String"},
     *         {"name"="productId", "dataType"="string", "required"=false},
     *         {"name"="limit", "dataType"="string", "required"=false},
     *     }
     * )
     */
    public function getProductReviewsAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $productService = $this->get('yilinker_frontend.service.product.product');

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        
        $product = $entityManager->getRepository('YilinkerCoreBundle:Product')
                        ->getOnebyIdOrSlug($request->request->get('productId', 0))->getOneOrNullResult();

        if(is_null($product)){
            return $productService->throwProductNotFound();
        }

        $page = (int)$request->request->get('page', 1);
        $limit = (int)$request->request->get('limit', 10);

        if($page < 1 || $limit == 0){
            return $productService->throwInvalidFields(array("Invalid page or limit supplied."));
        }

        $productService->setAuthenticatedUser($authenticatedUser);
        return $productService->getProductReviews($product, $page, $limit);
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
