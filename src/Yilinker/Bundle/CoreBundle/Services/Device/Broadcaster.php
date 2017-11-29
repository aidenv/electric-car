<?php

namespace Yilinker\Bundle\CoreBundle\Services\Device;

use Exception;
use Buzz\Browser;
use Buzz\Client\MultiCurl;
use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class Broadcaster
 * @package Yilinker\Bundle\CoreBundle\Services\Message
 */
class Broadcaster
{
    private $baseUri = "https://android.googleapis.com/gcm/send";

    private $logs;

    private $browser;

    private $responses;

    private $buyerAndroidRegistrationIds = array();

    private $buyerIosRegistrationIds = array();

    private $sellerAndroidRegistrationIds = array();

    private $sellerIosRegistrationIds = array();

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function init($registrationIds)
    {
        $this->browser = new Browser(new MultiCurl());
        $this->browser->getClient()->setVerifyPeer(false);

        foreach ($registrationIds as $registrationId) {
            if(
                $registrationId->getUser()->getUserType() == User::USER_TYPE_BUYER && 
                $registrationId->getDeviceType() == Device::DEVICE_TYPE_ANDROID
            ){
                array_push($this->buyerAndroidRegistrationIds, $registrationId->getToken());
            }
            elseif(
                $registrationId->getUser()->getUserType() == User::USER_TYPE_BUYER && 
                $registrationId->getDeviceType() == Device::DEVICE_TYPE_IOS
            ){
                array_push($this->buyerIosRegistrationIds, $registrationId->getToken());
            }
            elseif(
                $registrationId->getUser()->getUserType() == User::USER_TYPE_SELLER && 
                $registrationId->getDeviceType() == Device::DEVICE_TYPE_ANDROID
            ){
                array_push($this->sellerAndroidRegistrationIds, $registrationId->getToken());
            }
            elseif(
                $registrationId->getUser()->getUserType() == User::USER_TYPE_SELLER && 
                $registrationId->getDeviceType() == Device::DEVICE_TYPE_IOS
            ){
                array_push($this->sellerIosRegistrationIds, $registrationId->getToken());
            }
        }

        return $this;
    }

    public function send($data)
    {
        $this->sendAndroidNotifs("buyer", $data);
        $this->sendAndroidNotifs("seller", $data);        
        $this->sendIosNotifs("buyer", $data);
        $this->sendIosNotifs("seller", $data);

        return $this->logs;
    }

    private function sendAndroidNotifs($userType, $data)
    {
        $apiKey = $this->container->getParameter("gcm_{$userType}_android_appid");

        $headers = array(
            'Authorization: key='.$apiKey,
            'Content-Type: application/json',
        );

        $data = array(
            'data' => $data,
        );

        $androidRegistrationIds = $userType == "buyer"? $this->buyerAndroidRegistrationIds : $this->sellerAndroidRegistrationIds ;
        if(!empty($androidRegistrationIds)){

            $chunks = array_chunk($androidRegistrationIds, 1000);
            foreach ($chunks as $registrationIds) {
                $data['registration_ids'] = $androidRegistrationIds;
                $this->responses["android"] = $this->browser->post($this->baseUri, $headers, json_encode($data));
            }

            $this->browser->getClient()->flush();
        }

        if(!is_null($this->responses) && array_key_exists("android", $this->responses)){
            $result = json_decode($this->responses["android"]->getContent(), true);
            if(array_key_exists(0, $this->responses["android"]->getHeaders())){
                $this->logs["android"]["response"][$userType]["statusCode"] = $this->responses["android"]->getHeaders()[0];
                $this->logs["android"]["response"][$userType]["apiKey"] = $apiKey;
                foreach ($androidRegistrationIds as $index => $registrationId) {
                    if($result){
                        $this->logs["android"]["response"][$userType][$registrationId] = $result["results"][$index];
                    }
                    else{
                        $this->logs["android"]["response"][$userType] = $this->responses["android"]->getContent();
                    }
                }
            }
            else{
                $this->logs["android"]["headers"][$userType] = $this->responses["android"]->getHeaders();
            }
        } 
    }

    private function sendIosNotifs($userType, $data)
    {
        $apiKey = $this->container->getParameter("gcm_{$userType}_ios_appid");

        $headers = array(
            'Authorization: key='.$apiKey,
            'Content-Type: application/json',
        );

        $iosRegistrationIds = $userType == "buyer"? $this->buyerIosRegistrationIds : $this->sellerIosRegistrationIds;
        
        $data = array(
            "data" => $data
        );

        if(array_key_exists("recipientName", $data["data"]) && array_key_exists("isImage", $data["data"])){
            $data["notification"] = array(
                "title" => $data["data"]["recipientName"],
                "body" => $data["data"]["isImage"]? "Sent a photo" : $data["data"]["message"]
            );
        }

        if(!empty($iosRegistrationIds)){

            $chunks = array_chunk($iosRegistrationIds, 1000);
            foreach ($chunks as $registrationIds) {
                $data['registration_ids'] = $iosRegistrationIds;
                $this->responses["ios"] = $this->browser->post($this->baseUri, $headers, json_encode($data));
            }

            $this->browser->getClient()->flush();
        }

        if(!is_null($this->responses) && array_key_exists("ios", $this->responses)){
            $result = json_decode($this->responses["ios"]->getContent(), true);
            if(array_key_exists(0, $this->responses["ios"]->getHeaders())){
                $this->logs["ios"]["response"][$userType]["statusCode"] = $this->responses["ios"]->getHeaders()[0];
                $this->logs["ios"]["response"][$userType]["apiKey"] = $apiKey;
                foreach ($iosRegistrationIds as $index => $registrationId) {
                    if($result){
                        $this->logs["ios"]["response"][$userType][$registrationId] = $result["results"][$index];
                    }
                    else{
                        $this->logs["ios"]["response"][$userType] = $this->responses["ios"]->getContent();
                    }
                }
            }
            else{
                $this->logs["ios"]["headers"][$userType] = $this->responses["ios"]->getHeaders();
            }
        } 
    }
}
