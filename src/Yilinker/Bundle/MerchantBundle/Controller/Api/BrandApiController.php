<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
/**
 * Class BrandApiController
 * @package Yilinker\Bundle\FrontendBundle\Controller\Api
 */
class BrandApiController extends Controller
{

   /**
     * get brand 
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Brands",
     *       parameters={
     *         {"name"="brandKeyword", "dataType"="string", "required"=true, "description"="brandKeyword"},
     *     }
     * )
     */
    public function getBrandAction (Request $request)
    {
        $brandKeyword = $request->query->get('brandKeyword');
        $em = $this->getDoctrine()->getManager();
        $brandRepository = $em->getRepository('YilinkerCoreBundle:Brand');
        $brandEntities = $brandRepository->getBrandByName($brandKeyword);
        $ctr = 0;
        $brandContainer = array();
        $errorMessage = 'No Result';
        $isSuccessful = false;

        if ($brandEntities) {
            $isSuccessful = true;
            $errorMessage = '';

            foreach ($brandEntities as $brandEntity) {
                $brandContainer[$ctr]['name'] = $brandEntity->getName();
                $brandContainer[$ctr]['brandId'] = $brandEntity->getBrandId();
                $ctr++;
            }

        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'data' => $brandContainer,
            'message' => $errorMessage,
        ));
    }

}
