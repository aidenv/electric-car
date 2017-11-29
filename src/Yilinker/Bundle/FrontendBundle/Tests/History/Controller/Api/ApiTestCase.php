<?php

namespace Yilinker\Bundle\FrontendBundle\Tests\Controller\Api;

use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;

/**
 * Base Class for API Related Test Cases
 */
class ApiTestCase extends YilinkerWebTestCase
{
    /**
     * Holds current valid access token
     * @var string
     */
    protected $accessToken;
    /**
     * Sets up OAuth automatically
     */
    public function setUp($email = 'super.admin@admin.ad', $password = '123456')
    {
        parent::setUp();
        // Retrieve Client Credentials
        $container = $this->client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $credentials = $em->getRepository('YilinkerCoreBundle:OauthClient')
                          ->find(1);

        $queryString = http_build_query(
            array(
                'client_id' => $credentials->getPublicId(),
                'client_secret' => $credentials->getSecret(),
                'grant_type' => 'password',
                'username' => $email,
                'password' => $password
            )
        );
        // Retrieve valid access token
        $this->client->request('GET', '/oauth/v2/token?'.$queryString);
        $dataResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->accessToken = $dataResponse['access_token'];
    }

    /**
     * Returns valid access token
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
