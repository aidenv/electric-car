<?php

namespace Yilinker\Bundle\BackendBundle\Tests\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PermissionControllerTest extends WebTestCase
{
    public function testPermissionSuccessHomeAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign In')->form();

        $form['_username'] = 'admin';
        $form['_password'] = 'password';

        $crawler = $client->submit($form);

        $crawler = $client->followRedirect();

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );
    }

    public function testPermissionFailIndexHomeAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign In')->form();

        $form['_username'] = 'user';
        $form['_password'] = 'password';

        $crawler = $client->submit($form);

        $crawler = $client->request('GET', '/inhouse-products');

        $this->assertEquals(
            Response::HTTP_FORBIDDEN,
            $client->getResponse()->getStatusCode()
        );
    }
}
