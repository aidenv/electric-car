<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;

class OrderController extends Controller
{
    public function deliveryLogAction(Request $request)
    {
        $order = $request->get('order');
        if (!($order instanceof UserOrder)) {
            $em = $this->getDoctrine()->getManager();
            $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
            $order = $tbUserOrder->find($order);
        }
        $user = $this->getUser();
        if ($user->isSeller()) {
            $packages = $order->getPackagesOfSeller($user);
        }
        else {
            $packages = $order->getPackages();
        }

        $data = compact('order', 'packages');

        return $this->render('YilinkerCoreBundle:Order:delivery_log.html.twig', $data);
    }

    /**
     * Process express schedule pickup postback
     *
     * @param Request $request
     * @return mixed
     */
    public function processExpressSchedulePickupPostbackAction (Request $request)
    {
        $postback = array (
            'isSuccessful' => $request->get('isSuccessful', false),
            'data'         => $request->get('data', array()),
            'message'      => !is_null($request->get('message')) ? $request->get('message') : $request->get('errorMessage')
        );
        $response = $this->get('yilinker_core.logistics.yilinker.express')->processExpressSchedulePickupPostback($postback);
        $logger = $this->get('yilinker_core.express_api_logger');
        $response['isSuccessful'] ? $logger->getLogger()->info(json_encode($response)) : $logger->getLogger()->err(json_encode($response));

        return new JsonResponse($response);
    }

}
