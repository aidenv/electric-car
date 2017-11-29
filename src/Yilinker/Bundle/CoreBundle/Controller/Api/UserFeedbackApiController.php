<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;

class UserFeedbackApiController extends Controller
{

    public function addUserFeedbackAction(Request $request)
    {
        $sellerId       = $request->request->get("sellerId", 0);
        $orderId        = $request->request->get('orderId', 0);
        $title          = $request->request->get('title', "");
        $feedback       = $request->request->get('feedback', "");
        $userRatings    = json_decode($request->request->get('ratings', "[]"), true);

        $authenticatedUser = $this->getAuthenticatedUser();
        $userFeedbackService = $this->get('yilinker_core.service.user_feedback.user_feedback');

        $entityManager = $this->getDoctrine()->getManager();
        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');
        $userOrderRepository = $entityManager->getRepository('YilinkerCoreBundle:UserOrder');
        $userFeedbackRepository = $entityManager->getRepository('YilinkerCoreBundle:UserFeedback');
        $feedbackTypeRepository = $entityManager->getRepository('YilinkerCoreBundle:FeedbackType');

        $feedbackTypes = $feedbackTypeRepository->getAllHydrated();

        $seller = $userRepository->findOneBy(array(
            "userId" => $sellerId,
            "userType" => User::USER_TYPE_SELLER
        ));

        $order = $userOrderRepository->findOneBy(array(
                    "buyer" => $authenticatedUser,
                    "orderId" => $orderId
                 ));

        if(is_null($seller) || is_null($order)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "User or Order does not exsist.",
                "data" => array()
            ), 404);
        }

        $isReviewable = $userOrderRepository->isReviewable($order);

        if(!$isReviewable){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Product is not yet delivered to the buyer.",
                "data" => array(
                    "errors" => array("Product is not yet delivered to the buyer.")
                )
            ), 400);
        }

        $store = $seller->getStore();

        $hasRated = $userFeedbackRepository->findOneBy(array(
                      "reviewee" => $store,
                      "reviewer" => $authenticatedUser,
                      "order" => $order
                  ));

        if(!is_null($hasRated)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "User already reviewed this seller.",
                "data" => array()
            ), 400);
        }

        $userRatingsCount = count($userRatings);
        $feedbackTypesCount = count($feedbackTypes);

        if($userRatingsCount === $feedbackTypesCount){
            $feedbackTypesArray = array();

            foreach($userRatings as $rating){
                if(array_key_exists($rating["rateType"], $feedbackTypes) &&
                   !in_array($rating["rateType"], $feedbackTypesArray)){
                   array_push($feedbackTypesArray, $rating["rateType"]);
                }
                else{
                    return new JsonResponse(array(
                        "isSuccessful" => false,
                        "message" => "Duplicate or invalid rating type supplied.",
                        "data" => array()
                    ), 400);
                }
            }

            array_unique($feedbackTypesArray);
            if(count($feedbackTypesArray) === $feedbackTypesCount){
                foreach($userRatings as $userRating) {
                    $rating = floatval($userRating["rating"]);
                    if ($rating > 5.00 || $rating < 1.00) {
                        return new JsonResponse(array(
                            "isSuccessful" => false,
                            "message" => "Invalid rating.",
                            "data" => array()
                        ), 400);
                    }
                }

                $entityManager->beginTransaction();
                $userFeedback = $userFeedbackService->addUserFeedback($authenticatedUser, $store, $order, $title, $feedback, $userRatings, $feedbackTypes);

                if($userFeedback){
                    $entityManager->commit();
                    return new JsonResponse(array(
                        "isSuccessful" => true,
                        "message" => "Feedback added.",
                        "data" => $userFeedback
                    ), 201);
                }
                else{
                    $entityManager->rollback();
                    return new JsonResponse(array(
                        "isSuccessful" => false,
                        "message" => "An error occurred during your request.",
                        "data" => array()
                    ), 500);
                }
            }
            else{
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Invalid rating supplied.",
                    "data" => array()
                ), 400);
            }
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Invalid feedback.",
            "data" => array()
        ), 400);
    }

    public function getUserFeedbacksAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $authorizationChecker = $this->get('security.authorization_checker');
        $userFeedbackService = $this->get('yilinker_core.service.user_feedback.user_feedback');

        $entityManager = $this->getDoctrine()->getManager();

        if(
            (
                $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
                $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ) &&
            $authenticatedUser->getUserType() === User::USER_TYPE_SELLER
        ){
            $seller = $authenticatedUser;
        }
        else{
            $seller = $entityManager->getRepository('YilinkerCoreBundle:User')
                ->getOnebyUserOrSlug($request->get('sellerId',0))
                ->getOneOrNullResult();
        }

        if(is_null($seller)){
            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Seller not found",
                "data" => array()
            ), 404);
        }

        $store = $seller->getStore();
        $page = (int)$request->request->get('page', 1);
        $limit = (int)$request->request->get('limit', 10);

        if($page < 1 || $limit == 0){
            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Invalid page or limit supplied.",
                "data" => array()
            ), 400);
        }

        $data = $userFeedbackService->getUserFeedbacks($store, $page, $limit);
        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Feedback collection.",
            "data" => $data
        ), 201);
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
