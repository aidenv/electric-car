<?php

namespace Yilinker\Bundle\FrontendBundle\Services\Checkout;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Yilinker\Bundle\FrontendBundle\Controller\HomeController;
use Yilinker\Bundle\FrontendBundle\Controller\CheckoutController;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialMediaRedirectListener
{
    public function onKernelController(FilterControllerEvent $event)
    {
        $callableController = $event->getController();
        if (!is_array($callableController)) {
            return;
        }
        $controller = $callableController[0];

        $yilinkerController = strpos(get_class($controller), 'Yilinker') > -1;
        if (method_exists($controller, 'getRequest') && $yilinkerController) {
            $request = $controller->getRequest();
            $user = $controller->getUser();
            $route = $request->get('_route');
            $checkoutPageLastAccessed = $request->getSession()->get('checkout_page_last_accessed');

            if (!($controller instanceof HomeController)) {
                $request->getSession()->set('checkout_page_last_accessed', false);
            }
            $checkout = $controller instanceof CheckoutController;
            if ($checkout) {
                if (!$user) {
                    $request->getSession()->set('checkout_page_last_accessed', true);
                }
            }
            if ($user && $route == 'home_page' && $checkoutPageLastAccessed) {
                $url = $controller->generateUrl('checkout_type');
                $event->setController(function() use ($url) {
                    return new RedirectResponse($url);
                });
            }
        }
    }
}