<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

trait SlugHandler
{
    public function getLastSegment($url)
    {
    	$urlSegments = explode("?", $url);
    	$url = $urlSegments[0];

        $url = trim($url, "/");
        $segments = explode("/", $url);

        return $segments[count($segments)-1];
    }

    public function getQueryString($url)
    {
    	$urlSegments = explode("?", $url);

    	return array_key_exists(1, $urlSegments)? $urlSegments[1] : null;
    }
}