<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HeaderController extends Controller
{

    /**
     * Render the merchant header 
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderHeaderAction()
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        
        $jwt = null;
        $messages = 0;

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $entityManager = $this->getDoctrine()->getManager();
            $authenticatedUser = $this->container
                                      ->get('security.token_storage')
                                      ->getToken()
                                      ->getUser();

            $messages = $entityManager->getRepository('YilinkerCoreBundle:Message')
                                      ->getCountUnonepenedMessagesByUser($authenticatedUser);
            $jwtService = $this->get("yilinker_core.service.jwt_manager");
            $jwt = $jwtService->encodeToken(array("userId" => $authenticatedUser->getUserId()));
        }

        $baseUri = $this->getParameter('frontend_hostname');
        $nodePort = $this->getParameter('node_messaging_port');

        return $this->render('YilinkerMerchantBundle:Base:header.html.twig', compact('messages', 'jwt', 'baseUri', 'nodePort'));
    }

}
