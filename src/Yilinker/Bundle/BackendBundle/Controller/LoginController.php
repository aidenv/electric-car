<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class LoginController
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class LoginController extends Controller
{

    /**
     * Renders Login Form
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderLoginAction()
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $authenticationUtils = $this->get('security.authentication_utils');
            $csrfToken = $this->get('form.csrf_provider')->generateCsrfToken('form');

            $error = $authenticationUtils->getLastAuthenticationError();

            $lastUsername = $authenticationUtils->getLastUsername();

            return $this->render(
                'YilinkerBackendBundle:Admin:login.html.twig',
                array(
                    'last_username' => $lastUsername,
                    'error' => $error,
                    'csrfToken' => $csrfToken
                )
            );
        }
        else {
            return $this->redirect($this->generateUrl('admin_home_page'));
        }

    }

}
