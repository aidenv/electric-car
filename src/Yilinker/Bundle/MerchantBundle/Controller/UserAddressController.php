<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;

class UserAddressController extends Controller
{
    /**
     * Render Dashboard Store Address Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderUserAddressesAction()
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
        $userAddresses = $userAddressService->getUserAddresses($authenticatedUser, "DESC");

        $locationService = $this->get('yilinker_core.service.location.location');
        $provinces = $locationService->getAll(LocationType::LOCATION_TYPE_PROVINCE, true);

        return $this->render('YilinkerMerchantBundle:UserAddress:store_address.html.twig', compact('userAddresses', 'provinces'));
    }

    /**
     * Validate Address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateAddressAction (Request $request)
    {
        $isSuccessful = true;
        $message = null;

        $locationId = $request->request->get('locationId', null);
        $em = $this->getDoctrine()->getManager();
        $locationRepository = $em->getRepository("YilinkerCoreBundle:Location");
        $locationEntity = $locationRepository->find($locationId);

        $formData = array (
            "title" => $request->request->get("addressTitle", null),
            "unitNumber" => $request->request->get("unitNumber", null),
            "buildingName" => $request->request->get("buildingName", null),
            "streetNumber" => $request->request->get("streetNumber", null),
            "streetName" => $request->request->get("streetName", null),
            "subdivision" => $request->request->get("subdivision", null),
            "zipCode" => $request->request->get("zipCode", null),
            "streetAddress" => null,
            "longitude" => null,
            "latitude" => null,
        );

        $form = $this->createForm('core_user_address', new UserAddress());
        $form->submit($formData);

        if (!$locationEntity) {
            $isSuccessful = false;
            $message = array('Invalid Location');
        }
        else if (!$form->isValid()) {
            $isSuccessful = false;
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $message = $formErrorService->throwInvalidFields($form);
        }

        $response = compact (
            'isSuccessful',
            'message'
        );

        return new JsonResponse($response);
    }

    /**
     * Get children of a particular location
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChildrenLocationsAction(Request $request)
    {
        $locationId = $request->get('locationId', null);
        $location = $this->getDoctrine()
                         ->getRepository('YilinkerCoreBundle:Location')
                         ->findOneBy(array(
                            'locationId' => $locationId,
                            'isActive' => true
                        ));

        $response = array (
            'isSuccessful' => false,
            'message' => 'Location not found',
            'data' => array(),
        );

        if ($location !== null) {
            $locations = $location->getActiveChildren();
            $response['isSuccessful'] = count($locations) > 0;
            $response['message'] = count($locations) > 0 ? "" : "No children location found";
            $locationData = array();

            foreach($locations as $location) {
                $locationData[] = $location->toArray();
            }

            $response['data']['locations'] = $locationData;
            $response['data']['parentType'] = $location->getLocationType()->getLocationTypeId();
        }

        return new JsonResponse($response);
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
