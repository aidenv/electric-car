<?php

namespace Yilinker\FrontendBundle\Tests\Controller;

use Carbon\Carbon;
use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;
use Yilinker\Bundle\FrontendBundle\Services\User\Verification;

class VerificationTest extends YilinkerWebTestCase
{
    /**
     * Test if token is created for the user
     */
    public function testCreateVerificationToken()
    {
        $user = $this->getUserEntity();

        $verificationService = new Verification($this->getEntityManager());
        $verificationService->createVerificationToken($user);

        $this->assertNotNull($user->getVerificationToken());
    }

    /**
     * Test if email verification will success
     */
    public function testConfirmVerificationTokenSuccess()
    {
        $user = $this->getUserEntity();

        $verificationService = new Verification($this->getEntityManager());
        $verificationService->createVerificationToken($user);

        $token = $user->getVerificationToken();

        $verificationService->confirmVerificationToken($user, $token);
        $this->assertTrue($user->getIsEmailVerified());
    }

    /**
     * Test if email verification will fail
     */
    public function testConfirmVerificationTokenFail()
    {
        $user = $this->getUserEntity();

        $verificationService = new Verification($this->getEntityManager());
        $verificationService->createVerificationToken($user);

        $token = sha1(uniqid());

        $verificationService->confirmVerificationToken($user, $token);
        $this->assertFalse($user->getIsEmailVerified());
    }

    public function getUserEntity()
    {
        $entityManager = $this->getEntityManager();

        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');
        return $userRepository->loadUserByUsername('superadmin');
    }

    public function getEntityManager()
    {
        $client = $this->client;
        $container = $client->getContainer();
        return $container->get('doctrine.orm.entity_manager');
    }
}
