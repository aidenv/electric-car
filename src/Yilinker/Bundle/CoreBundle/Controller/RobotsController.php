<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RobotsController extends Controller
{
    public function indexAction(Request $request)
    {
        
        $response = new Response();
        $robot = $this->renderView('YilinkerCoreBundle:Robots:robot.html.twig',array('sitemap_hostname' => $this->getParameter('sitemap_hostname')));
        
        $response->headers->set('Content-Type', 'text/plain');
        $response->setContent($robot);
        //$response->send();

        return $response;
    }

}
