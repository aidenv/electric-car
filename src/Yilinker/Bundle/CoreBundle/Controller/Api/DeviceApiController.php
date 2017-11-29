<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Device;

class DeviceApiController extends Controller
{
    /**
     * Creates registration id
     *
     * @param Request $request
     * @return mixed
     */
    public function addRegistrationIdAction(Request $request)
    {
        $gcmService = $this->get('yilinker_core.service.device.gcm');

        $token = $request->request->get("registrationId", null);
        $deviceType = $request->request->get("deviceType", 0);

        if(is_null($token) OR $token == ""){
            return $gcmService->throwInvalidFields("CREATE_REGISTRATION_ID", array("Registration id is required."));
        }

        $gcmService->setAuthenticatedUser($this->getUser());
        $gcmService->addToken($token, $deviceType, Device::TOKEN_TYPE_REGISTRATION_ID);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "CREATE_REGISTRATION_ID",
            "message" => "Registration id successfully created.",
            "data" => array()
        ), 201);
    }

    /**
     * Deletes registration id
     *
     * @param Request $request
     * @return mixed
     */
    public function deleteRegistrationIdAction(Request $request)
    {
        $authorizationChecker = $this->get("security.authorization_checker");
        $gcmService = $this->get('yilinker_core.service.device.gcm');

        $token = $request->request->get("registrationId", null);

        if(is_null($token) OR $token == ""){
            return $gcmService->throwInvalidFields("DELETE_REGISTRATION_ID", array("Registration id is required."));
        }

        $em = $this->getDoctrine()->getManager();
                         
        $user = $this->getUser();
        

        if(
            !$authorizationChecker->isGranted("IS_AUTHENTICATED_FULLY") &&
            !$authorizationChecker->isGranted("IS_AUTHENTICATED_REMEMBERED") 
        ){

            $user = $em->getRepository("YilinkerCoreBundle:User")
                       ->findOneByEmail($request->request->get("email", null));
        }

        $device = $em->getRepository("YilinkerCoreBundle:Device")->findOneBy(array(
                    "user" => $user,
                    "token" => $token
                  ));

        if($device){
            $gcmService->setAuthenticatedUser($user);

            $gcmService->deleteToken($token, Device::TOKEN_TYPE_REGISTRATION_ID);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "responseType" => "DELETE_REGISTRATION_ID",
                "message" => "Registration id successfully deleted.",
                "data" => array()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "responseType" => "DELETE_REGISTRATION_ID",
            "message" => "Invalid user.",
            "data" => array("Invalid user.")
        ), 400);
    }

    /**
     * Updates registration id
     *
     * @param Request $request
     * @return mixed
     */
    public function updateRegistrationIdAction(Request $request)
    {
        $gcmService = $this->get('yilinker_core.service.device.gcm');

        $oldRegistrationId = $request->request->get("oldRegistrationId", null);
        $newRegistrationId = $request->request->get("newRegistrationId", null);
        
        $isIdle = $request->request->get("isIdle", false);

        $authenticatedUser = $this->getAuthenticatedUser();

        $entityManager = $this->getDoctrine()->getManager();

        if(is_null($oldRegistrationId) OR $oldRegistrationId == "" OR is_null($newRegistrationId) OR $newRegistrationId == ""){
            return $gcmService->throwInvalidFields("UPDATE_REGISTRATION_ID", array("Registration id is required."));
        }

        $deviceRepository = $entityManager->getRepository("YilinkerCoreBundle:Device");

        $device = $deviceRepository->findOneBy(array(
                                        "registrationId" => $oldRegistrationId,
                                        "user" => $authenticatedUser
                                    ));

        if(is_null($device)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "responseType" => "UPDATE_REGISTRATION_ID",
                "message" => "Registration id not found.",
                "data" => array()
            ), 404);
        }

        $gcmService->setAuthenticatedUser($authenticatedUser);
        $gcmService->updateRegistrationId($oldRegistrationId, $newRegistrationId, $device, $isIdle);

        return new JsonResponse(array(
            "isSuccessful" => true,
            "responseType" => "UPDATE_REGISTRATION_ID",
            "message" => "Registration id successfully updated.",
            "data" => array()
        ), 200);
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
