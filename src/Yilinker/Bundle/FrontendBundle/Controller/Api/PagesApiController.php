<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use stdClass;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Services\Cms\PagesService;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class PagesApiController extends YilinkerBaseController
{
    /**
     * Generate home page contents (XML to JSON)
     *
     * @param Request $request
     * @return JsonResponse*
     * @ApiDoc(
     *     section="Pages",
     * )
     */
    public function homeAction(Request $request, $version)
    {
        try {

            $translationService = $this->get('yilinker_core.translatable.listener');
            $country = $translationService->getCountry();

            $nocache = $request->get('nocache', 'false') == 'true';
            $key = 'home-page-api-' . $country . '-' . $version;
            $content = $this->getCacheValue($key, true, false);
            $cached = $content;

            if(!$content || $nocache){

                $homeXml = $this->getHomeXML($version);
                $xmlParserService = $this->get('yilinker_core.service.pages.xml_parser');

                $productIds = $xmlParserService->getAllNodeValues($homeXml, 'product');
                $productUnitIds = $xmlParserService->getAllNodeAttributeValues($homeXml, 'product', 'unit');

                $entityManager = $this->get("doctrine.orm.entity_manager");

                $productRepository = $entityManager->getRepository("YilinkerCoreBundle:Product");
                $products = $productRepository->loadProductsIn($productIds);

                $productUnitRepository = $entityManager->getRepository("YilinkerCoreBundle:ProductUnit");
                $productUnits = $productUnitRepository->loadProductUnitsIn($productUnitIds, null);

                $nodeCategoryIds = $xmlParserService->getAllNodeValues($homeXml, "category");
                $attributeCategoryIds = $xmlParserService->getAllNodeAttributeValues($homeXml, "category", "id");
                $categoryIds = array_unique(array_values(array_merge($nodeCategoryIds, $attributeCategoryIds)));
                $productCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory");
                $productCategories = $productCategoryRepository->loadProductCategoriesIn($categoryIds, false);

                $attributeUserIds = $xmlParserService->getAllNodeAttributeValues($homeXml, "user", "id");
                $nodeUserIds = $xmlParserService->getAllNodeValues($homeXml, "user");
                $userRepository = $entityManager->getRepository("YilinkerCoreBundle:User");
                $userIds = array_unique(array_values(array_merge($attributeUserIds, $nodeUserIds)));
                $users = $userRepository->loadUsersIn($userIds);

                $pagesService = $this->get('yilinker_core.service.pages.pages');
                $userSpecialties = $productCategoryRepository->getUserSpecialtyIn($attributeUserIds);

                $content = $pagesService->constructHomepageContent(
                    $homeXml, $products, $productUnits, $productCategories, $userSpecialties, $users, $version
                );

                $this->setCacheValue($key, $content);
            }

            if ($version !== "v1" && $this->getParameter("has_flash_sale")) {
                $pagesService = $this->get('yilinker_core.service.pages.pages');

                $layouts = $cached? $content->data : $content["data"];

                $flashSaleKey = 0;
                foreach($layouts as $index => $layout){
                    if(
                        is_array($layout) &&
                        array_key_exists("layoutId", $layout) &&
                        $layout["layoutId"] == PagesService::HOMEPAGE_MOBILE_V2_CONTENT_FLASH_SALE
                    ){
                        $flashSaleKey = $index;
                    }
                    elseif(
                        is_object($layout) &&
                        $layout->layoutId &&
                        $layout->layoutId == PagesService::HOMEPAGE_MOBILE_V2_CONTENT_FLASH_SALE
                    ){
                        $flashSaleKey = $index;
                    }
                }

                $flashSaleContent = $pagesService->constructHomeFlashSale($this->getHomeXML($version)->layout[$flashSaleKey]);

                if($cached){
                    $content->data[$flashSaleKey]->remainingTime = $flashSaleContent["remainingTime"];

                    if(is_null($flashSaleContent["remainingTime"])){
                        $content->data[$flashSaleKey]->data = array();
                    }
                    else{
                        $content->data[$flashSaleKey]->data = $flashSaleContent["currentPromoProducts"];
                    }
                }
                else{
                    $content["data"][$flashSaleKey]["remainingTime"] = $flashSaleContent["remainingTime"];

                    if(is_null($flashSaleContent["remainingTime"])){
                        $content["data"][$flashSaleKey]["data"] = array();
                    }
                    else{
                        $content["data"][$flashSaleKey]["data"] = $flashSaleContent["currentPromoProducts"];
                    }
                }
            }

            $content = $this->removeOverseasLayout($content,$cached);

            return new JsonResponse($content, 200);
        }
        catch (Exception $e) {
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Content not found",
                "data" => array()
            ), 404);
        }
    }

     /**
     * Overseas Coountries
     *
     * @param Request $request
     * @return JsonResponse*
     * @ApiDoc(
     *     section="HomePage Country",
     * )
     */
    public function overseasCountryAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $appCountryCode = $this->getCountryByApi()->getCode();
        $countries = $entityManager->getRepository('YilinkerCoreBundle:Country')
                                   ->findAllWithExclude($appCountryCode);

        $locationService = $this->get('yilinker_core.service.location.location');

        foreach ($countries as &$country) {
            $country = $locationService->countryDetail($country);
        }

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = $countries;
        $this->jsonResponse['message'] = "Overseas countries";

        return $this->jsonResponse();
    }

    private function getHomeXML($version)
    {
        return $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", $version, "mobile");
    }


    /**
     * Remove Overseas layout section if using v2
     */
    private function removeOverseasLayout($content,$cache)
    {
        $pathInfo = explode('/',$this->container->get('request')->getPathInfo());

        if($cache){
            $column = array_map(function($element) {
              return $element->sectionTitle;
            }, $content->data);

            if ($pathInfo[2] == 'v2') {
                $overseasKey = array_search('Overseas Country', $column);

                if ($overseasKey) {
                    unset($content->data[$overseasKey]);
                }
                $content->data = array_values($content->data);
            }

        }
        else {
            $column = array_map(function($element) {
                if(array_key_exists('sectionTitle', $element)){
                    return $element['sectionTitle'];
                }

                return "";
            }, $content['data']);

            if ($pathInfo[2] == 'v2') {
                $overseasKey = array_search('Overseas Country', $column);

                if ($overseasKey) {
                    unset($content['data'][$overseasKey]);
                }
                $content['data'] = array_values($content['data']);
            }
        }

        return $content;
    }
}
