<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class ProductConditionController
 * @package Yilinker\Bundle\FrontendBundle\Controller\Api
 */
class ProductConditionApiController extends Controller
{
    public function getProductConditionAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $productConditionRepository = $em->getRepository('YilinkerCoreBundle:ProductCondition');
        $errorMessage = 'No Result Found';
        $productConditionContainer = array();
        $productConditions = $productConditionRepository->findAll();
        $ctr = 0;
        $isSuccessful = false;

        if ($productConditions) {
            $isSuccessful = true;
            $errorMessage = '';

            foreach ($productConditions as $productCondition) {
                $productConditionContainer[$ctr]['productConditionId'] = $productCondition->getProductConditionId();
                $productConditionContainer[$ctr]['name'] = $productCondition->getName();
                $ctr++;
            }

        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'data' => $productConditionContainer,
            'message' => $errorMessage,
        ));
    }

}
