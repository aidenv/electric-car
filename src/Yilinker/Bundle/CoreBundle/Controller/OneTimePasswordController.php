<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OneTimePasswordController extends Controller
{
    public function indexAction(Request $request)
    {
    	$env = $this->getParameter('app_environment');
    	
    	if ($env === 'prod') {
    		throw new NotFoundHttpException('Sorry not existing!');
    	}
        
        $em = $this->getDoctrine()->getManager();
        $params['page'] = $request->query->get('page', 1);
    	
        $list = $em->getRepository('YilinkerCoreBundle:OneTimePassword')->getOnetimePasswordList($params);

        return $this->render('YilinkerCoreBundle:OneTimePassword:index.html.twig',array('list' => $list ));
    }

    public function sendAction(Request $request)
    {
        $this->get('session')->set('sendOTP_SMS',$request->query->get('send', 0));
        $redirectUrl = $this->generateUrl('home_page');

        return $this->redirect($redirectUrl);
    }
}
