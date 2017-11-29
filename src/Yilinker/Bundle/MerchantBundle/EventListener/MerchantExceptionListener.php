<?php

namespace Yilinker\Bundle\MerchantBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class MerchantExceptionListener
{
    /**
     * @var Symfony\Component\Security\Core\Authentication\Token\Storage $tokenStorage
     */
    private $tokenStorage;

    /**
     * @var Symfony\Bundle\FrameworkBundle\Routing\Router $router
     */
    private $router;

    private $twig;

    private $container;
    /**
     * Constructor
     *
     * @param Symfony\Component\Security\Core\Authentication\Token\Storage $tokenStorage
     * @param Symfony\Bundle\FrameworkBundle\Routing\Router $router
     * @param Twig_Environment $twig
     */
    public function __construct($tokenStorage, $router, $twig,$container)
    {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->twig = $twig;
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        $this->container->get('yilinker_core.service.user.mailer')->sendError($exception);

        if(($exception instanceof HttpException)){
            if($exception->getStatusCode() === 403){
                /**
                 * Handle unauthorized exception
                 */
                $token =  $this->tokenStorage->getToken();
                $roles = array();
                foreach($token->getRoles() as $role) {
                    $roles[] = $role->getRole();
                }


                if(is_null($token->getUser()) || $token->getUser()->getUserType() === User::USER_TYPE_BUYER ){
                    /**
                     * Handle cases where user is NULL (client credential oauth) or is of type buyer
                     * This can only happen for the API but is worth handling for development clarity
                     */
                    $jsonResponse = new JsonResponse(array(
                        'isSuccessful' => false,
                        'message'      => "This user is not allowed to access this resource.",
                    ));
                    $event->setResponse($jsonResponse, 403);
                }
                else if ((int) $token->getUser()->getStore()->getStoreType() === Store::STORE_TYPE_MERCHANT) {
                    $route = "home_page";
                    if (in_array('ROLE_UNACCREDITED_MERCHANT', $roles)) {
                        $route = "merchant_accreditation";
                    }

                    $url = $this->router->generate($route);
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }
                else {
                    $referer = $event->getRequest()->headers->get('referer');
                    $route = $this->router->generate('user_affiliate_login');

                    if (strpos($referer, $route) !== false) {
                        $redirectRoute = $this->router->generate('user_store_information');
                        $response = new RedirectResponse($redirectRoute);
                    }
                    else {
                        $response = new Response($this->twig->render('YilinkerMerchantBundle:AccessDenied:access_denied.html.twig'));
                    }

                    $event->setResponse($response);
                }
            }
        }
    }
}
