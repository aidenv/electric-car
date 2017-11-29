<?php

namespace Yilinker\Bundle\FrontendBundle\Tests\Controller;

use Yilinker\Bundle\FrontendBundle\Services\User\Verification;
use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;

/**
 * Functional Tests for User Controller
 */
class UserControllerTest extends YilinkerWebTestCase
{
    /**
     * Success testing for register user
     * @dataProvider registerFormValidDataProvider
     * @param array $data
     */
    public function testCreateUserActionSuccess($data)
    {
        $client = $this->client;
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $data['_token'] = $container->get('form.csrf_provider')->generateCsrfToken('core_user_add');

        $client->request('POST', '/user/register', array('user_add' => $data));

        $newUser = $entityManager->getRepository('YilinkerCoreBundle:User')
                                 ->findOneBy(array("email" => $data["email"]));

        $this->assertEquals($data['email'], $newUser->getEmail());

        $this->createAuthenticatedUser($data['email']);

        $isAuthenticatedFully = $client->getContainer()
                                       ->get('security.authorization_checker')
                                       ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
    }

    /**
     * data provider for testCreateUserActionSuccess
     * @return array
     */
    public function registerFormValidDataProvider()
    {
        return array(
            array(
                array(
                    "plainPassword" => array(
                        "first" => "123456",
                        "second" => "123456"
                    ),
                    "email" => "kevin.baisas@gmail.com",
                    "contact_number" => "0193810"
                )
            ),
            array(
                array(
                    "plainPassword" => array(
                        "first" => "123456",
                        "second" => "123456"
                    ),
                    "email" => "admin@admin.ad",
                    "contact_number" => "718193018"
                )
            )
        );
    }
    /**
     * Fail testing for register user
     * @dataProvider registerFormInvalidDataProvider
     * @param array $data
     */
    public function testCreateUserActionFail($data)
    {
        $client = $this->client;
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $data['_token'] = $container->get('form.csrf_provider')->generateCsrfToken('core_user_add');

        $client->request('POST', '/user/register', array('user_add' => $data));

        $newUser = $entityManager->getRepository('YilinkerCoreBundle:User')
                                 ->findOneBy(array("username" => $data["username"]));

        $this->assertNull($newUser);
    }

    /**
     * data provider for testCreateUserActionSuccess
     * @return array
     */
    public function registerFormInvalidDataProvider()
    {
        return array(
            array(
                //test for invalid user name
                array(
                    "username" => "super-user",
                    "plainPassword" => array(
                        "first" => "123456",
                        "second" => "123456"
                    ),
                    "email" => "kevin.baisas@gmail.com",
                    "contact_number" => "0193810"
                )
            ),
            array(
                //test for plain password not match
                array(
                    "username" => "adminone",
                    "plainPassword" => array(
                        "first" => "123456",
                        "second" => "654321"
                    ),
                    "email" => "admin@admin.ad",
                    "contact_number" => "718193018"
                )
            ),
            array(
                //test for invalid email
                array(
                    "username" => "admintwo",
                    "plainPassword" => array(
                        "first" => "123456",
                        "second" => "654321"
                    ),
                    "email" => "thisemailisnotvalid",
                    "contact_number" => "718193018"
                )
            ),
            array(
                //test for required fields
                array(
                    "username" => "",
                    "plainPassword" => array(
                        "first" => "123456",
                        "second" => ""
                    ),
                    "email" => "",
                    "contact_number" => ""
                )
            )
        );
    }

    /**
     * Testing log in valid user using username (from data fixture)
     */
    public function testLoginActionValidUserByUsername()
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/user/login');

        $form = $crawler->selectButton('login')->form();

        $form['_username'] = 'superadmin';
        $form['_password'] = '123456';

        $client->submit($form);

        $isAuthenticatedFully = $this->client
                                     ->getContainer()
                                     ->get('security.authorization_checker')
                                     ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
    }

    /**
     * Testing log in valid user using email (from data fixture)
     */
    public function testLoginActionValidUserByEmail()
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/user/login');

        $form = $crawler->selectButton('login')->form();

        $form['_username'] = 'super.admin@admin.ad';
        $form['_password'] = '123456';

        $client->submit($form);

        $isAuthenticatedFully = $this->client
                                     ->getContainer()
                                     ->get('security.authorization_checker')
                                     ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
    }

    /**
     * Testing log in invalid user by username(not in data fixture)
     */
    public function testLoginActionInvalidUserByUsername()
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/user/login');
        $form = $crawler->selectButton('login')->form();

        $form['_username'] = 'superuser';
        $form['_password'] = '123456';

        $client->submit($form);
        $client->followRedirect(true);

        $isAuthenticatedFully = $this->client
                                     ->getContainer()
                                     ->get('security.authorization_checker')
                                     ->isGranted('ROLE_AUTHENTICATED');

        $this->assertFalse($isAuthenticatedFully);
    }

    /**
     * Testing log in invalid user by email(not in data fixture)
     */
    public function testLoginActionInvalidUserByEmail()
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/user/login');
        $form = $crawler->selectButton('login')->form();

        $form['_username'] = 'superuser@admin.ca';
        $form['_password'] = '123456';

        $client->submit($form);
        $client->followRedirect(true);

        $isAuthenticatedFully = $this->client
                                     ->getContainer()
                                     ->get('security.authorization_checker')
                                     ->isGranted('ROLE_AUTHENTICATED');

        $this->assertFalse($isAuthenticatedFully);
    }

    /**
     * Testing log in required fields
     */
    public function testLoginActionInvalidFields()
    {
        $client = $this->client;
        $crawler = $client->request('GET', '/user/login');
        $form = $crawler->selectButton('login')->form();

        $form['_username'] = 'superuser';
        $form['_password'] = '123456';

        $client->submit($form);
        $client->followRedirect(true);

        $isAuthenticatedFully = $this->client
                                     ->getContainer()
                                     ->get('security.authorization_checker')
                                     ->isGranted('ROLE_AUTHENTICATED');

        $this->assertFalse($isAuthenticatedFully);
    }

    /**
     * Test if account action is for authenticated users only
     */
    public function testAccountActionSuccess()
    {
        $client = $this->createAuthenticatedClient("superadmin", "123456");

        $client->request('GET', '/user/account');

        $isAuthenticatedFully = $client->getContainer()
                                       ->get('security.authorization_checker')
                                       ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
    }

    /**
     * Test if account action is not for authenticated users
     */
    public function testAccountActionFail()
    {
        $client = $this->client;

        $client->request('GET', '/user/account');
        $client->followRedirect(true);

        $isAuthenticatedFully = $client->getContainer()
                                       ->get('security.authorization_checker')
                                       ->isGranted('ROLE_AUTHENTICATED');

        $this->assertFalse($isAuthenticatedFully);
    }

    /**
     * Test if the requesting user can generate a verification token within this link
     */
    public function testSendEmailVerificationActionEmailNotVerified()
    {
        $client = $this->createAuthenticatedClient('superadmin', '123456');;
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');

        $user = $entityManager->getRepository('Yilinker:User')->findOneBy(array("username" => "superadmin"));
        $user->setIsEmailVerified(false);
        $user->setVerificationToken(null);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->request('GET', '/user/send-verification');
        $client->followRedirect(true);

        $isAuthenticatedFully = $client->getContainer()
                                       ->get('security.authorization_checker')
                                       ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
        $this->assertFalse($user->getIsEmailVerified());

        /**
         * Main assertion: check that the verification token was generated
         */
        $this->assertNotNull($user->getVerificationToken());
    }

    /**
     * Test if the requesting user can generate a verification token within this link
     */
    public function testSendEmailVerificationActionEmailAlreadyVerified()
    {
        $client = $this->createAuthenticatedClient('superadmin', '123456');;
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');

        $user = $entityManager->getRepository('Yilinker:User')->findOneBy(array("username" => "superadmin"));
        $user->setIsEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->request('GET', '/user/send-verification');
        $client->followRedirect(true);

        $isAuthenticatedFully = $client->getContainer()
                                       ->get('security.authorization_checker')
                                       ->isGranted('ROLE_AUTHENTICATED');

        $this->assertTrue($isAuthenticatedFully);
        $this->assertTrue($user->getIsEmailVerified());
        
        /**
         * Main assertion: check that the verification token failed to be generated
         */
        $this->assertNull($user->getVerificationToken());
    }

    /**
     * Test if token is valid
     */
    public function testConfirmEmailActionSuccess()
    {
        $this->createAuthenticatedUser('superadmin');
        $client = $this->client;
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository('Yilinker:User')->findOneBy(array("username" => "superadmin"));
        $user->setIsEmailVerified(false);

        $verificationService = new Verification($entityManager);
        $verificationService->createVerificationToken($user);

        $token = $user->getVerificationToken();

        $client->request('GET', '/user/confirm-email?tk='.$token);

        $this->assertTrue($user->getIsEmailVerified());
    }

    /**
     * Test if token is not valid
     */
    public function testConfirmEmailActionFail()
    {
        $this->createAuthenticatedUser('superadmin');
        $client = $this->client;
        $container = $client->getContainer();

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository('Yilinker:User')
                              ->findOneBy(array("username" => "superadmin"));

        $user->setIsEmailVerified(false);

        $verificationService = new Verification($entityManager);
        $verificationService->createVerificationToken($user);

        $token = sha1(uniqid());

        $client->request('GET', '/user/confirm-email?tk='.$token);

        $this->assertFalse($user->getIsEmailVerified());
    }
}
