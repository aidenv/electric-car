<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Device;

class DeviceController extends Controller
{
    public function connectDeviceAction(Request $request)
    {
            $token      = $request->get("token");
            $em = $this->getDoctrine()->getManager();

            $jwtManager = $this->get("yilinker_core.service.jwt_manager");

            $tokenDetails = $jwtManager->decodeToken($token);

            $userRepository = $em->getRepository("YilinkerCoreBundle:User");
            $user = $userRepository->find($tokenDetails["userId"]);

            //if user not found throw error
            if(is_null($user)){
                return new JsonResponse(array(
                    "isSuccessful"  => false,
                    "message"       => "User not found.",
                    "data"          => array()
                ), 404);
            }

            $deviceRepository = $em->getRepository("YilinkerCoreBundle:Device");
            $device = $deviceRepository->findOneBy(array(
                                            "token" => $token,
                                            "user"  => $user
                                        ));

            $gcmService = $this->get('yilinker_core.service.device.gcm');
            $gcmService->setAuthenticatedUser($user);

            //if device not registered add
            if(is_null($device)){
                $gcmService->addToken($token, Device::DEVICE_TYPE_WEB, Device::TOKEN_TYPE_JWT);
            }
            else{
                //else update
                $gcmService->updateRegistrationId($token, $token, $device, false);
            }

        return new JsonResponse(array(
            "isSuccessful"  => true,
            "message"       => "Device connected.",
            "data"          => array()
        ), 200);
    }

    public function disconnectDeviceAction(Request $request)
    {
        try{
            $token      = $request->get("token");
            $em = $this->getDoctrine()->getManager();

            $jwtManager = $this->get("yilinker_core.service.jwt_manager");

            $tokenDetails = $jwtManager->decodeToken($token);

            $userRepository = $em->getRepository("YilinkerCoreBundle:User");
            $user = $userRepository->find($tokenDetails["userId"]);

            //if user not found throw error
            if(is_null($user)){
                return new JsonResponse(array(
                    "isSuccessful"  => false,
                    "message"       => "User not found.",
                    "data"          => array()
                ), 404);
            }

            $deviceRepository = $em->getRepository("YilinkerCoreBundle:Device");
            $device = $deviceRepository->findOneBy(array(
                                            "registrationId" => $token,
                                            "user"           => $user
                                        ));

            $gcmService = $this->get('yilinker_core.service.device.gcm');
            $gcmService->setAuthenticatedUser($user);

            //if device not registered add
            if(is_null($device)){
                return new JsonResponse(array(
                    "isSuccessful"  => false,
                    "message"       => "Device not found.",
                    "data"          => array()
                ), 404);
            }

            $gcmService->deleteToken($token, Device::TOKEN_TYPE_JWT);
        }
        catch(Exception $e){

        }

        return new JsonResponse(array(
            "isSuccessful"  => true,
            "message"       => "Device disconnected.",
            "data"          => array()
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
