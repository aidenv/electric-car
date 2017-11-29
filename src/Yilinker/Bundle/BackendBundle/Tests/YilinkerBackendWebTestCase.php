<?php

namespace Yilinker\Bundle\BackendBundle\Tests;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class YilinkerBackendWebTestCase extends WebTestCase
{
    protected $client;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures(array(
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Admin\LoadAdminRoleData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Admin\LoadAdminUserData',
        ), null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE);

        $this->client = static::createClient();
    }

    protected function createAuthenticatedClient($username, $password)
    {
        $credentials = array(
            'username' => $username,
            'password' => $password
        );

        $this->client = static::makeClient($credentials);

        return $this->client;
    }

    protected function createAuthenticatedUser($username)
    {
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => 'password',
        ));

        return $this->client;
    }
}
