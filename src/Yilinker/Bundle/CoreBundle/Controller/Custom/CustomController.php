<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Custom;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class CustomController extends Controller
{
    protected $jsonResponse = array(
        'isSuccessful'  => false,
        'data'          => array(),
        'message'       => ''
    );

    protected $redirectURL = '/';

    public function jsonResponse()
    {
        return new JsonResponse($this->jsonResponse, $this->jsonResponse? 200 : 400);
    }

    public function getRefererParams() {
        $request = $this->getRequest();
        $referer = $request->headers->get('referer');
        $baseUrl = $request->getBaseUrl();
        $lastPath = substr($referer, strpos($referer, $baseUrl) + strlen($baseUrl));

        return $this->get('router')->getMatcher()->match($lastPath);
    }

    public function redirectBack() {
        $url = $this->redirectURL;
        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $url = $_SERVER['HTTP_REFERER'];
        }

        return $this->redirect($url);
    }

    public function throwNotFoundIf($test, $message = 'Not Found')
    {
        if ($test) {
            throw $this->createNotFoundException($message);
        }
    }

    public function throwNotFoundUnless($test, $message = 'Not Found')
    {
        if (!$test) {
            throw $this->createNotFoundException($message);
        }
    }

}