<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Yilinker\Bundle\CoreBundle\Traits\PaginationHandler;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Entity\DeviceNotification;
use Yilinker\Bundle\CoreBundle\Repository\DeviceNotificationRepository;

use Carbon\Carbon;

/**
 * Class PushNotificationController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_MARKETING')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class PushNotificationController extends Controller
{
    const NOTIFICATION_PER_PAGE = 30;

    use PaginationHandler;
    use FormHandler;

    public function indexAction (Request $request)
    {
        $keyword = $request->get("keyword", null);
        $recipient = $request->get("recipient", null);
        $isSent = $request->get("isSent", null);
        $dateFrom = $request->get("dateFrom", null);
        $dateTo = $request->get("dateTo", null);
        $page = $request->get("page", 1);
        $targetType = $request->get("targetType", null);

        if($recipient && count($recipient) == 2){
            $recipient = array(
                DeviceNotification::RECIPIENT_ALL,
                DeviceNotification::RECIPIENT_ANDROID,
                DeviceNotification::RECIPIENT_IOS,
            );
        }
        elseif(is_array($recipient)){
            $recipient = (int)array_shift($recipient);
        }

        $dateFrom = $dateFrom? Carbon::createFromFormat("m/d/Y (H:i:s)", $dateFrom) : null;
        $dateTo = $dateTo? Carbon::createFromFormat("m/d/Y (H:i:s)", $dateTo) : null;

        $perPage = self::NOTIFICATION_PER_PAGE;
        $offset = $this->getOffset($perPage, $page);

        $em = $this->get("doctrine.orm.entity_manager");
        $pushNotificationManager = $this->get("yilinker_core.service.pushnotification.manager");

        $deviceNotificationRepository = $em->getRepository("YilinkerCoreBundle:DeviceNotification");
        $targetTypes = $pushNotificationManager->getTargetTypes();

        $notifications = $deviceNotificationRepository->getNotifications(
                        $recipient,
                        $isSent,
                        $dateFrom,
                        $dateTo,
                        $perPage,
                        $offset,
                        "dn.dateScheduled",
                        DeviceNotificationRepository::SORT_DIRECTION_DESC,
                        true,
                        false,
                        null,
                        $keyword,
                        $targetType
                    );

        $frontendHostName = $this->getParameter("frontend_hostname");
        $productSearchUrl = $this->generateUrl("frontend_search_product_route");
        $storeSearchUrl = $this->generateUrl("frontend_search_seller_route");
        $productUrl = $this->generateUrl("frontend_product_route");
        $storeUrl = $this->generateUrl("frontend_seller_route");

        $dateFrom = $request->get("dateFrom", null);
        $dateTo = $request->get("dateTo", null);
        $recipient = is_array($recipient)? DeviceNotification::RECIPIENT_ALL : $recipient;

        return $this->render(
            "YilinkerBackendBundle:PushNotification:notification.html.twig", 
            compact(
                "notifications", 
                "perPage", 
                "targetTypes", 
                "frontendHostName",
                "keyword",
                "dateFrom",
                "dateTo",
                "recipient",
                "targetType"
            )
        );
    }

    public function createAction(Request $request)
    {
        $pushNotificationManager = $this->get("yilinker_core.service.pushnotification.manager");

        $postData = array(
            "title"             => $request->get("title", null),
            "message"           => $request->get("message", null),
            "recipient"         => $request->get("recipient", null),
            "targetType"        => $request->get("targetType", null),
            "target"            => $request->get("target", null),
            "dateScheduled"     => $request->get("dateScheduled", null),
            "isActive"          => filter_var($request->get("isActive", null), FILTER_VALIDATE_BOOLEAN),
        );

        $form = $this->transactForm(
                    "admin_create_notification", 
                    null, 
                    $postData, 
                    array(
                        "csrf_protection" => false,
                        "target"          => $request->get("target", null),
                        "targetType"      => $request->get("targetType", null),
                    )
        );

        if($form->isValid()){

            $data = $form->getData();
            $deviceNotification = $pushNotificationManager->create($data);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Notification successfully added.",
                "data" => $deviceNotification->__toArray()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, true)
            )
        ), 400);
    }


    public function updateAction(Request $request)
    {
        $pushNotificationManager = $this->get("yilinker_core.service.pushnotification.manager");

        $postData = array(
            "deviceNotification"=> $request->get("deviceNotificationId", null),
            "title"             => $request->get("title", null),
            "message"           => $request->get("message", null),
            "recipient"         => $request->get("recipient", null),
            "targetType"        => $request->get("targetType", null),
            "target"            => $request->get("target", null),
            "dateScheduled"     => $request->get("dateScheduled", null),
            "isActive"          => filter_var($request->get("isActive", null), FILTER_VALIDATE_BOOLEAN),
        );

        $form = $this->transactForm(
                    "admin_update_notification", 
                    null, 
                    $postData, 
                    array(
                        "csrf_protection"       => false,
                        "target"                => $request->get("target", null),
                        "targetType"            => $request->get("targetType", null),
                        "deviceNotificationId"  => $request->get("deviceNotificationId", null)
                    )
        );

        if($form->isValid()){

            $data = $form->getData();
            $deviceNotification = $pushNotificationManager->update($data);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Notification successfully added.",
                "data" => $deviceNotification->__toArray()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, true)
            )
        ), 400);
    }
}
