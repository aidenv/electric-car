<?php

namespace Yilinker\Bundle\FrontendBundle\Tests\Controller\Api\v2;

use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Tests\YilinkerCoreWebTestCase;
use Yilinker\Bundle\CoreBundle\Traits\ContainerHandler;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Traits\AccessTokenGenerator;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;

class MobileFeedBackApiControllerTest extends YilinkerCoreWebTestCase
{
        
    public function testAdd()
    {
        $client = static::makeClient();
        
        $crawler = $client->request('POST', $client->getContainer()->get('router')->generate('api_core_mobile_feedback_add', array(
            'title' => 'i need help',
            'description' => 'i cant access the homepage after few clicks',
            'phoneModel' => 'phone model',
            'osVersion' => '2',
            'osName' => 'Android',
        )));
        
        $this->assertStatusCode(200, $client);
        
    }

    public function testAutthenticatedAdd()
    {
        $this->accessToken('buyer');

        $client = static::makeClient();
        
        $crawler = $client->request('POST', $client->getContainer()->get('router')->generate('api_core_mobile_feedback_auth_add', array(
            'access_token' => $this->token['access_token'],
            'title' => 'i need help',
            'description' => 'i cant access the homepage after few clicks',
            'phoneModel' => 'phone model',
            'osVersion' => '2',
            'osName' => 'Android',
        )));
        
        $this->assertStatusCode(200, $client);
        
    }


    public function testValidatedFields()
    {
        $client = static::makeClient();
        
        $crawler = $client->request('POST', $client->getContainer()->get('router')->generate('api_core_mobile_feedback_add', array(
            'title' => '',
            'description' => '',
            'phoneModel' => '',
            'osVersion' => '',
            'osName' => '',
        )));

        $content = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Title is required', $content->data->error[0]);
        $this->assertEquals('Description is required', $content->data->error[1]);
        $this->assertEquals('PhoneModel is required', $content->data->error[2]);
        $this->assertEquals('Os Version is required', $content->data->error[3]);
        $this->assertEquals('Os Name is required', $content->data->error[4]);
    }




}