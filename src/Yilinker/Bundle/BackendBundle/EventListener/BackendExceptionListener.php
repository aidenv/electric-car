<?php

namespace Yilinker\Bundle\BackendBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BackendExceptionListener
{
    private $container;
    
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        $this->container->get('yilinker_core.service.user.mailer')->sendError($exception);

        if(($exception instanceof HttpException)){
            
            if($exception->getStatusCode() === 403){
                /**
                 * Handle unauthorized exception
                 */
                $response = new RedirectResponse("/");
                $event->setResponse($response);
            }
        }
    }
}
