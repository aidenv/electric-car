<?php

namespace Yilinker\Bundle\OAuthServerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yilinker\Bundle\CoreBundle\Entity\OauthClient;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\OAuthServerBundle\Form\Model\Authorize;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class AuthorizeController extends Controller
{
    /**
     * Authorize the Resource Owner for Implicit/Authorization OAuth2 grants
     */
    public function authorizeAction(Request $request)
    {
        if (!$request->get('client_id')) {
            throw new NotFoundHttpException("Client id parameter {$request->get('client_id')} is missing.");
        }

        $clientManager = $this->container->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->findClientByPublicId($request->get('client_id'));

        if (!($client instanceof OauthClient)) {
            throw new NotFoundHttpException("Client {$request->get('client_id')} is not found.");
        }

        $user = $this->container->get('security.context')->getToken()->getUser();
        $form = $this->container->get('yilinker_oauth_server.form.factory.authorize');
        $formHandler = $this->container->get('yilinker_oauth_server.form.handler.authorize');
        $authorize = new Authorize();

        $entityManager = $this->getDoctrine()->getManager();
        $allowedOrigin = $this->getParameter("crm_hostname");

        if($request->getMethod() == 'GET'){

            if($user instanceof User){
                $grantRepository = $entityManager->getRepository("YilinkerCoreBundle:OauthUserGrant");
            }
            else if($user instanceof AdminUser){
                $grantRepository = $entityManager->getRepository("YilinkerCoreBundle:OauthAdminGrant");
            }

            $existingGrant = $grantRepository->findOneBy(array(
                'client' => $client,
                'user'   => $user,
            ));

            /**
             * If auth code already exists, programatically set form values
             */
            if($existingGrant){
                $request->setMethod("POST");
                $authorize->setAllowAccess(true);
            }
        }

        if (($response = $formHandler->process($request, $authorize, $allowedOrigin, $client)) !== false){
            $uri = $request->query->get("redirect_uri", null);
            $json = json_decode($response->getContent(), true);

            $code = (array_key_exists("data", $json) && array_key_exists("code", $json["data"]))?
                $json["data"]["code"] : null;

            if(is_null($code)){
                $response = $this->getAllowResponse($form, $client);
            }
            else{
                $response = $this->redirect($uri."?code=".$code);
            }
        }
        else{
            $response = $this->getAllowResponse($form, $client);
        }

        $response->headers->set('Access-Control-Allow-Origin' , $allowedOrigin, true);
        $response->headers->set('Access-Control-Allow-Credentials' , 'true', true);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST', true);
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With', true);

        return $response;
    }

    private function getAllowResponse($form, $client)
    {
        $templating = $this->container->get('templating');
        $failureEndpoint = $this->getParameter("failure_crm_endpoint");

        $template = $templating->render('YilinkerOAuthServerBundle:Authorize:authorize.html.twig', array(
             'form'   => $form->createView(),
             'client' => $client,
             'failureEndpoint' => $failureEndpoint
         ));

        return new Response($template, 200);
    }
}
