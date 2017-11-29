<?php

namespace Yilinker\Bundle\CoreBundle\Services\Device;

use Symfony\Component\Debug\Exception\ContextErrorException;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

class ApplePushNotification
{
    private $gateway;

    private $pemLocation;

    private $pemPassphrase;

    private $kernelDir;

    private $pushNotificationManager;

    private $fp;

    private $logger = null;

    public function __construct(
        $gateway, 
        $pemLocation, 
        $pemPassphrase, 
        $kernelDir,
        $pushNotificationManager
    ){
        $this->gateway = $gateway;
        $this->pemLocation = $pemLocation;
        $this->pemPassphrase = $pemPassphrase;
        $this->kernelDir = $kernelDir;
        $this->pushNotificationManager = $pushNotificationManager;
    }

    public function connect()
    {
        try{

            $ctx = stream_context_create();
            stream_context_set_option($ctx, "ssl", "local_cert", $this->kernelDir."/../../".$this->pemLocation);
            stream_context_set_option($ctx, "ssl", "passphrase", $this->pemPassphrase);

            $this->fp = stream_socket_client(
                            $this->gateway, 
                            $err,
                            $errstr, 
                            60, 
                            STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, 
                            $ctx
                        );

            if (!$this->fp) {
                throw new YilinkerException("Failed to connect: $err $errstr");
            }
        }
        catch(YilinkerException $e){
            $this->initLogger();
            $this->logger->addEmergency($e->getMessage());
        }
        catch(ContextErrorException $e){
            $this->initLogger();
            $this->logger->addEmergency($e->getMessage());
        }
        catch(Exception $e){
            $this->initLogger();
            $this->logger->addEmergency($e->getMessage());
        }
    }

    public function sendNotification($notification, $tokens)
    {
        foreach ($tokens as $token){

            $deviceToken = $token->getToken();
            $deviceNotificationId = $notification->getDeviceNotificationId();

            try{

                $apnRequest = $this->generateApnRequest($notification, $deviceNotificationId, $deviceToken);
                stream_set_blocking($this->fp, 0);
                $result = fwrite($this->fp, $apnRequest, strlen($apnRequest));

                if (!$result){
                    throw new YilinkerException("Message not delivered: {$deviceNotificationId} - {$deviceToken}");
                }
            }
            catch(YilinkerException $e){
                $this->initLogger();
                $this->logger->addEmergency($e->getMessage());
            }
            catch(ContextErrorException $e){
                $this->initLogger();
                $this->logger->addEmergency($e->getMessage());
            }
        }
    }

    private function generateApnRequest($notification, $deviceNotificationId, $deviceToken)
    {
        $aps = array(
            "title" => $notification->getTitle(),
            "alert" => $notification->getMessage(),
            "badge" => 0,
            "sound" => "default",
        );

        $data = $this->pushNotificationManager->constructNotificationTargets($notification);

        $payload = json_encode(array(
            "aps" => array_merge($aps, $data)
        ));

        $inner = 
            //device token
            chr(1)
            .pack("n", 32)
            .pack("H*", $deviceToken)

            //payload
            .chr(2)
            .pack("n", strlen($payload))
            .$payload

            //notification id
            .chr(3)
            .pack("n", 4)
            .pack("N", $deviceNotificationId)

            //notification expiration
            .chr(4)
            .pack("n", 4)
            .pack("N", time() + 86400)

            //priority (10 = send immediately)
            .chr(5)
            .pack("n", 1)
            .chr(10);

        return chr(2).pack("N", strlen($inner)).$inner;
    }

    public function close()
    {
        try{
            fclose($this->fp);
        }
        catch(ContextErrorException $e){
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

            $this->logger->pushHandler(new StreamHandler($this->kernelDir."/../notifications/logs/ios.log", Logger::EMERGENCY));
        }
    }
}
