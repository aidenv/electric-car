<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class UserFollowApiController extends Controller
{
    /**
     * Follow the seller
     *
     * @param Request $request
     * @return mixed
     *
     * @ApiDoc(
     *     section="User Follow",
     *     parameters={
     *         {"name"="sellerId", "dataType"="string", "required"=false, "description"="query String"},
     *     }
     * )
     */
    public function followSellerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();

        $sellerId = $request->request->get('sellerId', 0);

        $seller = $em->getRepository('YilinkerCoreBundle:User')
                     ->findOneBy(array(
                        "userId" => $sellerId,
                        "userType" => User::USER_TYPE_SELLER
                     ));

        $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');
        if(is_null($seller)){
            return $userFollowService->throwUserNotFound();
        }

        $userFollowService->setAuthenticatedUser($authenticatedUser);

        $record = $userFollowService->isFollowed($seller);
        if($record){
            return $userFollowService->throwAlreadyFollowed();
        }

        return $userFollowService->followSeller($seller);
    }

    /**
     * Unfollow seller
     *
     * @param Request $request
     * @return mixed
     */
    public function unfollowSellerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();

        $sellerId = $request->request->get('sellerId', 0);

        $seller = $em->getRepository('YilinkerCoreBundle:User')
                     ->findOneBy(array(
                         "userId" => $sellerId,
                         "userType" => User::USER_TYPE_SELLER
                     ));

        $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');
        if(is_null($seller)){
            return $userFollowService->throwUserNotFound();
        }

        $userFollowService->setAuthenticatedUser($authenticatedUser);

        $record = $userFollowService->isFollowed($seller);
        if(is_null($record)){
            return $userFollowService->throwAlreadyUnfollowed();
        }

        return $userFollowService->unfollowSeller($seller, $record);
    }

    /**
     * Get followed history
     *
     * @param Request $request
     * @return mixed
     */
    public function getFollowHistoryAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');

        $page = (int)$request->request->get("page");
        $limit = (int)$request->request->get("limit");

        if($page < 1 OR $limit < 1){
            return $userFollowService->throwInvalidFields(null, false, array("Invalid limit or offset supplied"));
        }

        $userFollowService->setAuthenticatedUser($authenticatedUser);
        return $userFollowService->getFollowHistory($limit, $page);
    }

    /**
     * Get followed users
     *
     * @param Request $request
     * @return mixed
     */
    public function getFollowedSellersAction(Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');

        $page = (int)$request->request->get("page");
        $limit = (int)$request->request->get("limit");

        if($page < 1 OR $limit < 1){
            return $userFollowService->throwInvalidFields(null, false, array("Invalid limit or offset supplied"));
        }

        $userFollowService->setAuthenticatedUser($authenticatedUser);
        return $userFollowService->getFollowedSellers($request->request->get("keyword", ""), $limit, $page);;
    }

    /**
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        return $this->get('security.token_storage')->getToken()->getUser();
    }
}

