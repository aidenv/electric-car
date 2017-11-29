<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RedirectController
 *
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class RedirectController extends Controller
{
    public function redirectAction($path, $segment = '', Request $request)
    {
        $attributes = $request->attributes->get('_route_params');

        $host = isset($attributes['host_redirect']) ? $attributes['host_redirect']
                                                    : $request->getHost();

        return $this->redirect(
            $request->server->get('REQUEST_SCHEME').'://'.$host.$path.$segment,
            Response::HTTP_MOVED_PERMANENTLY
        );
    }
}
