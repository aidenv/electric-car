<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class SellerController extends Controller
{
    public function searchStoreAction(Request $request)
    {
        $em = $this->getDoctrine();
        $query = $request->get('query', '');

        $stores = $this->get('yilinker_core.service.search.store')
                       ->searchStoreWithElastic($query);

        $results = array();
        foreach ($stores['stores'] as $store) {
            $results[] = array(
                'storeName' => $store->getStoreName()
            );
        }

        return new JsonResponse(array(
            "isSuccessful"  => true,
            "message"       => "",
            "data"          => $results
        ));
    }
}
