<?php

namespace Yilinker\FrontendBundle\Tests\Controller;

use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;
use Yilinker\Bundle\FrontendBundle\Services\User\Verification;

class MailerTest extends YilinkerWebTestCase
{
    /**
     * Test sendEmailVerificationAction and sendEmail.
     */
    public function testSendEmailSuccess()
    {
        $client = $this->createAuthenticatedClient("superadmin", "123456");
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository('Yilinker:User')
                              ->findOneBy(array("username" => "superadmin"));

        $verificationService = new Verification($entityManager);
        $verificationService->createVerificationToken($user);

        // Enable the profiler for the next request (it does nothing if the profiler is not available)
        $client->enableProfiler();

        $client->request('GET', '/user/send-verification');

        $mailCollector = $client->getProfile()
                                ->getCollector('swiftmailer');

        // Check that an email was sent
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];

        // Asserting email data
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals('Yilinker Account Confirmation', $message->getSubject());
        $this->assertEquals('noreply@easyshop.ph', key($message->getFrom()));
        $this->assertEquals($user->getEmail(), key($message->getTo()));
    }

    /**
     * Test sendEmailVerificationAction and sendEmail.
     */
    public function testSendEmailFail()
    {
        $client = $this->createAuthenticatedClient("superadmin", "123456");
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository('Yilinker:User')->findOneBy(array("username" => "superadmin"));
        $user->setIsEmailVerified(true);

        $verificationService = new Verification($entityManager);
        $verificationService->createVerificationToken($user);

        // Enable the profiler for the next request (it does nothing if the profiler is not available)
        $client->enableProfiler();

        $client->request('GET', '/user/send-verification');

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        // Check that an email was not sent
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }
}
