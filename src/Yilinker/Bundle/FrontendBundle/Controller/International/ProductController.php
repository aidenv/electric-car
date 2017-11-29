<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\International;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController as Controller;

class ProductController extends Controller
{
    public function listAction(Request $request)
    {
        $productSearchService = $this->get('yilinker_core.service.search.product');
        $em = $this->getDoctrine();

        $country = $this->getAppCountry();
        $filterCountry = $request->get('country', '');
        $selectedCountry = $em->getRepository('YilinkerCoreBundle:Country')->findOneByCode($filterCountry);
        $query = trim($request->get('query', ''));

        $productSearchResult = $productSearchService
            ->build($request)
            ->filterOverseasProduct(strtolower($country->getCode()), $filterCountry)
            ->search();

        $parameters = $request->query->all();
        unset($parameters['page']);

        if ($selectedCountry) {
            $noResultMessage = 'No products available in '.$selectedCountry->getName();
        }
        else if (strlen($query) > 0) {
            $noResultMessage = 'No search result found for "'.$query.'"';
        }
        else {
            $noResultMessage = 'No products available overseas';
        }

        return $this->render('YilinkerFrontendBundle:Search:search_page_by_product.html.twig', array(
            'products' => $productSearchResult['products'],
            'excludeSeller' => true,
            'includeCountry' => true,
            'noResultMessage' => $noResultMessage,
            'aggregations' => $productSearchResult['aggregations'],
            'totalProductResultCount' => $productSearchResult['totalResultCount'],
            'totalPages' => $productSearchResult['totalPage'],
            'page' => $request->get('page', 1),
            'query' => $query,
            'parameters' => $parameters,
            'appCountry' => $country,
        ));
    }
}
