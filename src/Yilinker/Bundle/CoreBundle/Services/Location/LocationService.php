<?php
namespace Yilinker\Bundle\CoreBundle\Services\Location;

use Doctrine\ORM\EntityManager;
use \Exception as Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Location;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;

class LocationService
{
    private $locationData = array(
        "country" => null,
        "island" => null,
        "region" => null,
        "province" => null,
        "city" => null,
        "municipality" => null,
        "barangay" => null
    );

    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    private $geoCoder;

    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine.orm.entity_manager');

        if ($container->has('bazinga_geocoder.geocoder')) {
            $this->geoCoder = $container->get('bazinga_geocoder.geocoder');
        }
    }

    public function getLocationData()
    {
        return $this->locationData;
    }

    public function getLocationDataObject()
    {
        return $this->locationDataObject;
    }

    public function getAllCountries($isActive = null)
    {
        $locationRepository = $this->em->getRepository("YilinkerCoreBundle:Location");

        $findBy = array("locationType" => LocationType::LOCATION_TYPE_COUNTRY);

        if(!is_null($isActive)){
            $findBy["isActive"] = $isActive;
        }

        $countries = array();
        $locations = $locationRepository->findBy($findBy);

        foreach($locations as $location){

            array_push($countries, array(
                "countryId" => $location->getLocationId(),
                "location" => $location->getLocation(),
            ));
        }

        return $countries;
    }

    /**
     * @param $locationId
     * @param $locationTypeId
     * @return array
     */
    public function getChildren($locationId, $locationTypeId, $isActive = null)
    {
        $locationRepository = $this->em->getRepository("YilinkerCoreBundle:Location");

        $findBy = array(
            "parent" => $locationId,
            "locationType" => $locationTypeId
        );

        if(!is_null($isActive)){
            $findBy["isActive"] = $isActive;
        }

        $children = array();
        $locations = $locationRepository->findBy($findBy);

        foreach($locations as $location){

            $locationType = $location->getLocationType()->getLocationTypeId();
            $var = null;
            switch($locationType){
                case LocationType::LOCATION_TYPE_BARANGAY:
                    $var = "barangayId";
                    break;
                case LocationType::LOCATION_TYPE_CITY:
                    $var = "cityId";
                    break;
                case LocationType::LOCATION_TYPE_PROVINCE:
                    $var = "provinceId";
                    break;
                case LocationType::LOCATION_TYPE_REGION:
                    $var = "regionId";
                    break;
                case LocationType::LOCATION_TYPE_ISLAND:
                    $var = "islandId";
                    break;
                case LocationType::LOCATION_TYPE_COUNTRY:
                    $var = "countryId";
                    break;
            }

            array_push($children, array(
                $var => $location->getLocationId(),
                "location" => $location->getLocation(),
            ));
        }

        return $children;
    }

    /**
     * @return array with key as locationId and value as text
     */
    public function getSimplifiedChildren($locationId, $locationTypeId, $entity = false, $isActive = null)
    {
        $locationRepository = $this->em->getRepository("YilinkerCoreBundle:Location");

        $findBy = array(
            'locationType' => $locationTypeId
        );

        if(!is_null($isActive)){
            $findBy['isActive'] = $isActive;
        }

        $query = $locationRepository->qb()
                                    ->filterBy($findBy);

        if ($locationId) {
            $query->filterByMultipleParent($locationId);
        }

        $locations = $query->getResult();

        if ($entity) {
            return $locations;
        }

        $children = array();
        foreach ($locations as $location) {
            $children[$location->getLocationId()] = $location->getLocation();
        }

        return $children;
    }

    /**
     * @param $locationTypeId
     * @return array
     */
    public function getAll($locationTypeId, $isActive = null)
    {
        $locationRepository = $this->em->getRepository("YilinkerCoreBundle:Location");

        $children = array();

        $findBy = array("locationType" => $locationTypeId);

        if(!is_null($isActive)){
            $findBy["isActive"] = $isActive;
        }

        $locations = $locationRepository->findBy($findBy);

        foreach($locations as $location){

            $locationType = $location->getLocationType()->getLocationTypeId();
            $var = null;
            switch($locationType){
                case LocationType::LOCATION_TYPE_BARANGAY:
                    $var = "barangayId";
                    break;
                case LocationType::LOCATION_TYPE_CITY:
                    $var = "cityId";
                    break;
                case LocationType::LOCATION_TYPE_PROVINCE:
                    $var = "provinceId";
                    break;
                case LocationType::LOCATION_TYPE_REGION:
                    $var = "regionId";
                    break;
                case LocationType::LOCATION_TYPE_ISLAND:
                    $var = "islandId";
                    break;
                case LocationType::LOCATION_TYPE_COUNTRY:
                    $var = "countryId";
                    break;
            }

            array_push($children, array(
                $var => $location->getLocationId(),
                "location" => $location->getLocation(),
            ));
        }

        return $children;
    }

    public function getBarangaysByCity($locationId, $isActive = null)
    {
        $data = array();
        $locationRepository = $this->em->getRepository("YilinkerCoreBundle:Location");
        $barangays = $locationRepository->loadBarangaysByCity($locationId, $isActive);

        foreach($barangays as $barangay){
            array_push($data, array(
                "barangayId" => $barangay->getLocationId(),
                "location" => $barangay->getLocation(),
            ));
        }

        return $data;
    }

    public function constructLocationHierarchy(Location $location)
    {
        $locationTypeId = $location->getLocationType()->getLocationTypeId();

        $parent = $location->getParent();

        switch($locationTypeId){
            case LocationType::LOCATION_TYPE_BARANGAY:
                $this->locationData["barangay"] = $location->getLocation();
                $this->locationDataObject["barangay"] = $location->toArray();
                break;
            case LocationType::LOCATION_TYPE_CITY:
                $this->locationData["city"] = $location->getLocation();
                $this->locationDataObject["city"] = $location->toArray();
                break;
            case LocationType::LOCATION_TYPE_PROVINCE:
                $this->locationData["province"] = $location->getLocation();
                $this->locationDataObject["province"] = $location->toArray();
                break;
            case LocationType::LOCATION_TYPE_REGION:
                $this->locationData["region"] = $location->getLocation();
                $this->locationDataObject["region"] = $location->toArray();
                break;
            case LocationType::LOCATION_TYPE_ISLAND:
                $this->locationData["island"] = $location->getLocation();
                $this->locationDataObject["island"] = $location->toArray();
                break;
            case LocationType::LOCATION_TYPE_COUNTRY:
                $assetsHelper = $this->container->get('templating.helper.assets');
                $this->locationData["country"] = $location->getLocation();
                $this->locationDataObject["country"] = $location->toArray();
                $this->locationDataObject["flag"] = $assetsHelper->getUrl('/images/country-flag/'.strtolower($location->getCode()).'.png');
                break;
        }

        if($locationTypeId != LocationType::LOCATION_TYPE_COUNTRY){
            $this->constructLocationHierarchy($parent);
        }
    }

    public function getDomainByIp($ipAddress)
    {
        try {
            $locations = $this->geoCoder->geocode($ipAddress);
            $location = $locations->first();

            if ($location
                && $locationCode = $location->getCountryCode()) {
                $country = $this->em->getRepository('YilinkerCoreBundle:Country')
                                    ->findOneByCode($locationCode);

                if ($country) {
                    return $country->getDomain();
                }
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * get Countries
     */
    public function getCountriesV3()
    {
        $countries = $this->em->getRepository('YilinkerCoreBundle:Country')->findAll();
        $countrylist = array();

        foreach($countries as $country) {
            $countrylist[] = $this->countryDetail($country);
        }

        return $countrylist;
    }

    /**
     * country details
     */
    public function countryDetail($country)
    {
        $assetsHelper = $this->container->get('templating.helper.assets');
        $c = $country->toArray();
        $c['flag'] =  $assetsHelper->getUrl('/images/country-flag/'.strtolower($country->getCode()).'.png');
        
        return $c;
    }

    /**
     * get current domain - api implementation
     */
    public function getCountryByApi()
    {
        $countryRepository = $this->em->getRepository('YilinkerCoreBundle:Country');
        $pathInfo = explode('/',$this->container->get('request')->getPathInfo());

        if (isset($pathInfo[3]) && $pathInfo[2] == 'v3' &&  $countryRepository->findByCode($pathInfo[3])) {
            return $countryRepository->findByCode($pathInfo[3])[0];
        }

        return $countryRepository->findFirst();
    }        

}
