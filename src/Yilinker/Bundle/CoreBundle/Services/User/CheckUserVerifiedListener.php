<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Yilinker\Bundle\CoreBundle\Controller\Custom\UserVerifiedController;
use Yilinker\Bundle\CoreBundle\Entity\User;

class CheckUserVerifiedListener
{
    private $context;

    public function __construct($context)
    {
        $this->context = $context;
    }

    public function beforeController(FilterControllerEvent $event)
    {
        $callableController = $event->getController();
        if (!is_array($callableController)) {
            return;
        }

        $controller = $callableController[0];
        $action = $callableController[1];
        if ($controller instanceof UserVerifiedController) {
            $user = $this->context->getToken()->getUser();
            $allowedActions = $controller->allowUnverifiedActions();
            $isAllowed = is_array($allowedActions) && in_array($action, $allowedActions);
            if ($user instanceof User && !$user->isVerified() && !$isAllowed) {
                $callableController[1] = 'unverifiedAction';
                $event->setController($callableController);
            }
        }
    }
}