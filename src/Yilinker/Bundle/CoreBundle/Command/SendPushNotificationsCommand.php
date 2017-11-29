<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;
use Yilinker\Bundle\CoreBundle\Services\Device\GooglePushNotification;
use Yilinker\Bundle\CoreBundle\Repository\DeviceNotificationRepository;

use Carbon\Carbon;

class SendPushNotificationsCommand extends ContainerAwareCommand
{
    private $em;
    private $limit;
    private $deviceRepository;
    private $deviceNotificationRepository;
    private $applePushNotification;
    private $googlePushNotification;

    protected function configure()
    {
        $this->setName('yilinker:pushnotifications:send')
             ->setDescription('Sends schedules push notifications to devices.')
             ->addOption(
                 "interval",
                 null,
                 InputOption::VALUE_REQUIRED,
                 "Cron interval"
             )
             ->addOption(
                 "limit",
                 null,
                 InputOption::VALUE_REQUIRED,
                 "limit of iteration"
             )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Sending...</info>");

        $container = $this->getContainer();

        $this->limit = $input->getOption("limit");

        $interval = $input->getOption("interval");
        $this->em = $container->get("doctrine.orm.entity_manager");

        $this->deviceRepository = $this->em
                                       ->getRepository("YilinkerCoreBundle:Device");
        $this->deviceNotificationRepository = $this->em
                                                   ->getRepository("YilinkerCoreBundle:DeviceNotification");

        $this->applePushNotification = $container->get("yilinker_core.service.pushnotification.apple");
        $this->googlePushNotification = $container->get("yilinker_core.service.pushnotification.google");

        $notifications = $this->deviceNotificationRepository->getNotifications(
                            null, 
                            false, 
                            Carbon::now()->subMinutes($interval),
                            Carbon::now(),
                            null,
                            null,
                            null,
                            DeviceNotificationRepository::SORT_DIRECTION_DESC,
                            false,
                            false,
                            true
                        );

        foreach($notifications as $notification){
            switch ($notification->getRecipient()) {
                case DeviceNotification::RECIPIENT_ANDROID:
                    $this->sendGoogleNotifications($notification);
                    break;
                case DeviceNotification::RECIPIENT_IOS:
                    $this->sendAppleNotifications($notification);
                    break;
                default:
                    $this->sendAppleNotifications($notification);
                    $this->sendGoogleNotifications($notification);
                    break;
            }

            $notification->setIsSent(true)
                         ->setDateSent(Carbon::now());
        }

        $this->em->flush();
        $output->writeln("<info>Notifications sent.</info>");
    }

    private function sendGoogleNotifications($notification)
    {
        $offset = 0;
        while($androidDevices = $this->deviceRepository->getNotificationDevices(
                            Device::DEVICE_TYPE_ANDROID,
                            Device::TOKEN_TYPE_REGISTRATION_ID,
                            null,
                            true,
                            true,
                            $this->limit,
                            $offset
        )){

            if(count($androidDevices) > 0){
                $this->googlePushNotification->init($androidDevices, GooglePushNotification::APP_TYPE_BUYER);
                $this->googlePushNotification->send($notification, GooglePushNotification::APP_TYPE_BUYER);
            }
            else{
                break;
            }

            $offset += $this->limit;
        }
    }

    private function sendAppleNotifications($notification)
    {
        $offset = 0;
        while($iosDevices = $this->deviceRepository->getNotificationDevices(
                            Device::DEVICE_TYPE_IOS,
                            Device::TOKEN_TYPE_DEVICE_TOKEN,
                            null,
                            true,
                            true,
                            $this->limit,
                            $offset
        )){
            
            if(count($iosDevices) > 0){
                $this->applePushNotification->connect();
                $this->applePushNotification->sendNotification($notification, $iosDevices);
            }
            else{
                break;
            }

            $offset += $this->limit;
            $this->applePushNotification->close();
        }
    }
}
