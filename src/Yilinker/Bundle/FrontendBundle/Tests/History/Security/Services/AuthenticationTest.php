<?php

namespace Yilinker\Bundle\FrontendBundle\Tests\Security\Services;

use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;
use Yilinker\Bundle\FrontendBundle\Security\Services\Authentication;

class AuthenticationTest extends YilinkerWebTestCase
{
    /**
     * Test if the function can create a auth token
     */
    public function testAuthenticateUser()
    {
        $client = $this->client;
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $tokenStorage = $container->get('security.token_storage');

        $user = $entityManager->getRepository('Yilinker:User')->findOneBy(array("username" => "superadmin"));

        $this->assertNull($tokenStorage->getToken()); //not yet logged in

        $authService = new Authentication($tokenStorage);
        $authService->authenticateUser($user);

        $isAuthenticatedFully = $client->getContainer()
                                       ->get('security.authorization_checker')
                                       ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
        $this->assertNotNull($tokenStorage->getToken());
    }

    /**
     * Test if the user token will be null once logged out
     */
    public function testRemoveAuthentication()
    {
        $this->createAuthenticatedUser('superadmin'); //force logged in
        $client = $this->client;
        $container = $client->getContainer();

        $tokenStorage = $container->get('security.token_storage');

        $authService = new Authentication($tokenStorage);
        $authService->removeAuthentication();

        $this->assertNull($tokenStorage->getToken());
    }
}
