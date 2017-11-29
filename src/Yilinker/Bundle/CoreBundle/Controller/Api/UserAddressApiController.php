<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Location;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;

class UserAddressApiController extends Controller
{
    /**
     * Add user address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addNewAddressAction(Request $request)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $authenticatedUser = $this->getAuthenticatedUser();

        $locationId = $request->request->get('locationId', null);

        $entityManager = $this->getDoctrine()->getManager();
        $locationRepository = $entityManager->getRepository("YilinkerCoreBundle:Location");
        $locationTypeRepository = $entityManager->getRepository("YilinkerCoreBundle:LocationType");

        $locationType = $locationTypeRepository->find(LocationType::LOCATION_TYPE_BARANGAY);
        $location = $locationRepository->findOneBy(array(
                        "locationId" => $locationId,
                        "locationType" => $locationType
                    ));

        $request->request->remove('locationId');

        $postData = array(
            "title" => $request->request->get("title", ""),
            "unitNumber" => $request->request->get("unitNumber", ""),
            "buildingName" => $request->request->get("buildingName", ""),
            "streetNumber" => $request->request->get("streetNumber", ""),
            "streetName" => $request->request->get("streetName", ""),
            "subdivision" => $request->request->get("subdivision", ""),
            "zipCode" => $request->request->get("zipCode", ""),
            "streetAddress" => $request->request->get("streetAddress", ""),
            "longitude" => $request->request->get("longitude", ""),
            "latitude" => $request->request->get("latitude", ""),
            "landline" => $request->request->get("landline", "")
        );

        $form = $this->transactForm('core_user_address', new UserAddress(), $postData);

        if($form->isValid() && !is_null($location) && $location){

            $locationDetails = array();
            if(!is_null($location)){
                $locationDetails = $location->getLocalizedLocationTree(true);
            }

            $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
            $userAddress = $userAddressService->addUserAddress($authenticatedUser, $form->getData(), $location);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "User address successfully added.",
                "data" => array(
                    "userAddressId" => $userAddress->getUserAddressId(),
                    "title" => $userAddress->getTitle(),
                    "locationId" => !is_null($location)? $location->getLocationId() : null,
                    "unitNumber" => $userAddress->getUnitNumber(),
                    "buildingName" => $userAddress->getBuildingName(),
                    "streetNumber" => $userAddress->getStreetNumber(),
                    "streetName" => $userAddress->getStreetName(),
                    "subdivision" => $userAddress->getSubdivision(),
                    "zipCode" => $userAddress->getZipCode(),
                    "streetAddress" => $userAddress->getStreetAddress(),
                    "provinceId" => $userAddressService->getLocationId("province", $locationDetails),
                    "province" => $userAddressService->getLocation("province", $locationDetails),
                    "cityId" => $userAddressService->getLocationId("city", $locationDetails),
                    "city" => $userAddressService->getLocation("city", $locationDetails),
                    "barangayId" => $userAddressService->getLocationId("barangay", $locationDetails),
                    "barangay" => $userAddressService->getLocation("barangay", $locationDetails),
                    "longitude" => $userAddress->getLongitude(),
                    "latitude" => $userAddress->getLatitude(),
                    "landline" => $userAddress->getLandline(),
                    "fullLocation" => $userAddress->getAddressString(),
                    "isDefault" => $userAddress->getIsDefault(),
                )
            ), 200);
        }

        $errors = $formErrorService->throwInvalidFields($form);

        if(is_null($location) || !$location){
            array_push($errors, "Province, City & Barangay is required.");
        }

        return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
    }

    public function editUserAddressAction(Request $request)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $authenticatedUser = $this->getAuthenticatedUser();

        $userAddressId = $request->request->get('userAddressId', 0);
        $locationId = $request->request->get('locationId', 0);

        $entityManager = $this->getDoctrine()->getManager();

        $userAddressRepository = $entityManager->getRepository("YilinkerCoreBundle:UserAddress");
        $userAddress = $userAddressRepository->findOneBy(array(
                            "userAddressId" => $userAddressId,
                            "user" => $authenticatedUser
                        ));

        if(is_null($userAddress)){
            return $formErrorService->throwResourceNotFoundResponse("User address not found.");
        }

        $locationRepository = $entityManager->getRepository("YilinkerCoreBundle:Location");
        $locationTypeRepository = $entityManager->getRepository("YilinkerCoreBundle:LocationType");

        $locationType = $locationTypeRepository->find(LocationType::LOCATION_TYPE_BARANGAY);
        $location = $locationRepository->findOneBy(array(
                        "locationId" => $locationId,
                        "locationType" => $locationType
                    ));

        $request->request->remove('locationId');

        $postData = array(
            "title" => $request->request->get("title", ""),
            "unitNumber" => $request->request->get("unitNumber", ""),
            "buildingName" => $request->request->get("buildingName", ""),
            "streetNumber" => $request->request->get("streetNumber", ""),
            "streetName" => $request->request->get("streetName", ""),
            "subdivision" => $request->request->get("subdivision", ""),
            "zipCode" => $request->request->get("zipCode", ""),
            "streetAddress" => $request->request->get("streetAddress", ""),
            "longitude" => $request->request->get("longitude", ""),
            "latitude" => $request->request->get("latitude", ""),
            "landline" => $request->request->get("landline", "")
        );

        $form = $this->transactForm('core_user_address', $userAddress, $postData);

        if($form->isValid() && !is_null($location) && $location){

            $locationDetails = array();
            if(!is_null($location)){
                $locationDetails = $location->getLocalizedLocationTree(true);
            }

            $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
            $userAddress = $userAddressService->editUserAddress($form->getData(), $location);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "User address successfully edited.",
                "data" => array(
                    "userAddressId" => $userAddress->getUserAddressId(),
                    "locationId" => !is_null($location)? $location->getLocationId() : null,
                    "title" => $userAddress->getTitle(),
                    "unitNumber" => $userAddress->getUnitNumber(),
                    "buildingName" => $userAddress->getBuildingName(),
                    "streetNumber" => $userAddress->getStreetNumber(),
                    "streetName" => $userAddress->getStreetName(),
                    "subdivision" => $userAddress->getSubdivision(),
                    "zipCode" => $userAddress->getZipCode(),
                    "streetAddress" => $userAddress->getStreetAddress(),
                    "provinceId" => $userAddressService->getLocationId("province", $locationDetails),
                    "province" => $userAddressService->getLocation("province", $locationDetails),
                    "cityId" => $userAddressService->getLocationId("city", $locationDetails),
                    "city" => $userAddressService->getLocation("city", $locationDetails),
                    "barangayId" => $userAddressService->getLocationId("barangay", $locationDetails),
                    "barangay" => $userAddressService->getLocation("barangay", $locationDetails),
                    "longitude" => $userAddress->getLongitude(),
                    "latitude" => $userAddress->getLatitude(),
                    "landline" => $userAddress->getLandline(),
                    "fullLocation" => $userAddress->getAddressString(),
                    "isDefault" => $userAddress->getIsDefault(),
                )
            ), 200);
        }

        $errors = $formErrorService->throwInvalidFields($form);

        if(is_null($location) || !$location){
            array_push($errors, "Province, City & Barangay is required.");
        }

        return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
    }

    public function deleteUserAddressAction(Request $request)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $authenticatedUser = $this->getAuthenticatedUser();

        $entityManager = $this->getDoctrine()->getManager();
        $userAddressId = $request->request->get('userAddressId', 0);

        $userAddressRepository = $entityManager->getRepository("YilinkerCoreBundle:UserAddress");
        $userAddress = $userAddressRepository->findOneBy(array(
                            "userAddressId" => $userAddressId,
                            "user" => $authenticatedUser
                        ));

        if(is_null($userAddress)){
            return $formErrorService->throwResourceNotFoundResponse("User address not found.");
        }

        if($userAddress->getIsDefault()){
            return $formErrorService->throwCustomErrorResponse(array("Can't delete primary user address."), "Delete failed.");
        }

        $userAddress->setIsDelete(true);
        $entityManager->persist($userAddress);
        $entityManager->flush();

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "User address successfully deleted.",
            "data" => array()
        ), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function setDefaultAddressAction(Request $request)
    {
        $userAddressId = (int)$request->request->get("userAddressId", 0);

        $userAddressRepository = $this->getDoctrine()->getManager()->getRepository("YilinkerCoreBundle:UserAddress");
        $userAddress = $userAddressRepository->find($userAddressId);

        $authenticatedUser = $this->getAuthenticatedUser();

        if(!is_null($userAddress) && $userAddress->getUser() == $authenticatedUser){

            $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
            
            if(!$userAddress->getIsDefault()){
                $userAddressService->setDefaultUserAddress($userAddress, $authenticatedUser);
            }
            
            $location = $userAddress->getLocation();

            $locationDetails = array();
            if(!is_null($location)){
                $locationDetails = $location->getLocalizedLocationTree(true);
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "User address set to default.",
                "data" => array(
                    "userAddressId" => $userAddress->getUserAddressId(),
                    "locationId" => !is_null($location)? $location->getLocationId() : null,
                    "title" => $userAddress->getTitle(),
                    "unitNumber" => $userAddress->getUnitNumber(),
                    "buildingName" => $userAddress->getBuildingName(),
                    "streetNumber" => $userAddress->getStreetNumber(),
                    "streetName" => $userAddress->getStreetName(),
                    "subdivision" => $userAddress->getSubdivision(),
                    "zipCode" => $userAddress->getZipCode(),
                    "streetAddress" => $userAddress->getStreetAddress(),
                    "provinceId" => $userAddressService->getLocationId("province", $locationDetails),
                    "province" => $userAddressService->getLocation("province", $locationDetails),
                    "cityId" => $userAddressService->getLocationId("city", $locationDetails),
                    "city" => $userAddressService->getLocation("city", $locationDetails),
                    "barangayId" => $userAddressService->getLocationId("barangay", $locationDetails),
                    "barangay" => $userAddressService->getLocation("barangay", $locationDetails),
                    "longitude" => $userAddress->getLongitude(),
                    "latitude" => $userAddress->getLatitude(),
                    "landline" => $userAddress->getLandline(),
                    "fullLocation" => $userAddress->getAddressString(),
                    "isDefault" => $userAddress->getIsDefault(),
                )
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "User address not found.",
            "data" => array()
        ), 402);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserAddressesAction(Request $request)
    {
        $userAddressService = $this->get('yilinker_core.service.user_address.user_address');

        $userAddresses = $userAddressService->getUserAddresses($this->getAuthenticatedUser());

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "User address collection.",
            "data" => $userAddresses
        ), 200);
    }

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $postData
     * @return \Symfony\Component\Form\Form
     */
    private function transactForm($formType, $entity, $postData)
    {
        $form = $this->createForm($formType, $entity);
        $form->submit($postData);

        return $form;
    }

    /**
     * @param $array
     * @param $index
     * @param $value
     */
    private function assignIfNotNull(&$array, $index, $value)
    {
        if(!is_null($value)){
            $array[$index] = $value;
        }
    }

    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }
}

