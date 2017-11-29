<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api\v2;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\OAuth2ServerException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class UserApiController extends Controller
{
    /**
     * Authenticate User
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="User",
     *     parameters={
     *         {"name"="client_id", "dataType"="string", "required"=true, "description"="Oauth client ID"},
     *         {"name"="client_secret", "dataType"="string", "required"=true, "description"="Oauth client secret"},
     *         {"name"="grant_type", "dataType"="string", "required"=true, "description"="Oauth grant type"},
     *         {"name"="email", "dataType"="string", "required"=true, "description"="Either email or contact number."},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="Minimum of 8 atleast 1 number."},
     *         {"name"="refresh_token", "dataType"="string", "required"=false, "description"="Only use if using refresh_token grant type"},
     *     },
     *     views = {"user", "default", "v2"}
     * )
     */

    public function tokenAction(Request $request)
    {
        $oauthServer = $this->get('fos_oauth_server.server');
        try {
            $response = $oauthServer->grantAccessToken($request);
            $content = $response->getContent();
            $jsonContent = json_decode($content, true);
            $token = $jsonContent['access_token'];

            $accessToken = $oauthServer->verifyAccessToken($token);
            $user = $accessToken->getUser();

            $cartService = $this->container->get('yilinker_front_end.service.cart');
            $cartSession = $cartService->cartSessionToDB($user);
            $cartItems = $this->get('session')->get('checkout/cartItems');
            $this->get('session')->remove('checkout');

            if ($cartItems) {
                $dbCartItems = array();
                foreach ($cartItems as $itemId) {
                    if (array_key_exists($itemId, $cartSession)) {
                        $dbCartItems[] = $cartSession[$itemId]->getId();
                    }
                }
                $this->get('session')->set('checkout/cartItems', $dbCartItems);
            }

            return $response;
        } catch (OAuth2ServerException $e) {
            return $e->getHttpResponse();
        }
    }
}
