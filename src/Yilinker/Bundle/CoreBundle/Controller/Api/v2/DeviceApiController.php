<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api\v2;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;
use Yilinker\Bundle\CoreBundle\Services\Device\GooglePushNotification;
use Yilinker\Bundle\CoreBundle\Repository\DeviceNotificationRepository;

use Carbon\Carbon;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class DeviceApiController extends Controller
{
    use FormHandler;

    /**
     * For IOS only (used for notifications as of now)
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Device",
     *     statusCodes={
     *         200={
                    "added or overriden",
     *         },
     *         400={
     *             "Field errors or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="deviceToken", "dataType"="string", "required"=true, "description"="Device token from IOS"},
     *     },
     *     views = {"device", "default", "v2"}
     * )
     */
    public function addDeviceTokenAction(Request $request)
    {
        $gcmService = $this->get('yilinker_core.service.device.gcm');

        $deviceToken = $request->get("deviceToken", null);

        $form = $this->transactForm(
                    "api_core_add_device_token_v2", 
                    null, 
                    array("deviceToken" => $deviceToken), 
                    array("csrf_protection" => false)
                );

        if($form->isValid()){

            $data = $form->getData();
            $gcmService->setAuthenticatedUser($this->getUser());
            $gcmService->addToken(
                $data["deviceToken"], 
                Device::DEVICE_TYPE_IOS, 
                Device::TOKEN_TYPE_DEVICE_TOKEN,
                false,
                true
            );

            return new JsonResponse(array(
                "isSuccessful" => true,
                "responseType" => "CREATE_DEVICE_TOKEN",
                "message" => "Device token added",
                "data" => array()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "responseType" => "CREATE_DEVICE_TOKEN",
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, true)
            )
        ), 400);
    }

    public function sendPushNotificationAction(Request $request)
    {
        $em = $this->get("doctrine.orm.entity_manager");
        $deviceRepository = $em->getRepository("YilinkerCoreBundle:Device");
        $deviceNotificationRepository = $em->getRepository("YilinkerCoreBundle:DeviceNotification");

        $applePushNotification = $this->get("yilinker_core.service.pushnotification.apple");
        $googlePushNotification = $this->get("yilinker_core.service.pushnotification.google");

        $notifications = $deviceNotificationRepository->getNotifications(
                            null, 
                            false, 
                            Carbon::now()->subMinutes(30),
                            null,
                            null,
                            null,
                            null,
                            DeviceNotificationRepository::SORT_DIRECTION_DESC,
                            false,
                            false,
                            true
                        );

        $androidDevices = $deviceRepository->getNotificationDevices(
                            Device::DEVICE_TYPE_ANDROID,
                            Device::TOKEN_TYPE_REGISTRATION_ID,
                            null,
                            true,
                            true
                        );

        $iosDevices = $deviceRepository->getNotificationDevices(
                            Device::DEVICE_TYPE_IOS,
                            Device::TOKEN_TYPE_DEVICE_TOKEN,
                            null,
                            true,
                            true
                        );
        
        $applePushNotification->connect();
        $googlePushNotification->init($androidDevices, GooglePushNotification::APP_TYPE_BUYER);

        foreach($notifications as $notification){
            switch ($notification->getRecipient()) {
                case DeviceNotification::RECIPIENT_ANDROID:
                    $googlePushNotification->send($notification, GooglePushNotification::APP_TYPE_BUYER);
                    break;
                case DeviceNotification::RECIPIENT_IOS:
                    $applePushNotification->sendNotification($notification, $iosDevices);
                    break;
                default:
                    $applePushNotification->sendNotification($notification, $iosDevices);
                    $googlePushNotification->send($notification, GooglePushNotification::APP_TYPE_BUYER);
                    break;
            }

            $notification->setIsSent(true)
                         ->setDateSent(Carbon::now());
        }

        $em->flush();
        $applePushNotification->close();

        return new JsonResponse(array(
            "message" => "Notifications sent."
        ), 400);
    }
}
