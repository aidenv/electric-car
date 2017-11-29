<?php

namespace Yilinker\Bundle\CoreBundle\Services\Device;

use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;

use Exception;
use Buzz\Browser;
use Buzz\Client\MultiCurl;
use Buzz\Message\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class GooglePushNotification
{
    const APP_TYPE_BUYER = 0;
    
    const APP_TYPE_SELLER = 1;

    private $gateway;

    private $buyerAppId;

    private $sellerAppId;

    private $kernelDir;

    private $pushNotificationManager;

    private $browser;

    private $logger = null;

    private $tokens = null;

    public function __construct(
        $gateway, 
        $buyerAppId, 
        $sellerAppId, 
        $kernelDir,
        $pushNotificationManager
    ){
        $this->gateway = $gateway;
        $this->buyerAppId = $buyerAppId;
        $this->sellerAppId = $sellerAppId;
        $this->kernelDir = $kernelDir;
        $this->pushNotificationManager = $pushNotificationManager;
    }

    public function init($tokens, $appType)
    {
        try{

            $this->browser = new Browser(new MultiCurl());
            $this->browser->getClient()->setVerifyPeer(false);

            if(is_null($this->tokens)){
                $this->tokens = array();
                foreach($tokens as $token){
                    if($token->getUser()->getUserType() == $appType){
                        array_push($this->tokens, $token->getToken());
                    }
                }
            }
        }
        catch(YilinkerException $e){
            $this->initLogger();
            $this->logger->addEmergency($e->getMessage());
        }
    }

    public function send($notification, $appType)
    {
        try{

            $apiKey = null;

            if($appType == self::APP_TYPE_BUYER){
                $apiKey = $this->buyerAppId;
            }
            elseif($appType == self::APP_TYPE_SELLER){
                $apiKey = $this->sellerAppId;
            }

            if($apiKey){

                $response = null;

                $headers = array(
                    "Authorization: key=".$apiKey,
                    "Content-Type: application/json",
                );

                $title = $notification->getTitle();
                $body = $notification->getMessage();
                $data = array(
                    "data" => array(
                        "isSuccessful" => true,
                        "responseType" => "NOTIFICATION",
                        "message"      => "Google Cloud Messaging.",
                        "data"         => $this->pushNotificationManager
                                               ->constructNotificationTargets($notification)
                    ),
                    "title" => $title,
                    "body" => $body
                );

                if($this->tokens){
                    $data["registration_ids"] = $this->tokens;
                    $response = $this->browser->post($this->gateway, $headers, json_encode($data));
                    $this->browser->getClient()->flush();

                    if($response && $response instanceof Response){
                        $content = json_decode($response->getContent(), true);

                        if($content && array_key_exists("results", $content)){
                            foreach($content["results"] as $key => $result){
                                if(array_key_exists("error", $result)){
                                    
                                    $this->initLogger();
                                    
                                    $token = $this->tokens[$key];
                                    $error = $result["error"];
                                    
                                    $this->logger->addEmergency("Failed to send notification to {$token} -- {$error} -- {$title} : {$body}");
                                }
                            }
                        }
                    }
                }
            }
        }
        catch(YilinkerException $e){
            $this->initLogger();
            $this->logger->addEmergency($e->getMessage());
        }
        catch(Exception $e){
            $this->initLogger();
            $this->logger->addEmergency($e->getMessage());
        }
    }

    private function initLogger()
    {
        if(is_null($this->logger)){

            $this->logger = new Logger('GCM');

            if(!file_exists($this->kernelDir."/../notifications")){
                mkdir($this->kernelDir."/../notifications", 0777);
            }

            if(!file_exists($this->kernelDir."/../notifications/logs")){
                mkdir($this->kernelDir."/../notifications/logs", 0777);
            }

            $this->logger->pushHandler(new StreamHandler($this->kernelDir."/../notifications/logs/android.log", Logger::EMERGENCY));
        }
    }
}
