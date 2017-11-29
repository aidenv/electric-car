<?php

namespace Yilinker\Bundle\FrontendBundle\Tests\Controller\Api;

use Yilinker\Bundle\FrontendBundle\Tests\Controller\Api\ApiTestCase;

/**
 * Functional test for OAuth + Route Authorization
 */
class ApiAuthTest extends ApiTestCase
{
    /**
     * Test token generation
     *
     * @dataProvider tokenGeneratorDataProvider
     * @param mixed $requestData
     * @param boolean $isSuccessful
     */
    public function testTokenGeneration($requestData, $isSuccessful)
    {
        $container = $this->client->getContainer();

        $clientId = null;
        $clientSecret = null;
        if($requestData['isValidClient']){
            $em = $container->get('doctrine.orm.entity_manager');
            $credentials = $em->getRepository('YilinkerCoreBundle:Oauthclient')
                              ->find(1);
            $clientId = $credentials->getPublicId();
            $clientSecret = $credentials->getSecret();
        }
        
        $queryString = http_build_query(
            array(
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'password',
                'username' => $requestData['username'],
                'password' => $requestData['password'],
            )
        );
        // Retrieve valid access token
        $this->client->request('GET', '/oauth/v2/token?'.$queryString);
        $dataResponse = json_decode($this->client->getResponse()->getContent(), true);

        if($isSuccessful){
            $this->assertArrayHasKey('access_token', $dataResponse);
        }
        else{
            $this->assertArrayHasKey('error', $dataResponse);
        }
    }
    
    /**
     * Test resource request with access token
     *
     * @dataProvider accessTokenDataProvider
     * @param boolean $isSuccessful
     */
    public function testAccessToken($isSuccessful)
    {
        $accessToken = $isSuccessful ? $this->getAccessToken() : 'invalidtoken';

        $client = $this->client;
        $client->request(
            'POST', 
            '/api/product/create', 
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/x-www-form-urlencoded'),
            'access_token='.$accessToken
        );

        if($isSuccessful){
            $this->assertEquals(
                \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                $client->getResponse()->getStatusCode()
            );
        }
        else{
            $this->assertNotEquals(
                \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                $client->getResponse()->getStatusCode()
            );
        }
    }

        /**
     * Data provider for testTokenGeneration
     *
     * @return array
     */
    public function tokenGeneratorDataProvider()
    {
        return array(
            // Valid token generation
            array(
                'requestData' => array(
                    'username' => 'super.admin@admin.ad',
                    'password' => '123456',
                    'isValidClient' => true,
                ),
                'isSuccessful' => true,
            ),
            // Invalid user credentials
            array(
                'requestData' => array(
                    'username' => 'super.admin@admin.ad',
                    'password' => 'wrongpassword',
                    'isValidClient' => true,
                ),
                'isSuccessful' => false,
            ),
            // Invalid Oauth client credentials
            array(
                'requestData' => array(
                    'username' => 'super.admin@admin.ad',
                    'password' => '123456',
                    'isValidClient' => false,
                ),
                'isSuccessful' => false,
            ),
        );
    }


    /**
     * Data provider for testAccessToken
     *
     * @return array
     */
    public function accessTokenDataProvider()
    {
        return array(
            // Valid access token
            array(
                'isSuccessful' => true,
            ),
            // Invalid access token
            array(
                'isSuccessful' => false,
            ),
        );
    }

}
