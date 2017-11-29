<?php

namespace Yilinker\Bundle\CoreBundle\Security\Services;
 
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
 
class DomainRequestMatcher implements RequestMatcherInterface
{
    private $host;

    /**
    * @param string|null          $host
    */
    public function __construct($host = null)
    {
        $this->host = $host;
    }

    public function matches(Request $request)
    {
        return $request->getHost() == $this->host;
    }

}