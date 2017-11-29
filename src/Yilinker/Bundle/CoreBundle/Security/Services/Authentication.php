<?php

namespace Yilinker\Bundle\CoreBundle\Security\Services;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Yilinker\Bundle\CoreBundle\Entity\User;

class Authentication
{
    /**
     * @var Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    private $tokenStorage;

    /**
     * @var Symfony\Component\Security\Csrf\CsrfTokenManager
     */
    private $csrfTokenManager;

    /**
     * @var string
     */
    private $defaultCsrfIntention;

    private $container;

    /**
     * Constructor
     *
     * @param TokenStorage $tokenStorage
     * @param CsrfTokenManager $csrfTokeManager
     * @param string $defaultCsrfIntention
     */
    public function __construct(TokenStorage $tokenStorage, CsrfTokenManager $csrfTokeManager, $defaultCsrfIntention)
    {
        $this->tokenStorage = $tokenStorage;
        $this->csrfTokeManager = $csrfTokeManager;
        $this->defaultCsrfIntention = $defaultCsrfIntention;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Authenticates/logs in the user
     * uses in (successful registration)
     * @param User $user
     */
    public function authenticateUser(User $user, $firewall = 'default', $roles = null)
    {
        if($roles === null){
            $roles = $user->getRoles();
        }

        $language = $user->getLanguage();
        if ($language) {
            $requestStack = $this->container->get('request_stack');
            $request = $requestStack->getCurrentRequest();
            if ($request) {
                $request->getSession()->set('_locale', $language->getCode());
            }
        }

        $token = new UsernamePasswordToken($user, null, $firewall, $roles);
        $this->tokenStorage->setToken($token);
        
        /**
         * Refresh CSRF token for default intention to avoid session fixation attacks
         */
        $this->csrfTokeManager->refreshToken($this->defaultCsrfIntention);
    }

    /**
     * Remember the user
     * uses in (successful registration)
     * @param User $user
     */
    public function rememberUser(User $user, $firewall = 'default', $secret = null)
    {
        $token = new RememberMeToken($user, $firewall, $secret);
        $this->tokenStorage->setToken($token);
    }

    /**
     * Logs out the user
     */
    public function removeAuthentication()
    {
        $this->tokenStorage->setToken(null);
    }
}

