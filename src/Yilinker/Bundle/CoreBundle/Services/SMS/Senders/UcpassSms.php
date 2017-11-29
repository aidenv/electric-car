<?php

namespace Yilinker\Bundle\CoreBundle\Services\SMS\Senders;

use Yilinker\Bundle\CoreBundle\Services\SMS\Senders\SmsInterface;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;

class UcpassSms implements SmsInterface
{           
    const SoftVersion = "2014-06-30";   
    const BaseUrl = "https://api.ucpaas.com/";  //API请求地址   
    private $accountSid;                        //账号ID。由32个英文字母和阿拉伯数字组成的开发者账号唯一标识符。
    private $token;                             //账号TOKEN 
    private $timestamp;                         //时间戳
    private $appId;     
    private $apiConfiguration;                  //API Configuration file
    private $mobileNumber;                      //mobileNumber  
    private $message;                           //SMS Message

    public function  __construct()  
    {    
        $this->message = "";
        $this->timestamp  = date("YmdHis") + 7200;
    }
        
    public function setConfig($config)
    {       
        $this->apiConfiguration = $config;
    }
    
    public function getProviderValue()
    {
        return OneTimePassword::PROVIDER_UCPASS;
    }
        
    /**
     * Set the mobile number
     *
     * @param string $mobileNumber
     * @return $this
     * @throws \Exception
     */
    public function setMobileNumber($mobileNumber, $country)
    { 
        if($mobileNumber[0] == '0'){
            $mobileNumber = substr($mobileNumber, 1, strlen($mobileNumber));
        }
             
        if(!preg_match('/^1[34578]\d{9}$/', $mobileNumber)){
            throw new YilinkerException("This service only supports 11 digit mobile numbers starting with 1");
        }       
        $this->mobileNumber = $mobileNumber;    
        return $this;
    }
    
    
    /**
     *  Set SMS message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }
        
    
    /**
     * @return string
     * 包头验证信息,使用Base64编码（账户Id:时间戳）
     */
    private function getAuthorization()
    {
        $data = $this->apiConfiguration['accountSid'] . ":" . $this->timestamp;
        return trim(base64_encode($data));
    }
    
    /**
     * @return string
     * 验证参数,URL后必须带有sig参数，sig= MD5（账户Id + 账户授权令牌 + 时间戳，共32位）(注:转成大写)
     */
    private function getSigParameter()
    {
        $sig = $this->apiConfiguration['accountSid'] . $this->apiConfiguration['token'] . $this->timestamp;
        return strtoupper(md5($sig));
    }
    
    /**
     * @param $url
     * @param string $type
     * @return mixed|string
     */
    private function getResult($url, $body = null, $type = 'json',$method)
    {
        $data = $this->connection($url,$body,$type,$method);
        if (isset($data) && !empty($data)) {
            $result = $data;
        } else {
            $result = '没有返回数据';
        }
        return $result;
    }
    
    
    /**
     * @param $url
     * @param $type
     * @param $body  post数据
     * @param $method post或get
     * @return mixed|string
     */
    private function connection($url, $body, $type,$method)
    {
        if ($type == 'json') {
            $mine = 'application/json'; 
        }
        else {
            $mine = 'application/xml';
        }
        if (function_exists("curl_init")) {
            $header = array(
                'Accept:' . $mine,
                'Content-Type:' . $mine . ';charset=utf-8',
                'Authorization:' . $this->getAuthorization(),
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if($method == 'post'){
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        }
        else {
            $opts = array();
            $opts['http'] = array();
            $headers = array(
                "method" => strtoupper($method),
            );
            $headers[]= 'Accept:'.$mine;
            $headers['header'] = array();
            $headers['header'][] = "Authorization: ".$this->getAuthorization();
            $headers['header'][]= 'Content-Type:'.$mine.';charset=utf-8';
    
            if(!empty($body)) {
                $headers['header'][]= 'Content-Length:'.strlen($body);
                $headers['content']= $body;
            }
    
            $opts['http'] = $headers;
            $result = file_get_contents($url, false, stream_context_create($opts));
        }
        
        return $result;
    }
    
    
    /**
     * @param $appId
     * @param $to
     * @param $templateId
     * @param null $param
     * @param string $type
     * @return mixed|string
     * @throws Exception
     */
    public function templateSMS($to,$templateId,$param=null,$type = 'json'){
        
        $url = self::BaseUrl . self::SoftVersion . '/Accounts/' . $this->apiConfiguration['accountSid']. '/Messages/templateSMS?sig=' . $this->getSigParameter();
    
        if($type == 'json'){
            $body_json = array('templateSMS'=>array(
                'appId'=>$this->apiConfiguration['appId'],
                'templateId'=>$templateId,
                'to'=>$to,
                'param'=>$param
            ));
          
            $body = json_encode($body_json);
        }
        elseif($type == 'xml'){
            $body_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <templateSMS>
                            <templateId>'.$templateId.'</templateId>
                            <to>'.$to.'</to>
                            <param>'.$param.'</param>
                            <appId>'.$this->apiConfiguration['appId'].'</appId>
                        </templateSMS>';
            $body = trim($body_xml);
        }
        else {
            throw new Exception("只能json或xml，默认为json");
        }
       
        $data = $this->getResult($url, $body, $type,'post');      
        return $data;
    }
    
    //发送短信的功能方法
    public function sendSMS()
    {
        if("" !== $this->message && null !== $this->mobileNumber){
            $messageStr = explode('#',$this->message);
            $templateId = $messageStr[0];
            $param = $messageStr[1];
            $this->templateSMS($this->mobileNumber,$templateId,$param);
        }
    }
    
}

