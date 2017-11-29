<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Yilinker\Bundle\CoreBundle\Traits\FormHandler;

class SmsController extends Controller
{
    use FormHandler;

    public function indexAction()
    {
        return $this->render('YilinkerBackendBundle:Sms:sms_list.html.twig');
    }
}