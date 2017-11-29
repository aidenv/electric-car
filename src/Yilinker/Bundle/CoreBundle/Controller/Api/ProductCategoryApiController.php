<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;

/**
 * Class ProductCategoryApi
 * @package Yilinker\Bundle\FrontendBundle\Controller\Api
 */
class ProductCategoryApiController extends Controller
{

    /**
     * Get children of a category
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCategoryAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $parentId = $request->query->get('parentId', null);
        $categoryQueryString = $request->query->get('queryString', null);

        $productCategoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $productCategories = $productCategoryRepository->searchCategory($parentId, null, null, $categoryQueryString);

        $errorMessage = 'No Result';
        $isSuccessful = false;

        $assetHelper = $this->get('templating.helper.assets');
        foreach ($productCategories as $index => $productCategory) {
            if($productCategory['image'] !== null && strlen(trim($productCategory['image'])) > 0){
                $productCategories[$index]['image'] = $assetHelper->getUrl($productCategory['image'], 'mobile_category');
            }
        }

        if ($productCategories) {
            $isSuccessful = true;
            $errorMessage = '';
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'data' => $productCategories,
            'message' => $errorMessage,
        ));
    }

}
