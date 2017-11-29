<?php

namespace Yilinker\Bundle\OAuthServerBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Yilinker\Bundle\OAuthServerBundle\Form\Model\Authorize; 
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use OAuth2\OAuth2;  
use OAuth2\OAuth2ServerException;  
use OAuth2\OAuth2RedirectException;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\OauthUserGrant;
use Yilinker\Bundle\CoreBundle\Entity\OauthAdminGrant;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;

class AuthorizeFormHandler
{  
    protected $request;
    
    protected $form;

    protected $context;

    protected $oauth2;

    protected $em;

    public function __construct(Form $form, SecurityContextInterface $context, OAuth2 $oauth2, EntityManager $em)  
    {
        $this->form = $form;  
        $this->context = $context;  
        $this->oauth2 = $oauth2;
        $this->em = $em;
    }

    public function process(Request $request, Authorize $authorize, $allowedOrigin, $client)  
    {
        $this->form->setData($authorize);
        
        if ($request->getMethod() == 'POST') {

            $this->form->submit($request);
            if ($this->form->isValid()) {

                try {  
                    $user = $this->context->getToken()->getUser();  
                    $response = $this->oauth2->finishClientAuthorization(true, $user, $request, null);
                    
                    $query = parse_url($response->headers->get('location'), PHP_URL_QUERY);
                    parse_str($query, $params);

                    $json = array(
                        'isSuccessful' => false,
                        'message'      => "Something went wrong. Please try again later",
                        'data'         => array(),
                    );
                    
                    if(isset($params['code'])){

                        /** 
                         * Persist user access grant into the database
                         */
                        $grant = null;
                        if($user instanceof User){
                            $grant = new OauthUserGrant();
                        }
                        else if($user instanceof AdminUser){
                            $grant = new OauthAdminGrant();
                        }

                        if($grant){
                            $grant->setClient($client);
                            $grant->setUser($user);
                            $this->em->persist($grant);
                            $this->em->flush();
                        }
                        
                        $json['isSuccessful'] = true;
                        $json['message'] = "OAuth2 authorization successful.";
                        $json['data'] = array( 'code' => $params['code'] );
                    }

                    $response = new JsonResponse($json, 200);

                    $response->headers->set('Access-Control-Allow-Origin' , $allowedOrigin, true);
                    $response->headers->set('Access-Control-Allow-Credentials' , 'true', true);
                    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST', true);
                    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With', true);

                    return $response;
                }
                catch (OAuth2ServerException $e){
                    return $e->getHttpResponse();
                }
            }
        }

        return false;
    }
}
