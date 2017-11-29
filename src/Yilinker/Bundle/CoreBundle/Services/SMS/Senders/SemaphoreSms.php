<?php

namespace Yilinker\Bundle\CoreBundle\Services\SMS\Senders;

use Yilinker\Bundle\CoreBundle\Services\SMS\Senders\SmsInterface;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;

class SemaphoreSms implements SmsInterface
{
    /**
     * API Configuration file
     *
     * @param mixed
     */
    private $apiConfiguration;

    /**
     * Mobile Number Recipient
     *
     * @param string
     */
    private $mobileNumber;

    /**
     * SMS Message
     *
     * @param string
     */
    private $message;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->message = "";
        $this->mobileNumber = null;
    }

    /**
     * Set the API configuration
     *
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->apiConfiguration = $config;
    }

    public function getProviderValue()
    {
        return OneTimePassword::PROVIDER_SEMAPHORE;
    }

    /**
     * Set the mobile number
     *
     * @param string $mobileNumber
     * @return $this
     * @throws \Exception
     */
    public function setMobileNumber($mobileNumber, $country = null)
    {
        if($mobileNumber[0] == '0'){
            $mobileNumber = substr($mobileNumber, 1, strlen($mobileNumber));
        }

        if(0 === preg_match('/^(8|9)[0-9]{9}$/', $mobileNumber)){
            throw new YilinkerException("This service only supports 11 digit mobile numbers starting with 08 & 09");
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
     * Send the SMS
     *
     * @return mixed
     */
    public function sendSMS()
    {
        if("" !== $this->message && null !== $this->mobileNumber){
            $smsParameters = array(
                'api' => $this->apiConfiguration['api_key'],
                'number' => $this->mobileNumber,
                'message' => $this->message,
                'from' => $this->apiConfiguration['from'],
            );

            $outboundEndpoint = $this->apiConfiguration['outbound_endpoint'];
            $smsParamString = http_build_query($smsParameters);
            // allow new lines
            $smsParamString = str_replace('%5Cn', '%0A', $smsParamString);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $outboundEndpoint);
            curl_setopt($ch, CURLOPT_POST, count($smsParameters));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $smsParamString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);

            return $output;
        }

        return false;
    }

    

}
