<?php

namespace Yilinker\Bundle\CoreBundle\Services\Node;

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class SocketIOService
{
    private $client;
    public $silent = true;

    public function connect($url, $options = array())
    {
        try {
            $socket = new Version1X($url, $options);
            $this->client = new Client($socket);
            $this->client->initialize();
        } catch (\Exception $e) {
            if (!$this->silent) {
                throw $e;
            }
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        call_user_func_array(array($this->client, $name), $arguments);
    }
}