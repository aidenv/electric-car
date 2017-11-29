<?php

namespace Yilinker\Bundle\OAuthServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;  
use Symfony\Component\HttpFoundation\Request;  
use Symfony\Component\HttpFoundation\Response;  
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends Controller
{
    const BUYER_PREFIX = "ylo-buyer";

    const ADMIN_PREFIX = "ylo-admin";
    
    public function loginAction(Request $request)  
    {        
        $session = $request->getSession();  

        $error = "";
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {  
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);  
        }
        elseif (null !== $session && $session->has(SecurityContext::AUTHENTICATION_ERROR)) {  
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);  
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);  
        }

        if($error && $error instanceof BadCredentialsException){
            $error = "Incorrect username/password combination.";
        }

        $templating = $this->container->get('templating');
        
        $template = $templating->render('YilinkerOAuthServerBundle:Security:login.html.twig', array(  
            'error'  => $error
        ));


        $response = new Response($template, 200);
        $allowedOrigin = $this->getParameter("crm_hostname");
        $response->headers->set('Access-Control-Allow-Origin' , $allowedOrigin, true);
        $response->headers->set('Access-Control-Allow-Credentials' , 'true', true);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST', true);
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With', true);

        return $response;
    }

    public function loginCheckAction(Request $request)  
    {  
          
    }
    
    /**
     * Retrieve user object for OAUTH2 clients
     *
     * @param Request $request
     */
    public function getUserOjectAction(Request $request)
    {
        $tokenStorage = $this->container->get('security.token_storage');
        $user = $tokenStorage->getToken()->getUser();

        $jsonResponse = array(
            'isSuccessful' => false,
            'message'      => "Unsupported user type",
            'data'         => array(),
        );
        
        if($user instanceof User){
            $jsonResponse = array(
                'isSuccessful' => true,
                'message'      => "User information successfully retrieved",
                'data'         => array(
                    'username'      => $user->getContactNumber(),
                    'email'         => $user->getEmail(),
                    'firstName'     => $user->getFirstName(),
                    'lastName'      => $user->getLastName(),
                    'accountId'     => self::BUYER_PREFIX . "-". $user->getUserId(),
                    'role'          => "ROLE_BUYER",
                ),
            );
        }
        else if($user instanceof AdminUser){
            $jsonResponse = array(
                'isSuccessful' => true,
                'message'      => "Admin information successfully retrieved",
                'data'         => array(
                    'username'      => $user->getUsername(),
                    'email'         => "",
                    'firstName'     => $user->getFirstName(),
                    'lastName'      => $user->getLastName(),
                    'accountId'     => self::ADMIN_PREFIX . "-". $user->getAdminUserId(),
                    'role'          => $user->getAdminRole()->getRole(),
                ),
            );
        }

        $response = new JsonResponse($jsonResponse, 200);
        $allowedOrigin = $this->getParameter("crm_hostname");
        $response->headers->set('Access-Control-Allow-Origin' , $allowedOrigin, true);
        $response->headers->set('Access-Control-Allow-Credentials' , 'true', true);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST', true);
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With', true);

        return $response;
    }
    
}
