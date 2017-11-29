<?php

namespace Yilinker\Bundle\CoreBundle\Services\Device;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\CoreBundle\Services\Contact\ContactService;
use Endroid\Gcm\Client;
use Yilinker\Bundle\CoreBundle\Services\Predis\PredisService;

/**
 * Class GcmService
 * @package Yilinker\Bundle\CoreBundle\Services\Message
 */
class GcmService
{
    /**
     * @var \Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var ContactService
     */
    private $contactService;

    /**
     * @var Predis
     */
    private $predisService;

    /**
     * @var Broadcaster
     */
    private $broadcaster;

    /**
     * @param EntityManager $entityManager
     * @param ContactService $contactService
     */
    public function __construct(
        EntityManager $entityManager, 
        ContactService $contactService,
        PredisService $predisService,
        Broadcaster $broadcaster
    )
    {
        $this->entityManager = $entityManager;
        $this->contactService = $contactService;
        $this->predisService = $predisService;
        $this->broadcaster = $broadcaster;
    }

    /**
     * @param mixed $authenticatedUser
     */
    public function setAuthenticatedUser($authenticatedUser)
    {
        $this->authenticatedUser = $authenticatedUser;

        return $this;
    }

    /**
     * Create token id
     *
     * @param $registrationId
     * @return JsonResponse
     */
    public function addToken(
        $token, 
        $deviceType, 
        $tokenType = Device::TOKEN_TYPE_REGISTRATION_ID,
        $broadcast = true,
        $overrideExisting = false
    ){
        $authenticatedUser = $this->authenticatedUser;

        if($overrideExisting){
            $device = $this->entityManager
                           ->getRepository("YilinkerCoreBundle:Device")
                           ->findOneByToken($token);

            $device = !$device? new Device() : $device;
        }
        else{
            $device = new Device();
        }

        $device->setToken($token);
        $device->setDeviceType($deviceType);
        $device->setTokenType($tokenType);
        $device->setIsIdle(false);
        $device->setIsDelete(false);
        $device->setIsNotificationSubscribe(true);

        if($authenticatedUser){
            $device->setUser($authenticatedUser);
        }

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        if($broadcast && $authenticatedUser){
            $this->broadcastUserOnline();
        }
    }

    /**
     * Deletes the token
     *
     * @param $token
     * @return JsonResponse
     */
    public function deleteToken($token, $tokenType)
    {
        $authenticatedUser = $this->authenticatedUser;
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update("YilinkerCoreBundle:Device", "ur")
                     ->set("ur.isDelete", true)
                     ->where("ur.token = :token")
                     ->andWhere("ur.tokenType = :tokenType")
                     ->andWhere("ur.user = :user")
                     ->setParameter(":token", $token)
                     ->setParameter(":tokenType", $tokenType)
                     ->setParameter(":user", $authenticatedUser)
                     ->getQuery()
                     ->execute();

        $deviceRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Device");

        $getActiveNonIdleDevices = $authenticatedUser->getActiveNonIdleDevices();

        //if registration ids count is 0 then set to offline
        if($getActiveNonIdleDevices->count() == 0 && $authenticatedUser){
            $this->broadcastUserOffline();
        }
    }

    /**
     * Updates the registration id
     *
     * @param $oldRegistrationId
     * @param $newRegistrationId
     * @param $isIdle
     * @return JsonResponse
     */
    public function updateRegistrationId($oldRegistrationId, $newRegistrationId, $device, $isIdle)
    {
        $authenticatedUser = $this->authenticatedUser;
        $deviceRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Device");

        $device->setToken($newRegistrationId)
               ->setisIdle(filter_var($isIdle, FILTER_VALIDATE_BOOLEAN));

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        $devices = $deviceRepository->loadUserRegistrationIds($authenticatedUser);

        $idleDevice = 0;
        foreach($devices as $device){
            if($device->getIsIdle()){
                $idleDevice++;
            }
        }

        if(count($devices) == $idleDevice){
            $this->broadcastUserOffline();
        }
        else{
            $this->broadcastUserOnline();
        }
    }

    public function broadcastUserOffline()
    {
        $authenticatedUser = $this->authenticatedUser;
        $deviceRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Device");
        $contacts = $this->contactService->getContacts($authenticatedUser, null, null, null);
        $tokens = $deviceRepository->loadAllTokens(
                    $contacts, 
                    array(
                        Device::TOKEN_TYPE_REGISTRATION_ID,
                        Device::TOKEN_TYPE_JWT
                    ),
                    false
                );

        $gcmResponse = array(
            "isSuccessful" => true,
            "responseType" => "USER_ONLINE_STATUS",
            "message" => "User is now offline.",
            "data" => array(
                "userId" => $authenticatedUser->getUserId(),
                "name" => $authenticatedUser->getFullName(),
                "isOnline" => false
            )
        );

        $this->updateUserLastLoginDate();
        $this->sendToGCM($gcmResponse, $tokens);
        $this->predisService->publishUserOffline($authenticatedUser, $tokens);
    }

    public function broadcastUserOnline()
    {
        $authenticatedUser = $this->authenticatedUser;
        $deviceRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Device");

        $activeTokens = $authenticatedUser->getActiveNonIdleDevices();

        if($activeTokens){
            $contacts = $this->contactService->getContacts($authenticatedUser, null, null, null);
            $tokens = $deviceRepository->loadAllTokens(
                        $contacts, 
                        array(
                            Device::TOKEN_TYPE_REGISTRATION_ID,
                            Device::TOKEN_TYPE_JWT
                        ),
                        false
                    );

            $gcmResponse = array(
                                "isSuccessful" => true,
                                "responseType" => "USER_ONLINE_STATUS",
                                "message" => "User is now online.",
                                "data" => array(
                                    "userId" => $authenticatedUser->getUserId(),
                                    "name" => $authenticatedUser->getFullName(),
                                    "isOnline" => true
                                )
                            );

            $this->sendToGCM($gcmResponse, $tokens);
            $this->predisService->publishUserOnline($authenticatedUser, $tokens);
        }
    }

    /**
     * Updates user last login date on idle/delete registration id
     */
    private function updateUserLastLoginDate(){

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update("YilinkerCoreBundle:User", "u")
                     ->set("u.lastLogoutDate", ":lastLogoutDate")
                     ->where("u = :user")
                     ->setParameter(":lastLogoutDate", Carbon::now())
                     ->setParameter(":user", $this->authenticatedUser)
                     ->getQuery()
                     ->execute();
    }

    /**
     * Required fields not supplied
     *
     * @param string $error_type
     * @param null $errors
     * @return JsonResponse
     */
    public function throwInvalidFields($error_type = "", $errors = null)
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "responseType" => $error_type,
            "message" => "Invalid fields supplied.",
            "data" => $errors
        );

        return new JsonResponse($response, 400);
    }

    /**
     * User not found in DB
     *
     * @param string $error_type
     * @return JsonResponse
     */
    public function throwUserNotFound($error_type = "")
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "responseType" => $error_type,
            "message" => "Requested user not found.",
            "data" => array()
        );

        return new JsonResponse($response, 402);
    }

    /**
     * Sends data to gcm
     *
     * @param $data
     * @param $registrationIds
     */
    private function sendToGCM($data, $registrationIds)
    {
        if(!empty($registrationIds)){
            $this->broadcaster->init($registrationIds)->send($data);
        }
    }
}
