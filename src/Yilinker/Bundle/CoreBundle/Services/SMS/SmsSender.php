<?php

namespace Yilinker\Bundle\CoreBundle\Services\SMS;

use Doctrine\Common\Collections\ArrayCollection;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class SmsSender
{

    const MOBIWEB_SERVICE = "mobiweb_sms";

    const MOBIWEB_LIMIT = 1;

    const SEMAPHORE_SERVICE = "semaphore_sms";

    const SEMAPHORE_LIMIT = 2;
    
    const UCPASS_SERVICE = "ucpass_sms";
    
    const UCPASS_LIMIT = 3;
    
    const SMS_MODE_SWITCH = "switch";

    const SMS_MODE_BROADCAST = "broadcast";
    
    //加入国内的发送模式
    const SMS_MODE_CN  = 'CN';

    private $container;

    private $session;

    private $em;

    private $provider = null;

    private $mode;

    private $smsMapping = array(
        Country::AREA_CODE_PHILIPPINES => array(
            self::MOBIWEB_SERVICE => self::MOBIWEB_LIMIT,
            self::SEMAPHORE_SERVICE => self::SEMAPHORE_LIMIT
        ),
        Country::AREA_CODE_CHINA => array(
            self::UCPASS_SERVICE => self::UCPASS_LIMIT
        )
    );

    public function setContainer($container)
    {
        $this->container = $container;
        $this->em = $container->get("doctrine.orm.entity_manager");
    }

    public function setSession($session)
    {
        $this->session = $session;
      
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    public function getService($areaCode, $entries)
    {
        switch ($this->mode) {          
            case self::SMS_MODE_SWITCH:             
                if(array_key_exists($areaCode, $this->smsMapping)){
                    $collection = new ArrayCollection($this->smsMapping[$areaCode]);
                    foreach ($collection as $service => $limit) {
                        if($entries < $limit){
                            $this->provider = $service;
                        }
                    }                  
                    if(is_null($this->provider)){
                        $this->provider = $collection->key();
                    }                   
                    return $this->provider;
                }
                break;
            case self::SMS_MODE_BROADCAST:
                if(array_key_exists($areaCode, $this->smsMapping)){
                    $this->provider = array();
                    foreach ($this->smsMapping[$areaCode] as $service => $limit) {
                        array_push($this->provider, $service);
                    }

                    return $this->provider;
                }
                break;
            case self::SMS_MODE_CN: //加入国内模式
                if(array_key_exists($areaCode, $this->smsMapping)){
                    $collection = new ArrayCollection($this->smsMapping[$areaCode]);
                    foreach ($collection as $service => $limit) {
                        if($entries < $limit){
                            $this->provider = $service;
                        }
                    }
                    if(is_null($this->provider)){
                        $this->provider = $collection->key();
                    }                        
                    return $this->provider;
               }
               break;
        }
     
        return null;
    }

    public function sendMessage(
        $message, 
        $contactNumber,
        $country,
        $persistableObject = null, 
        $providerMethod  = null
    ){
        switch($this->mode){
            case self::SMS_MODE_SWITCH: 
                $smsService = $this->container->get("yilinker_core.service.sms.{$this->provider}");   
                $this->sendSms($smsService, $message, $contactNumber, $country);
                $this->persistObject($persistableObject, $providerMethod, $smsService->getProviderValue());
                break;
            case self::SMS_MODE_BROADCAST:
                foreach ($this->provider as $provider) {
                    $smsService = $this->container->get("yilinker_core.service.sms.{$provider}");                  
                    $this->sendSms($smsService, $message, $contactNumber, $country);
                    $this->persistObject(
                        !is_null($persistableObject)? clone $persistableObject : null, 
                        $providerMethod, 
                        $smsService->getProviderValue()
                    );
                }             
                break;
             case self::SMS_MODE_CN://国内模式
                  $smsService = $this->container->get("yilinker_core.service.sms.{$this->provider}"); 
                  $this->sendSms($smsService, $message, $contactNumber, $country);
                  $this->persistObject($persistableObject, $providerMethod, $smsService->getProviderValue());
                  break;
         }
    }

    private function sendSms($smsService, $message, $contactNumber, $country)
    { 
        
        $smsService->setMessage($message);       
        $smsService->setMobileNumber($contactNumber, $country);

        if ($this->canSendSMS()) {
            $smsService->sendSMS();
        }
    }

    private function persistObject($object, $method, $value)
    {
        if(!is_null($object) && method_exists($object, $method)){
            $object->$method($value);
            $this->em->persist($object);
            $this->em->flush();
        }
    }

    /**
     * Check if able to send SMS 
     */
    private function canSendSMS()
    {
        $env = $this->container->getParameter('app_environment');
        
        if ($env == 'dev' || $env == 'staging') {
            
            if ($this->session->has('sendOTP_SMS') && $this->session->get('sendOTP_SMS') == 1) {
                return true;
            } else if ($this->session->get('sendOTP_SMS') == 0) {
                $this->session->remove('sendOTP_SMS');
            }

            return false;
        }

        return true;
    }
}
