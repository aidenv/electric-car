<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class LocationApiController extends YilinkerBaseController
{
    /**
     * get All Countries
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *     }
     * )
     */
    public function getAllCountriesAction(Request $request)
    {
        $locationService = $this->get('yilinker_core.service.location.location');

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of countries",
            "data" => $locationService->getAllCountries(true)
        ));
    }


    /**
     * getChildIslands
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="countryId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getChildIslandsAction(Request $request)
    {
        $locationId = $request->request->get("countryId", null);
        $locationService = $this->get('yilinker_core.service.location.location');

        if(is_null($locationId)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed fetching islands",
                "data" => array()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of islands",
            "data" => $locationService->getChildren($locationId, LocationType::LOCATION_TYPE_ISLAND, true)
        ));
    }

    /**
     * getChildRegions
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="islandId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getChildRegionsAction(Request $request)
    {
        $locationId = $request->request->get("islandId", null);
        $locationService = $this->get('yilinker_core.service.location.location');

        if(is_null($locationId)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed fetching regions",
                "data" => array()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of regions",
            "data" => $locationService->getChildren($locationId, LocationType::LOCATION_TYPE_REGION, true)
        ));
    }

    /**
     * getChildProvinces
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="regionId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getChildProvincesAction(Request $request)
    {
        $locationId = $request->request->get("regionId", null);
        $locationService = $this->get('yilinker_core.service.location.location');

        if(is_null($locationId)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed fetching provinces",
                "data" => array()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of provinces",
            "data" => $locationService->getChildren($locationId, LocationType::LOCATION_TYPE_PROVINCE, true)
        ));
    }

    /**
     * getChildCities
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="provinceId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getChildCitiesAction(Request $request)
    {
        $locationId = $request->request->get("provinceId", null);
        $locationService = $this->get('yilinker_core.service.location.location');

        if(is_null($locationId)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed fetching cities",
                "data" => array()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of cities",
            "data" => $locationService->getChildren($locationId, LocationType::LOCATION_TYPE_CITY, true)
        ));
    }

    /**
     * getChildBarangays
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="municipalityId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getChildBarangaysAction(Request $request)
    {
        $locationId = $request->request->get("municipalityId", null);
        $locationService = $this->get('yilinker_core.service.location.location');

        if(is_null($locationId)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed fetching barangays",
                "data" => array()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of barangays",
            "data" => $locationService->getChildren($locationId, LocationType::LOCATION_TYPE_BARANGAY, true)
        ));
    }

    /**
     * getAllProvinces
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     * )
     */
    public function getAllProvincesAction(Request $request)
    {
        $locationService = $this->get('yilinker_core.service.location.location');

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of provinces",
            "data" => $locationService->getAll(LocationType::LOCATION_TYPE_PROVINCE, true)
        ));
    }

     /**
     * getBarangaysByCity
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="cityId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getBarangaysByCityAction(Request $request)
    {
        $locationId = $request->request->get("cityId", null);
        $locationService = $this->get('yilinker_core.service.location.location');

        if(is_null($locationId)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Failed fetching barangays",
                "data" => array()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Collection of barangays",
            "data" => $locationService->getBarangaysByCity($locationId, true)
        ));
    }

    /**
     * getChildren
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Location",
     *     parameters={
     *         {"name"="locationId", "dataType"="string", "required"=false},
     *         {"name"="locationTypeId", "dataType"="string", "required"=false}
     *     }
     * )
     */
    public function getChildrenAction(Request $request)
    {
        $locationId = $request->get('locationId');
        $locationTypeId = $request->get('locationTypeId');
        $locationService = $this->get('yilinker_core.service.location.location');
        $locations = $locationService->getSimplifiedChildren($locationId, $locationTypeId, false, true);

        $view = $request->get('view', null);
        if ($view) {
            
            $em = $this->getDoctrine()->getEntityManager();
            
            $tbLocationType = $em->getRepository('YilinkerCoreBundle:LocationType');
            $locationTypes = $tbLocationType->findBy(array(
                'locationTypeId' => $locationTypeId
            ));

            $labels = array();
            foreach ($locationTypes as $locationType) {
                $labels[] = $locationType->getName();
            }
            
            $label = implode(' or ', $labels);

            return $this->render($view, compact('locations', 'label'));
        }

        return new JsonResponse($locations);
    }

    /**
     * Get List of Countries
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Country"
     * )
     */
    public function getCountryListAction(Request $request)
    {
        $locationService = $this->get('yilinker_core.service.location.location');

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = $locationService->getCountriesV3();
        $this->jsonResponse['message'] = "Country List";

        return $this->jsonResponse();
    }


    /**
     * Get List of Languages
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Country"
     * )
     */
    public function getLanguageListAction(Request $request)
    {
        $languageList = array();
        $em = $this->getDoctrine()->getManager();
        $languages = $em->getRepository('YilinkerCoreBundle:Language')->findAll();

        foreach($languages as $language) {
            $languageList[] = $language->toArray();
        }

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = $languageList;
        $this->jsonResponse['message'] = "Language List";

        return $this->jsonResponse();
    }

}

