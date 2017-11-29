<?php
namespace Yilinker\Bundle\FrontendBundle\Tests;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class YilinkerWebTestCase extends WebTestCase
{
    protected $client;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->loadFixtures(array(
            'Yilinker\Bundle\FrontendBundle\DataFixtures\ORM\LoadUserData',
            'Yilinker\Bundle\FrontendBundle\DataFixtures\ORM\LoadOauthData',
        ));

        $this->client = static::createClient();
    }

    /**
     * Create an authenticated client via HTTP_AUTH
     * Use this if you want to go through the application firewall
     */
    protected function createAuthenticatedClient()
    {
        $email = 'super.admin@admin.ad';
        $password = 'password';

        $client =  static::createClient(array(), array(
            'PHP_AUTH_USER' => $email,
            'PHP_AUTH_PW'   => $password,
        ));
        
        return $client;
    }

    /**
     * Creates an authentictaed user and sets the appropriate tokens
     *
     */
    protected function createAuthenticatedUser()
    {
        $email = 'super.admin@admin.ad';
        $container = $this->client->getContainer();
        $doctrine = $container->get('doctrine');
        $user = $doctrine->getRepository('Yilinker:User')
                         ->findOneByEmail($email);

        $firewall = 'default';
        $container->get('security.context')->setToken(
            new UsernamePasswordToken(
                $user,
                null,
                $firewall,
                array('ROLE_USER')
            )
        );
    }
}
