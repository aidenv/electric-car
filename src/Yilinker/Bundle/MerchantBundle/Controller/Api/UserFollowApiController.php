<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserFollowApiController
 * @package Yilinker\Bundle\MerchantBundle\Controller\Api
 */
class UserFollowApiController extends Controller
{
    const PAGE_LIMIT = 10;

    /**
     * Get Followers by authenticated User
     * @param Request $request
     * @return JsonResponse
     */
    public function getFollowersAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userEntity = $this->getUser();
        $page = (int) $request->query->get('page', 1);
        $searchKeyword = $request->query->get('searchKeyword', null);
        $pageLimit = $request->query->get('perPage', self::PAGE_LIMIT);

        $listOfFollowers = $em->getRepository('YilinkerCoreBundle:UserFollow')
                              ->getFollowers ($userEntity, $this->getOffset($pageLimit, $page), $pageLimit, $searchKeyword);
        $userFollowManager = $this->get('yilinker_merchant.service.user.user_follow');
        $listOfFollowersArray = $userFollowManager->constructUser($listOfFollowers);

        $response = array (
            'isSuccessful' => true,
            'message' => 'Successfully fetched list of followers',
            'data' => $listOfFollowersArray
        );

        return new JsonResponse($response);
    }

    private function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }
}
