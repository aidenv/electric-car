<?php

namespace Yilinker\Bundle\CoreBundle\Services\Yilinker;

class Logger
{
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}