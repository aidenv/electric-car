<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;

class ProductApiController extends Controller
{
    /**
     * Get Product Details
     *
     * @param Request|Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function detailAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $productId = $request->get('productId', 0);

        $user = $this->getUser();
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $product = $tbProduct->findOneBy(array(
            'productId' => $productId,
            'user'      => $user,
            'status'    => Product::ACTIVE,
        ));
        $product = $product ? $product->getDetails(false) : array();
        $product = $this->preProcessProductDetails($product);

        $data = array(
            'isSuccessful' => $product ? true : false,
            'data'         => $product,
            'message'      => $product ? '' : 'Product does not exist.'
        );

        return new JsonResponse($data);
    }

    /**
     * Get Product search keywords
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getProductSearchKeywordAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $queryString = $request->get('queryString', null);       
        $keywords = $em->getRepository('YilinkerCoreBundle:SearchKeyword')
                       ->findByQueryString($queryString);
        $keywordData = array();
        $router = $this->get('router');
        foreach($keywords as $keyword){
            $queryString = $keyword->getKeyword();
            $keywordData[] = array(
                'keyword'     => $keyword->getKeyword(),
                'searchUrl'   => $router->generate('api_product_list', array('query' => $queryString)),
                'webSearch'   => $router->generate('search_product', array('query' => $queryString)),
            );
        }

        $data = array(
            'isSuccessful' => count($keywords) > 0 ? true : false,
            'data'         => $keywordData,
            'message'      => count($keywords) > 0 ? '' : 'No result found.'
        );

        $response = new JsonResponse($data);
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }


    private function preProcessProductDetails($product)
    {
        if (!$product) {
            return $product;
        }
        $assetHelper = $this->get('templating.helper.assets');
        $product['image'] = $assetHelper->getUrl($product['image'], 'product');

        foreach ($product['images'] as &$image) {
            $image['imageLocation'] = $assetHelper->getUrl($image['imageLocation'], 'product');
        }

        return $product;
    }
}
