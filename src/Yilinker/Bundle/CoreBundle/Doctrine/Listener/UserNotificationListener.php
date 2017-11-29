<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

class UserNotificationListener
{
    private $notificationService;
    private $sendNotificationService;

    public function __construct($notificationService, $sendNotificationService)
    {
        $this->notificationService = $notificationService;
        $this->sendNotificationService = $sendNotificationService;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->sendNotificationService->sendNotification($args, 'INSERT');

        return $this->notificationService->record($args, 'INSERT');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->sendNotificationService->sendNotification($args, 'UPDATE');

        return $this->notificationService->record($args, 'UPDATE');
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->sendNotificationService->sendNotification($args, 'DELETE');
        
        return $this->notificationService->record($args, 'DELETE');
    }
}