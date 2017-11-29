<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\FrontendBundle\Oauth\GrantExtension\BuyerGrantExtension;
use Yilinker\Bundle\CoreBundle\Entity\OauthProvider;
use Yilinker\Bundle\CoreBundle\Entity\OauthAccessToken;
use Carbon\Carbon;
use Facebook\Facebook;
use Google_Client;
use Google_Service_Plus;
use Google_Service_Oauth2;
use Google_Auth_Exception;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class SocialMediaApiController extends Controller
{
    /**
     * Authenticate a facebook user via the facebook returned token
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function authenticateFacebookUserAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => '',
            'data'         => array(),
        );

        $token = $request->get('token', '');

        $validationResponse = $this->validateOauthUser($request);
        if($validationResponse['isSuccessful']){
            $oauthClient = $validationResponse['oauthClient'];

            $em  = $this->get('doctrine')->getManager();
            $facebook = new Facebook(array(
                'app_id'  => $this->container->getParameter('facebook_client_id'),
                'app_secret' => $this->container->getParameter('facebook_client_secret'),
            ));

            $oAuth2Client = $facebook->getOauth2Client();

            try {
                $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($token);
                $facebook->setDefaultAccessToken($longLivedAccessToken);

                $fbResponse = $facebook->get('/me?fields=name,email');
                $userNode = $fbResponse->getGraphUser();
                $graphObject = $fbResponse->getGraphObject();

                if($userNode){
                    $responseOauthProvider = OauthProvider::OAUTH_PROVIDER_FACEBOOK;
                    $socialMediaId = $graphObject->getProperty('id');
                    $oauthProvider = $em->getRepository('YilinkerCoreBundle:OauthProvider')
                                        ->find($responseOauthProvider);
                    $registeredOauthUser = $em->getRepository('YilinkerCoreBundle:UserMerge')
                                              ->findOneBy(array(
                                                  'socialMediaId' => $socialMediaId,
                                                  'oauthProvider' => $oauthProvider->getOauthProviderId()
                                              ));

                    if($registeredOauthUser !== null){
                        $accessToken = $this->createAccessToken($registeredOauthUser->getUser(), $oauthClient);
                        $response['isSuccessful'] = true;
                        $response['message'] = 'User successfully authenticated';
                        $response['data'] = $accessToken;
                    }
                    else{
                        $email = $graphObject->getProperty('email');
                        $fullname = $graphObject->getProperty('name');

                        $existingUserWithEmail = $em->getRepository('YilinkerCoreBundle:User')
                                                    ->findOneByEmail($email);

                        if($existingUserWithEmail === null){

                            $em->beginTransaction();

                            try{
                                $lastname = "";
                                $firsname = "";

                                if($fullname){
                                    $explodedFullname = explode(' ', $fullname);
                                    $lastname = array_pop($explodedFullname);
                                    $firstname = implode(' ', $explodedFullname);
                                }

                                $plainPassword = 'YILINKER' . '-' . rand(1, 999) . '-' . strtotime(Carbon::now());

                                $storeService = $this->get("yilinker_core.service.entity.store");
                                $socialMediaManager = $this->get('yilinker_front_end.service.social_media.social_media_manager');

                                $buyer = $socialMediaManager->registerAccount($email, $firstname, $lastname, $socialMediaId, $oauthProvider, $plainPassword, User::USER_TYPE_BUYER, false);
                                $affiliate = $socialMediaManager->registerAccount($email, $firstname, $lastname, $socialMediaId, $oauthProvider, $plainPassword, User::USER_TYPE_SELLER, false);

                                $mailer = $this->container->get('yilinker_core.service.user.mailer');
                                $t = $mailer->sendAutoGeneratedPassword ($buyer, $plainPassword);

                                $jwtService = $this->get("yilinker_core.service.jwt_manager");
                                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($buyer)->encodeToken(null);

                                $ylaService = $this->get("yilinker_core.service.yla_service");
                                $ylaService->setEndpoint(false);

                                $response = $ylaService->sendRequest("user_create", "post", array("request" => $request));

                                $buyer->setAccountId($response["data"]["userId"]);
                                $affiliate->setAccountId($response["data"]["userId"]);

                                $store = $storeService->createStore($affiliate, Store::STORE_TYPE_RESELLER);

                                $store->setStoreNumber($storeService->generateStoreNumber($store));

                                $accessToken = $this->createAccessToken($buyer, $oauthClient);
                                $response['isSuccessful'] = true;
                                $response['message'] = 'User successfully registered';
                                $response['data'] = $accessToken;

                                $em->flush();
                                $em->commit();
                            }
                            catch(Exception $e){
                                $em->rollback();
                                $response['message'] = "Failed to login via facebook. Please try again later";
                            }
                        }
                        else{
                            $data = array(
                                'isExisting' => true,
                            );
                            $response['data'] = $data;
                            $response['message'] = "The email address ".$email." is already in use";
                        }
                    }
                }

            } catch(\Facebook\Exceptions\FacebookResponseException $e) {
                $response['message']  = 'Graph returned an error: ' . $e->getMessage();
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                $response['message']  = 'Facebook SDK returned an error: ' . $e->getMessage();
            } catch(\OAuth2\OAuth2ServerException $e) {
                $response['message']  = $e->getMessage();
            }
        }
        else{
            $response['message'] = $validationResponse['message'];
        }

        return new JsonResponse($response);
    }

    /**
     * Authenticate a google user
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function authenticateGoogleUserAction(Request $request)
    {
        $response = array(
            'message'       => 'OAuth2 grant type is not allowed',
            'isSuccessful'  => false,
            'data'          => array(),
        );

        $tokenId = $request->get('token', '');

        $validationResponse = $this->validateOauthUser($request);
        if($validationResponse['isSuccessful']){

            $em  = $this->get('doctrine')->getManager();
            $userAttributes = false;
            try{
                $clientId = $this->container->getParameter('google_client_id');
                $oauthClient = $validationResponse['oauthClient'];
                $googleClient = new Google_Client();
                $googleClient->setClientId($clientId);
                $googleClient->setClientSecret($this->container->getParameter('google_client_secret'));
                $googleClient->addScope("https://www.googleapis.com/auth/userinfo.profile");
                $googleClient->addScope("https://www.googleapis.com/auth/plus.login");
                $userAttributes = $googleClient->verifyIdToken($tokenId)->getAttributes();
             }
             catch(Google_Auth_Exception $e){
                 $response['message'] = $e->getMessage();
             }

             if($userAttributes){
                 $responseOauthProvider = OauthProvider::OAUTH_PROVIDER_GOOGLE;
                 $socialMediaId = $userAttributes['payload']['sub'];
                 $oauthProvider = $em->getRepository('YilinkerCoreBundle:OauthProvider')
                                     ->find($responseOauthProvider);
                 $registeredOauthUser = $em->getRepository('YilinkerCoreBundle:UserMerge')
                                           ->findOneBy(array(
                                               'socialMediaId' => $socialMediaId,
                                               'oauthProvider' => $oauthProvider->getOauthProviderId()
                                           ));

                 if($registeredOauthUser !== null){
                     $accessToken = $this->createAccessToken($registeredOauthUser->getUser(), $oauthClient);
                     $response['isSuccessful'] = true;
                     $response['message'] = 'User successfully authenticated';
                     $response['data'] = $accessToken;
                 }
                 else{
                     $email = $userAttributes['payload']['email'];

                     $existingUserWithEmail = $em->getRepository('YilinkerCoreBundle:User')
                                                 ->findOneByEmail($email);

                     if($existingUserWithEmail === null){

                         $em->beginTransaction();

                         try{
                            $emailName = substr($email, 0, strpos($email, '@'));

                            $plainPassword = 'YILINKER' . '-' . rand(1, 999) . '-' . strtotime(Carbon::now());

                            $storeService = $this->get("yilinker_core.service.entity.store");
                            $socialMediaManager = $this->get('yilinker_front_end.service.social_media.social_media_manager');
                            
                            $buyer = $socialMediaManager->registerAccount($email, $emailName, '',  $socialMediaId, $oauthProvider, $plainPassword, User::USER_TYPE_BUYER);
                            $affiliate = $socialMediaManager->registerAccount($email, $emailName, '',  $socialMediaId, $oauthProvider, $plainPassword, User::USER_TYPE_SELLER);

                            $mailer = $this->container->get('yilinker_core.service.user.mailer');
                            $mailer->sendAutoGeneratedPassword ($buyer, $plainPassword);

                            $jwtService = $this->get("yilinker_core.service.jwt_manager");
                            $request = $jwtService->setKey("ylo_secret_key")->encodeUser($buyer)->encodeToken(null);

                            $ylaService = $this->get("yilinker_core.service.yla_service");
                            $ylaService->setEndpoint(false);

                            $response = $ylaService->sendRequest("user_create", "post", array("request" => $request));

                            $buyer->setAccountId($response["data"]["userId"]);
                            $affiliate->setAccountId($response["data"]["userId"]);

                            $store = $storeService->createStore($affiliate, Store::STORE_TYPE_RESELLER);
                            $store->setStoreNumber($storeService->generateStoreNumber($store));

                            $accessToken = $this->createAccessToken($buyer, $oauthClient);
                            $response['isSuccessful'] = true;
                            $response['message'] = 'User successfully registered';
                            $response['data'] = $accessToken;

                            $em->flush();
                            $em->commit();
                        }
                        catch(Exception $e){
                            $em->rollback();
                            $response['message'] = "Failed to login via google. Please try again later";
                        }
                    }
                    else{
                        $data = array(
                            'isExisting' => true,
                        );
                        $response['data'] = $data;
                        $response['message'] = "The email address ".$email." is already in use";
                    }
                }
            }
        }
        else{
            $response['message'] = $validationResponse['message'];
        }

        return new JsonResponse($response);
    }

    /**
     * Request account merging
     *
     * @param Request $request
     * @return Response
     */
    public function requestAccountMergeAction(Request $request)
    {
        $response = array(
            'message'       => 'Merging is currently unavailable',
            'isSuccessful'  => false,
            'data'          => array(),
        );

        $accountType = strtolower($request->get('accountType', 'facebook'));
        $token = $request->get('token', '');

        $validationResponse = $this->validateOauthUser($request);
        if($validationResponse['isSuccessful']){
            $oauthClient = $validationResponse['oauthClient'];
            $em = $this->get('doctrine')->getManager();
            $hasException = false;
            try{
                if($accountType === 'facebook'){
                    $facebook = new Facebook(array(
                        'app_id'  => $this->container->getParameter('facebook_client_id'),
                        'app_secret' => $this->container->getParameter('facebook_client_secret'),
                    ));
                    $oAuth2Client = $facebook->getOauth2Client();
                    $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($token);
                    $facebook->setDefaultAccessToken($longLivedAccessToken);
                    $fbResponse = $facebook->get('/me?fields=name,email');
                    $graphObject = $fbResponse->getGraphObject();
                    $oauthProviderId = OauthProvider::OAUTH_PROVIDER_FACEBOOK;
                    $socialMediaId = $graphObject->getProperty('id');
                    $email = $graphObject->getProperty('email');
                }
                else if($accountType === 'google'){
                    $googleClient = new Google_Client();
                    $googleClient->setClientId($this->container->getParameter('google_client_id'));
                    $googleClient->setClientSecret($this->container->getParameter('google_client_secret'));
                    $googleClient->addScope("https://www.googleapis.com/auth/userinfo.email");
                    $googleClient->addScope("https://www.googleapis.com/auth/userinfo.profile");
                    $userAttributes = $googleClient->verifyIdToken($token)->getAttributes();
                    $oauthProviderId = OauthProvider::OAUTH_PROVIDER_GOOGLE;
                    $socialMediaId = $userAttributes['payload']['sub'];
                    $email = $userAttributes['payload']['email'];
                }
            }
            catch(\Facebook\Exceptions\FacebookResponseException $e) {
                $response['message']  = 'Graph returned an error: ' . $e->getMessage();
                $hasException = true;
            }
            catch(\Facebook\Exceptions\FacebookSDKException $e) {
                $response['message']  = 'Facebook SDK returned an error: ' . $e->getMessage();
                $hasException = true;
            }
            catch(Google_Auth_Exception $e){
                $response['message'] = $e->getMessage();
                $hasException = true;
            }
            catch(\OAuth2\OAuth2ServerException $e) {
                $response['message']  = $e->getMessage();
                $hasException = true;
            }

            if(!$hasException){
                $existingUserWithEmail = $em->getRepository('YilinkerCoreBundle:User')
                                            ->findOneByEmail($email);
                $registeredOauthUser = $em->getRepository('YilinkerCoreBundle:UserMerge')
                                          ->findOneBy(array(
                                              'socialMediaId' => $socialMediaId,
                                              'oauthProvider' => $oauthProviderId,
                                          ));

                if($existingUserWithEmail){
                    if($registeredOauthUser === null){
                        /**
                         * Merge the accounts immediately
                         */
                        $em->beginTransaction();
                        try{
                            $socialMediaManager = $this->get('yilinker_front_end.service.social_media.social_media_manager');
                            $oauthProvider = $em->getRepository('YilinkerCoreBundle:OauthProvider')->find($oauthProviderId);
                            $userEntity = $socialMediaManager->mergeAccount($existingUserWithEmail, $socialMediaId, $oauthProvider);
                            $token = new UsernamePasswordToken($userEntity, null, 'buyer', $userEntity->getRoles());
                            $jwtService = $this->get("yilinker_core.service.jwt_manager");
                            $request = $jwtService->setKey("ylo_secret_key")->encodeUser($userEntity)->encodeToken(null);
                            $ylaService = $this->get("yilinker_core.service.yla_service");
                            $ylaService->setEndpoint(false);
                            $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

                            $em->commit();
                            $accessToken = $this->createAccessToken($userEntity, $oauthClient);
                            $response['message'] = "Account successfully merged";
                            $response['isSuccessful'] = true;
                            $response['data'] = $accessToken;
                        }
                        catch(\Exception $e){
                            $em->rollback();
                            $response['message'] = "Account merging is currently unavailable";
                            $response['message'] = $e->getMessage();
                        }
                    }
                    else{
                        $response['message'] = "This social media account is already registered.";
                    }
                }
                else{
                    $response['message'] = "This email is not yet in use.";
                }
            }
        }
        else{
            $response['message'] = $validationResponse['message'];
        }

        return new JsonResponse($response);
    }


    /**
     * Creates an access token manually
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $user
     * @param Yilinker\Bundle\CoreBundle\Entity\OauthClient $client
     * @param string $scope
     */
    private function createAccessToken($user, $oauthClient, $scope = 'user')
    {
        $buyerGrantExtension = $this->get('yilinker_front_end.api.oauth.buyer_extension');
        $buyerGrantExtension->setIgnorePassword(true);
        $buyerGrantExtension->checkGrantExtension($oauthClient, array('email' => $user->getEmail()), array());
        $accessToken =  $this->get('fos_oauth_server.server')
                            ->createAccessToken($oauthClient, $user, $scope);
        $accessToken['isExisting'] = false;

        return $accessToken;
    }

    /**
     * Manually Validates the OAuth user based on the request parameter
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    private function validateOauthUser(Request $request)
    {
        $response = array(
            'message'        => 'OAuth2 grant type is not allowed',
            'isSuccessful'   => false,
            'oauthClient'    => null,
        );

        $clientId = $request->get('client_id');
        $clientSecret = $request->get('client_secret');
        $grantType = trim($request->get('grant_type'));

        if($grantType === BuyerGrantExtension::GRANT_URI){

            $response['message'] = 'Oauth client id/client secret is invalid';

            $em  = $this->get('doctrine')->getManager();
            $oauthClient = $this->get('fos_oauth_server.client_manager.default')
                                ->findClientByPublicId($clientId);
            $isValidOuathClient = $oauthClient ? $oauthClient->getSecret() === $clientSecret : null;

            if($isValidOuathClient){
                $response['message'] = '';
                $response['isSuccessful'] = true;
                $response['oauthClient'] = $oauthClient;
            }
        }

        return $response;
    }

    /**
     * Google token action viewing. Use this to view the access token details for a certain user
     * This is for testing only. Do not deploy into production (handled by routing)
     * This endpoint was put in because google+ does not have a facility for creating test users.
     * Simply access the endpoint and you will be given the following response based on the logged-in
     * google account in your browser:
     *
     * {
     *   "access_token": "",
     *   "token_type": "Bearer",
     *   "expires_in": 3599,
     *   "id_token": ""
     *   "created": 1448560313
     * }
     *
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     */
    public function getGoogleTokenAction(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId($this->container->getParameter('google_client_id'));
        $client->setClientSecret($this->container->getParameter('google_client_secret'));

        $client->addScope("https://www.googleapis.com/auth/userinfo.profile");
        $client->addScope("https://www.googleapis.com/auth/userinfo.email");
        $redirect_uri = $this->container->getParameter('frontend_hostname').$this->generateUrl('api_google_token');
        $client->setRedirectUri($redirect_uri);

        if (! isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        }
        else {
            try{
                $client->authenticate($_GET['code']);
                return new JsonResponse(json_decode($client->getAccessToken()));
            }
            catch(\Google_Auth_Exception $e){
                return new JsonResponse(array(
                    'message' => 'Google OAuth API returned an error: ' . $e->getMessage(),
                ));
            }
        }
        exit();
    }



}
