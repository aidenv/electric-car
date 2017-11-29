<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PromoEventApiController extends Controller
{
    public function subscribePromoEventAction(Request $request)
    {
        $promoEventId = $request->get("promoEventId", 0);
        
        $em = $this->getDoctrine()->getManager();
        $promoEvent = $em->getRepository('YilinkerCoreBundle:PromoEvent')
                         ->getActivePromoEvent(
                            $promoEventId, 
                            Carbon::now()->startOfDay(), 
                            Carbon::now()->endOfDay()
                        );   

        if(is_null($promoEvent)){
            return new JsonResponse(array(
                "isSuccessful"  => false,
                "message"       => "Event not available.",
                "data"          => array("errors" => array("Event not available."))
            ), 400);
        }

        $user = $this->get("security.token_storage")->getToken()->getUser();

        $promoEventService = $this->get("yilinker_core.service.promo_event");
        $promoEventService->subscribePromoEvent($promoEvent, $user);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Successfully subscribed.",
            "data" => array()
        ));
    }
}

