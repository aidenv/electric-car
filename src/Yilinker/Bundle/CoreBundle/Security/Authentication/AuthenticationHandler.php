<?php

namespace Yilinker\Bundle\CoreBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Repository\UserImageRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class AuthenticationHandler
 * @package Yilinker\Bundle\FrontendBundle\Services\Authenticate
 */
class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    private $router;

    private $session;

    private $container;

    /**
     * Constructor
     * @param RouterInterface $router
     * @param Session $session
     */
    public function __construct(RouterInterface $router, Session $session, $container)
    {
        $this->router  = $router;
        $this->session = $session;
        $this->container = $container;
    }

    /**
     * onAuthenticationSuccess
     *
     * @param Request $request
     * @param TokenInterface $token
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /**
         * Refresh CSRF token for default intention to avoid session fixation attacks
         */
        $csrfTokenManager = $this->container->get('security.csrf.token_manager');
        $csrfTokenManager->refreshToken($this->container->getParameter('csrf_default_intention'));
        $applicationManager = $this->container->get('yilinker_core.service.accreditation_application_manager');

        $authenticatedUser = $token->getUser();
        $language = $authenticatedUser->getLanguage();
        if ($language && $language->getLanguageId()) {
            $request->getSession()->set('_locale', $language->getCode());
        }
        if($authenticatedUser->getUserType() === User::USER_TYPE_BUYER){            
            $checkoutService = $this->container->get('yilinker_front_end.service.checkout');
            $checkoutService->cartSessionToDB();
        }
        else if($authenticatedUser->getUserType() === User::USER_TYPE_SELLER){
            $accreditationApplication = $authenticatedUser->getAccreditationApplication();

            if (!($accreditationApplication instanceof AccreditationApplication)) {
                /**
                 * If seller or affiliate does not have an accreditation application, create one
                 */
                $storeEntity = $authenticatedUser->getStore();
                $applicationManager->createApplication ($authenticatedUser, '', $storeEntity->getStoreType(), true);
            }
            else {
                /**
                 * If seller or affiliate affiliate is accredited but does not have firstname or lastname, revert back to unaccredited
                 */
                $isEmptyFirstname = $authenticatedUser->getFirstName() === '' || is_null($authenticatedUser->getFirstName());
                $isEmptyLastname = $authenticatedUser->getLastName() === '' || is_null($authenticatedUser->getLastName());
                if (($isEmptyFirstname || $isEmptyLastname) && $accreditationApplication->getAccreditationLevel()){
                    $applicationManager->revertAccreditationLevel($authenticatedUser);
                }
            }

        }

        if ( $request->isXmlHttpRequest() ) {
            $response = new JsonResponse(array(
                'success' => true
            ));
            
            $response->headers->set( 'Content-Type', 'application/json' );

            return $response;
        }
        else {
            if ( $this->session->get('_security.main.target_path' ) ) {
                $url = $this->session->get( '_security.main.target_path' );
            }
            elseif ($request->get('_target_path_success')) {
                $url = $this->router->generate($request->get('_target_path_success'));
            }
            elseif ($request->get('_target_path')) {
                $url = $this->router->generate($request->get('_target_path'));
            }
            else {
                $url = $this->router->generate('home_page');
            }

            return new RedirectResponse($url);
        }
    }

    /**
     * onAuthenticationFailure
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception )
    {
        if ( $request->isXmlHttpRequest() ) {
            $array = array( 'success' => false, 'message' => $exception->getMessage() );
            $response = new JsonResponse( $array );
            $response->headers->set( 'Content-Type', 'application/json' );

            return $response;
        } 
        else {
            $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

            if ($request->get('_target_path_error')) {
                $url = $this->router->generate($request->get('_target_path_error'));
            }
            elseif ($request->get('_target_path')) {
                $url = $this->router->generate($request->get('_target_path'));
            }
            else {
                $url = $this->router->generate( 'user_buyer_login' );
            }

            return new RedirectResponse($url);
        }
    }
}
