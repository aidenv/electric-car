<?php

namespace Yilinker\Bundle\CoreBundle\Services\SMS\Senders;

interface SmsInterface
{
	public function setConfig($config);

	public function getProviderValue();

	public function setMobileNumber($mobileNumber, $country);

	public function setMessage($message);

	public function sendSMS();
}