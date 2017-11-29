<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ShippingCategoryController extends Controller
{
    public function searchAction(Request $request)
    {
        $em = $this->getDoctrine();
        $query = $request->get('query', '');

        $shippingCategories = $em->getRepository('YilinkerCoreBundle:ShippingCategory')
                                 ->filterBy(array('name' => $query))
                                 ->page(1)
                                 ->getResult();

        $results = array();
        foreach ($shippingCategories as $value) {
            $results[] = $value->toArray();
        }

        return new JsonResponse(array(
            "isSuccessful"  => true,
            "message"       => "",
            "data"          => $results
        ));
    }
}