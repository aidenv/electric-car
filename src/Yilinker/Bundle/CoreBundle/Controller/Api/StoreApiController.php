<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Request;

class StoreApiController extends Controller
{
    /**
     * Search for a particular storename
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchStorenameAction(Request $request)
    {
        $query = $request->get('queryString');
        $perPage = $request->get('perPage', 15);
        $page = $request->get('page', 1);

        $result = array(
            'isSuccessful' => false,
            'data' => array(),
            'message' => 'No result found',
        );

        $stores = array();
        $searchResult = $this->get('yilinker_core.service.search.store')
                             ->searchStoreWithElastic($query, 0, null, null, $page, $perPage);
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();
        $securityContext = $this->container->get('security.context');
        $isAuthenticated = $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY');

        if($searchResult['totalResultCount'] > 0){
            $result['isSuccessful'] = true;
            $result['message'] = $searchResult['totalResultCount'] . " result(s) found";
            foreach($searchResult['stores'] as $store){

                $seller = $store->getUser();
                $primaryImage = $seller->getPrimaryImage() ? $seller->getPrimaryImage()->getFullImagePath() : null;
                $assetsHelper = $this->get('templating.helper.assets');
                if($primaryImage === null){
                    $primaryImage = $assetsHelper->getUrl(UserImage::DEFAULT_DIRECTORY . DIRECTORY_SEPARATOR . UserImage::DEFAULT_SELLER_AVATAR_FILE, 'user');
                }

                $products = array();
                $latestUploadUnits = $em->getRepository('YilinkerCoreBundle:ProductUnit')->getLatestUploadedProducts($seller, Product::ACTIVE, 5);
                foreach($latestUploadUnits as $latesUploadUnit){
                    $product = $latesUploadUnit->getProduct();
                    $products[] = array(
                        'productId'        => $product->getProductId(),
                        'name'             => $product->getName(),
                        'slug'             => $product->getSlug(),
                        'shortDescription' => $product->getShortDescription(),
                        'image'            => $assetsHelper->getUrl($product->getPrimaryImageLocation(), 'product'),
                    );
                }

                $isFollowed = false;
                if($isAuthenticated && $authenticatedUser->getUserType() === User::USER_TYPE_BUYER){
                    $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');
                    $userFollowService->setAuthenticatedUser($authenticatedUser);
                    $isFollowed = $userFollowService->isFollowed($store->getUser());
                }

                $result['data'][] = array(
                    'userId'      => $seller->getUserId(),
                    'specialty'   => $store->getSpecialtyCategory() ? $store->getSpecialtyCategory()['name'] : "",
                    'storeName'   => $store->getStoreName() ? $store->getStoreName() : "",
                    'slug'        => $store->getStoreSlug() ? $store->getStoreSlug() : "",
                    'description' => $store->getStoreDescription() ? $store->getCleanDescription() : "",
                    'isFollowed'  => $isFollowed,
                    'image'       => $primaryImage,
                    'products'    => $products,
                );
            }
            
        }

        return new JsonResponse($result);
    }
}
