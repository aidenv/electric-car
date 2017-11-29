<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\ContactNumber;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductCancellationReason;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Yilinker\Bundle\CoreBundle\Form\Type\OrderProductCancellationFormType;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\UserIdentificationCard;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Carbon\Carbon;

class DashboardController extends YilinkerBaseController
{
    const POINT_ENTRIES_PER_PAGE = 15;

    const ORDERS_PER_PAGE = 15;

    const DISPUTE_CASE_PER_PAGE = 15;

    /**
     * Render Profile Information Markup
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileInformationAction(Request $request)
    {
        $securityContext = $this->container->get('security.context');

        if (
            !$securityContext->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $url = $this->generateUrl("user_buyer_login");
            return $this->redirect($url);
        }

        $authenticatedUser = $this->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();

        $em = $this->getDoctrine()->getManager();

        $locationRepository = $em->getRepository('YilinkerCoreBundle:Location');

        $provinces = $locationRepository->getLocationsByType(LocationType::LOCATION_TYPE_PROVINCE, true);

        $totalPoints = $em->getRepository('YilinkerCoreBundle:UserPoint')
                          ->filterBy(array('user' => $this->getUser()))
                          ->getSum('this.points');

        return $this->render('YilinkerFrontendBundle:Profile:profile_information.html.twig', array(
            "user"         => $authenticatedUser,
            "provinces"    => $provinces,
            "totalPoints"  => $totalPoints
        ));
    }

    /**
     * Update the user information
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\JsonResponse
     */
    public function profileInformationUpdateAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'You must be authenticated to access this page',
        );

        $securityContext = $this->container->get('security.context');
        if (
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $authenticatedUser = $this->get('security.token_storage')
                                      ->getToken()
                                      ->getUser();
            
            $form = $this->createForm('buyer_profile', null, array(
                'csrf_protection' => false,
                'buyerId' => $authenticatedUser->getUserId(),
            ));

            $form->submit(array(
                'firstname'      => $request->request->get('firstName'),
                'lastname'       => $request->request->get('lastName'),
                'defaultAddress' => $request->request->get('defaultAddressId')
            ));
            
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $formData = $form->getData();

                if($formData['defaultAddress']->getUser()->getUserId() === $authenticatedUser->getUserId()){   

                    $em->beginTransaction();

                    try{
                    
                        $authenticatedUser->setFirstName($formData['firstname'])
                                          ->setLastName($formData['lastname']);
                        if ($countryId = $request->get('country')) {
                            $country = $em->getReference('YilinkerCoreBundle:Country', $countryId);
                            $authenticatedUser->setCountry($country);
                        }
                        if ($languageId = $request->get('language')) {
                            $language = $em->getReference('YilinkerCoreBundle:Language', $languageId);
                            $authenticatedUser->setLanguage($language);
                        }
                        $formData['defaultAddress']->setIsDefault(true);                    
                        $em->flush();

                        $jwtService = $this->get("yilinker_core.service.jwt_manager");
                        $request = $jwtService->setKey("ylo_secret_key")->encodeUser($authenticatedUser)->encodeToken(null);

                        $ylaService = $this->get("yilinker_core.service.yla_service");
                        $ylaService->setEndpoint(false);

                        $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

                        $em->commit();

                        $response['message'] = 'Profile update successfully';
                        $response['isSuccessful'] = true;
                    }
                    catch(YilinkerException $e){
                        $response['message'] = $e->getMessage();
                    }
                }
                else{
                    $response['message'] = 'User address does not belong to this user';
                }
            }
            else{
                $response['message'] = $form->getErrors(true)[0]->getMessage();
            }
        }

        return new JsonResponse($response);        
    }

    public function addReferrerCodeAction(Request $request)
    {
        $response = array(
            'message' => 'Invalid referral code',
            'isSuccessful' => false,
            'data' => array(),
        );

        $referralCode = trim($request->get('referralCode', ''));

        if ($referralCode) {
            $coreAccountManager = $this->get('yilinker_core.service.account_manager');
            $response = $coreAccountManager->processReferralCode($referralCode, $this->getUser());

            if ($response['isSuccessful']) {
                $response['data'] = array('referrerName' => $response['data']->getReferrer()->getFullName());
            }
        }

        return new JsonResponse($response);
    }

    /**
     * Create a new address for the logged in user
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createNewAddressAction(Request $request)
    {
        $response = array(
            'message' => 'You must be logged-in to create a new address',
            'isSuccessful' => false,
            'data' => array(),
        );
        
        $doctrine = $this->getDoctrine();
        $securityContext = $this->container->get('security.context');
        if (
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $authenticatedUser = $this->get('security.token_storage')
                                      ->getToken()
                                      ->getUser();

            $unitNumber = $request->get('unitNumber', '');
            $buildingName = $request->get('buildingName', '');
            $streetNumber = $request->get('streetNumber', '');
            $subdivision = $request->get('subdivision', '');
            $zipCode = $request->get('zipCode', '');
            $addressTitle = $request->get('addressTitle', '');
            $streetName = $request->get('streetName', null);
            $locationId = $request->get('locationId', null);
            $latitude = $request->get('latitude', null);
            $longitude = $request->get('longitude', null);

            $em = $doctrine->getManager();
            $location = $this->getDoctrine()
                             ->getRepository('YilinkerCoreBundle:Location')
                             ->findOneBy(array(
                                 'locationId' => $locationId,
                                 'locationType' => LocationType::LOCATION_TYPE_BARANGAY,
                             ));

            if ($location !== null) {

                $form = $this->createForm('core_user_address', null);

                $form->submit(array(
                    'unitNumber' => $unitNumber,
                    'buildingName' => $buildingName,
                    'streetNumber' => $streetNumber,
                    'streetName' => $streetName,
                    'subdivision' => $subdivision,
                    'zipCode' => $zipCode,
                    'title' => $addressTitle,
                    'latitude' => $latitude, 
                    'longitude' => $longitude,
                ));

                if($form->isValid()){
                    $address = new UserAddress();
                    $address->setUnitNumber($unitNumber);
                    $address->setBuildingName($buildingName);
                    $address->setStreetNumber($streetNumber);
                    $address->setStreetName($streetName);
                    $address->setSubdivision($subdivision);
                    $address->setTitle($addressTitle);
                    $address->setZipCode($zipCode);
                    if($latitude != null){
                        $address->setLatitude(floatval($latitude));
                    }
                    if($longitude != null){
                        $address->setLongitude(floatval($longitude));
                    }

                    $insertedAddress = $this->get('yilinker_core.service.user_address.user_address')
                                            ->addUserAddress($authenticatedUser, $address, $location);

                    $response['message'] = "Address successfuly created";
                    $response['isSuccessful'] = true;
                    $response['data'] = $insertedAddress->toArray();
                    $response['data']['locationTree'] = $insertedAddress->getLocation()->getLocalizedLocationTree(true);
                }
                else{
                    $response['message'] = $form->getErrors(true)[0]->getMessage();
                }
            }
            else{
                $response['message'] = "The city, province and barangay must be set";
            }
        }
        
        return new JsonResponse($response);
    }

    /**
     * Update the address of a user
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateAddressAction(Request $request)
    {
        $response = array(
            'message' => 'You must be logged-in to delete an address',
            'isSuccessful' => false,
            'data' => array(),
        );
        $doctrine = $this->getDoctrine();
        $securityContext = $this->container->get('security.context');
        if (
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->get('security.token_storage')
                                      ->getToken()
                                      ->getUser();
            $userAddressId = $request->get('addressId', null);            
            $unitNumber = $request->get('unitNumber', '');
            $buildingName = $request->get('buildingName', '');
            $streetNumber = $request->get('streetNumber', '');
            $subdivision = $request->get('subdivision', '');
            $zipCode = $request->get('zipCode', '');
            $addressTitle = $request->get('addressTitle', '');
            $streetName = $request->get('streetName', null);
            $locationId = $request->get('locationId', null);
            $latitude = $request->get('latitude', null);
            $longitude = $request->get('longitude', null);

            $em = $doctrine->getManager();
            $location = $this->getDoctrine()
                             ->getRepository('YilinkerCoreBundle:Location')
                             ->findOneBy(array(
                                 'locationId'   => $locationId,
                                 'locationType' => LocationType::LOCATION_TYPE_BARANGAY,
                             ));
            if($location !== null){
                
                $form = $this->createForm('core_user_address', null);

                $form->submit(array(
                    'unitNumber' => $unitNumber,
                    'buildingName' => $buildingName,
                    'streetNumber' => $streetNumber,
                    'streetName' => $streetName,
                    'subdivision' => $subdivision,
                    'zipCode' => $zipCode,
                    'title' => $addressTitle,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ));
                if($form->isValid()){
                    $address = $em->getRepository('YilinkerCoreBundle:UserAddress')
                                  ->findOneBy(array(
                                      'userAddressId' => $userAddressId,
                                      'user' => $authenticatedUser->getUserId(),
                                  ));
                    if($address){

                        $address->setUnitNumber($unitNumber);
                        $address->setBuildingName($buildingName);
                        $address->setStreetNumber($streetNumber);
                        $address->setStreetName($streetName);
                        $address->setSubdivision($subdivision);
                        $address->setTitle($addressTitle);
                        $address->setZipCode($zipCode);
                        $address->setLocation($location);
                        if($latitude != null){
                            $address->setLatitude(floatval($latitude));
                        }
                        if($longitude != null){
                            $address->setLongitude(floatval($longitude));
                        }
                        $em->flush();
                        
                        $response['message'] = "Address successfuly updated";
                        $response['isSuccessful'] = true;
                        $response['data'] = $address->toArray();
                        $response['data']['locationTree'] = $address->getLocation()->getLocalizedLocationTree(true);
                    }
                    else{
                        $response['message'] = "You are not allowed to edit this address.";
                    }
                }            
                else{
                    $response['message'] = $form->getErrors(true)[0]->getMessage();
                }
            }
            else{
                $response['message'] = "The city, province and barangay must be set";
            }
        }

        return new JsonResponse($response);
    }

    /**
     * Delete an address of a user
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteAddressAction(Request $request)
    {
        $response = array(
            'message' => 'You must be logged-in to delete an address',
            'isSuccessful' => false,
            'data' => array(),
        );

        $doctrine = $this->getDoctrine();
        $securityContext = $this->container->get('security.context');
        if (
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $authenticatedUser = $this->get('security.token_storage')
                                      ->getToken()
                                      ->getUser();
            
            $userAddressId = $request->get('addressId', null);

            $address = $this->getDoctrine()->getManager()->getRepository('YilinkerCoreBundle:UserAddress')->find($userAddressId);

            if(is_null($address) || $address->getIsDefault()){
                $response['message'] = "Default address cannot be deleted";
                return new JsonResponse($response);
            }

            $isSuccessful = $this->get('yilinker_core.service.user_address.user_address')
                                 ->deleteUserAddress($userAddressId, $authenticatedUser->getUserId());
            $response['isSuccessful'] = $isSuccessful;
            if($isSuccessful){
                $response['message'] = "Address successfully deleted";
            }
            else{
                $response['message'] = "Address cannot be deleted because it does not exist";
            }
        }
        
        return new JsonResponse($response);
    }

    /**
     * Update the user's email
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateEmailAction(Request $request)
    {
        $email = $request->get('email', null);

        $entityManager = $this->getDoctrine()->getManager();

        $authenticatedUser = $this->getAuthenticatedUser();

        $entityManager->beginTransaction();

        try{

            if($authenticatedUser->getEmail() == $email){
                throw new YilinkerException("New email should not be the same with your present email.", 1);
            }

            $accountManager = $this->get("yilinker_front_end.service.user.account_manager");

            $response = $accountManager->updateUserEmail($authenticatedUser, $email, false);

            if(!$response["isSuccessful"]){
                return new JsonResponse($response, 400);
            }

            $jwtService = $this->get("yilinker_core.service.jwt_manager");
            $request = $jwtService->setKey("ylo_secret_key")->encodeUser($authenticatedUser)->encodeToken(null);

            $ylaService = $this->get("yilinker_core.service.yla_service");
            $ylaService->setEndpoint(false);

            $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

            $accountManager->updateUserEmail($authenticatedUser, $email, true);

            $entityManager->flush();
            $entityManager->commit();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Email has been changed.",
                "data" => array()
            ), 200);
        }
        catch(YilinkerException $e){
            $entityManager->rollback();

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Email change failed.",
            "data" => array()
        ), 400);
    }

    /**
     * Update the password of a user
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updatePasswordAction(Request $request)
    {
        $response = array(
            'message' => 'You must be logged-in to update your password',
            'isSuccessful' => false,
        );

        $em = $this->getDoctrine()->getManager();
        $securityContext = $this->container->get('security.context');
        if (
            $securityContext->isGranted('IS_AUTHENTICATED_FULLY') ||
            $securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $tokenStorage = $this->get('security.token_storage');
            $authenticatedUser = $tokenStorage->getToken()->getUser();
                     
            $confirmNewPassword = $request->get('confirmNewPassword', null);
            $newPassword = $request->get('newPassword', null);
            $oldPassword = $request->get('oldPassword', '');

            $form = $this->createForm('core_change_password', null);
            $form->submit(array(
                'plainPassword' => array(
                    'first'  => $newPassword,
                    'second' => $confirmNewPassword,
                ),
                'oldPassword' =>  $oldPassword,
            ));

            $passwordEncoder = $this->get('security.encoder_factory')->getEncoder($authenticatedUser);

            $isPasswordValid = $passwordEncoder->isPasswordValid($authenticatedUser->getPassword(), $oldPassword, null);

            if(!$isPasswordValid){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Old password does not match.",
                    "data" => array()
                ), 200);
            }

            $isOldPassword =  $passwordEncoder->isPasswordValid($authenticatedUser->getPassword(), $newPassword, null);

            if($isOldPassword){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "New password is same to the old password.",
                    "data" => array(
                        "errors" => array("New password is same to the old password.")
                    )
                ), 200);
            }

            if($form->isValid()){
                $formData = $form->getData();
                $em->beginTransaction();

                try{

                    $authenticatedUser->setPlainPassword($formData['plainPassword']);
                    $em->flush();

                    $jwtService = $this->get("yilinker_core.service.jwt_manager");
                    $request = $jwtService->setKey("ylo_secret_key")->encodeUser($authenticatedUser)->encodeToken(null);

                    $ylaService = $this->get("yilinker_core.service.yla_service");
                    $ylaService->setEndpoint(false);

                    $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));

                    if(is_array($response) && array_key_exists("isSuccessful", $response) && $response["isSuccessful"]){ 
                        $em->commit();          
                        $response['message'] = "Password successfully updated";
                        $response['isSuccessful'] = true;
                        $tokenStorage->setToken(null);
                    }
                    else{
                        throw new YilinkerException("Error Processing Request", 1);
                    }

                }
                catch(YilinkerException $e){
                    $em->rollback();
                    $response['message'] = $e->getMessage();
                }
            }
            else{
                $response['message'] = $form->getErrors(true)[0]->getMessage();
            }
        }
        
        return new JsonResponse($response); 
    }

    /**
     * Update the contact number
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateContactNumberAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'Contact number update is currently not available',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();

        $form = $this->createForm('core_change_contact_number', null, array(
            'csrf_protection' => false,
        ));

        $form->submit(array(
            'contactNumber' =>  $request->get('newContactNumber', ''),
        ));
        
        if($form->isValid()){
            $formData = $form->getData();
            $response = $this->get('yilinker_core.service.sms.sms_service')
                             ->sendUserVerificationCode($authenticatedUser, $formData['contactNumber']);
            if($response['isSuccessful']){
                $response['data'] = array(
                    'expiration_in_minutes' => Verification::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                );
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }


        return new JsonResponse($response);
    }

    /**
     * Get children of a particular location
     *
     * Symfony\Component\HttpFoundation\Request $request
     * Symfony\Component\HttpFoundation\JsonResponse
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

        $response = array(
            'isSuccessful' => false,
            'message' => 'Location not found',
            'data' => array(),
        );

        if($location !== null){
            $locations = $location->getActiveChildren();
            $response['isSuccessful'] = count($locations) > 0;
            $response['message'] = count($locations) > 0 ? "" : "No children location found";
            $locationData = array();
            foreach($locations as $location){
                $locationData[] = $location->toArray();
            }
            $response['data']['locations'] = $locationData;
            $response['data']['parentType'] = $location->getLocationType()->getLocationTypeId(); 
        }

        return new JsonResponse($response);
    }
    
    /**
     * Render Profile Activity Log Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileActivityLogAction()
    {
        $em = $this->getDoctrine()->getEntityManager();

        $tbUserActivities = $em->getRepository('YilinkerCoreBundle:UserActivityHistory');
        $user = $this->getUser();
        $timeline = $tbUserActivities->getTimelinedActivities($user->getId());

        $data = compact('timeline');

        return $this->render('YilinkerFrontendBundle:Profile:profile_activity_log.html.twig', $data);
    }

    /**
     * Render Profile Following page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function profileFollowingAction(Request $request)
    {
        $securityContext = $this->container->get('security.context');
        $em = $this->getDoctrine()->getManager();

        if (
            !$securityContext->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $url = $this->generateUrl("user_buyer_login");
            return $this->redirect($url);
        }

        $authenticatedUser = $this->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();
        $locationRepository = $this->getDoctrine()
                                   ->getRepository('YilinkerCoreBundle:Location');
        $provinces = $locationRepository->getLocationsByType(LocationType::LOCATION_TYPE_PROVINCE);
        $userFollowService = $this->get('yilinker_front_end.service.user.user_follow');

        $page = (int) $request->request->get("page", 1);
        $limit = (int) $request->request->get("limit", 10);

        if ($page < 1 OR $limit < 1) {
            return $userFollowService->throwInvalidFields(null, false, array("Invalid limit or offset supplied"));
        }

        $userFollowService->setAuthenticatedUser($authenticatedUser);
        $offset = $userFollowService->getOffset(10, $page);
        $userFollowRepository = $em->getRepository("YilinkerCoreBundle:UserFollow");
        $followedSellers = $userFollowRepository->loadFollowedSellers($authenticatedUser, '', $limit, $offset);
        $followedSellersData = $userFollowService->constructSellers($followedSellers);

        $data = array (
            "user" => $authenticatedUser,
            "provinces" => $provinces,
            "listOfFollowedSeller" => $followedSellersData
        );

        return $this->render('YilinkerFrontendBundle:Profile:profile_following.html.twig', $data);
    }

    /**
     * Render Profile Messages Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileMessagesAction(Request $request, $userId)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $messageService = $this->get('yilinker_core.service.message.chat');

        $page = 1;
        $limit = 10;

        $messageService->setAuthenticatedUser($authenticatedUser);
        $messages = $messageService->getConversationHead($limit, $page);

        return $this->render('YilinkerFrontendBundle:Profile:profile_messages.html.twig', compact('messages', 'userId'));
    }

    /**
     * Render Profile Settings Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileSettingsAction()
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $em = $this->getDoctrine()->getEntityManager();

        $smsSubcription = $em->getRepository('YilinkerCoreBundle:SmsNewsletterSubscription')
                             ->findOneBy(array(
                                 'userId'   => $authenticatedUser->getUserId(),
                                 'isActive' => true,
                             ));

        return $this->render('YilinkerFrontendBundle:Profile:profile_settings.html.twig', array(
            'isSubscribed' => $smsSubcription !== null,
        ));
    }

    /**
     * Render Profile Transactions Markup
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileTransactionAction(Request $request)
    {
        $securityContext = $this->container->get('security.context');
        if (
            !$securityContext->isGranted('IS_AUTHENTICATED_FULLY') &&
            !$securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $url = $this->generateUrl("user_buyer_login");
            return $this->redirect($url);
        }

        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $transactionService = $this->get('yilinker_core.service.transaction');
                
        $dateToFilters = array(
            'week' => Carbon::now()->startOfWeek()->toDateString('Y-m-d'),
            'month' => Carbon::now()->startOfMonth()->toDateString('Y-m-d'),
            'year' => '2016-01-01', //Carbon::now()->startOfYear()->toDateString('Y-m-d'),
        );

        $page = (int) $request->get('page', 1);
        $dateFrom = $request->get('dateFrom', $dateToFilters['year']);
        $dateTo = $request->get('dateTo', Carbon::now()->toDateString('Y-m-d'));        
        if($dateFrom){
            $dateFrom = Carbon::createFromFormat("Y-m-d", $dateFrom)->startOfDay();
        }
        if($dateTo){
            $dateTo = Carbon::createFromFormat("Y-m-d", $dateTo)->endOfDay();
        }

        $tab = $request->get('tab', null);
        $paymentMethod = $request->get('paymentMethod', null);
        $paymentMethod = $paymentMethod === "" ? null : $paymentMethod; 

        $orderProductStatuses = null;
        $forFeedback = null;
        if($tab === 'on-delivery'){
            $orderProductStatuses = array(
                OrderProductStatus::STATUS_PRODUCT_ON_DELIVERY,
            );
        }
        else if($tab === "for-feedback"){
            $forFeedback = true;
        }
        $orderStatuses = $transactionService->getOrderStatusesValid(true);
        $orderSearchResults = $this->get('yilinker_core.service.transaction')
                                   ->getBuyerTransactions(
                                       $authenticatedUser->getUserId(), 
                                       $dateFrom, 
                                       $dateTo, 
                                       $orderStatuses,
                                       $paymentMethod, 
                                       $page,
                                       self::ORDERS_PER_PAGE,
                                       null, null, null,
                                       $orderProductStatuses,
                                       $forFeedback, null,
                                       "create", "desc"
                                       
                                   );

        $paymentMethods = $em->getRepository('YilinkerCoreBundle:PaymentMethod')
                             ->findAll();
        $dateFilters = array(
            'dateFrom' => $dateToFilters,
            'dateTo' => Carbon::now()->toDateString('Y-m-d'),
        );

        return $this->render('YilinkerFrontendBundle:Profile:profile_transaction.html.twig', array(
            'buyer' => $authenticatedUser,
            'orders' => $orderSearchResults['orders'],
            'totalTransactionCount' => $orderSearchResults['totalResultCount'],
            'perPage' => self::ORDERS_PER_PAGE,
            'paymentMethods' => $paymentMethods,
            'dateFilters' => $dateFilters,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
        ));
    }

    /**
     * Render Profile Transactions Markup
     *
     * @param string $invoice
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileTransactionViewAction($invoice)
    {
        $securityContext = $this->container->get('security.context');
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $buyerId =  $authenticatedUser->getUserId();
        $transactionService = $this->get('yilinker_core.service.transaction');

        $validOrderStatuses = $transactionService->getOrderStatusesValid();
        $validOrderStatuses[] = UserOrder::ORDER_STATUS_WAITING_FOR_PAYMENT;
        $validOrderStatuses[] = UserOrder::ORDER_STATUS_ORDER_REJECTED_FOR_FRAUD;
        $order = $em->getRepository('YilinkerCoreBundle:UserOrder')
                    ->findOneBy(array(
                        'invoiceNumber' => $invoice,
                        'buyer'         => $buyerId,
                    ));

        if(count($order) === 0 || !in_array($order->getOrderStatus()->getOrderStatusId(), $validOrderStatuses)){
            throw $this->createNotFoundException('Invoice not found');
        }

        $reasons = $em->getRepository('YilinkerCoreBundle:OrderProductCancellationReason')
                      ->findAll();

        $cancellableStatuses = $transactionService->getCancellableOrderProductStatus();
        $cancellableOrderProducts = array();
        if($order->getIsFlagged() === false){
            foreach($order->getOrderProducts() as $key => $orderProduct){
                $orderProductStatus = $orderProduct->getOrderProductStatus();
                if($orderProductStatus && in_array($orderProductStatus->getOrderProductStatusId(), $cancellableStatuses)){
                    $cancellableOrderProducts[] = $orderProduct;
                }
            }
        }

        $cancellationForm = $this->createForm('order_product_cancellation', null, array(
            'orderProducts' => $cancellableOrderProducts,            
        ));

        return $this->render('YilinkerFrontendBundle:Profile:profile_transaction_view.html.twig', array(
            'order' => $order,
            'cancellationReasons' => $reasons,
            'cancellationForm' => $cancellationForm->createView(),
        ));
    }

    /**
     * Cancel order products within a transaction
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileTransactionCancellationAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => '',
            'data' => array(),
        );

        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $this->getUser();        
        $orderProductIds = $request->get('orderProducts');
        $em = $this->getDoctrine()->getEntityManager();
        $orderProducts = $em->getRepository("YilinkerCoreBundle:OrderProduct")
                              ->getBuyerOrderProductsByIds(
                                  $authenticatedUser, $orderProductIds
                              );
        
        $form = $this->createForm('order_product_cancellation', null, array(
            'orderProducts' => $orderProducts,
            'userCancellationType' => OrderProductCancellationReason::USER_TYPE_BUYER
        ));
        $form->submit(array(
            '_token'        => $request->get('_token'),
            'orderProducts' => $orderProductIds,
            'reason'        => $request->get('reason'),
            'remark'        => $request->get('remark'),
        ));
      
        if ($form->isValid()) {
            
            $formData = $form->getData();
            $invoices = array();
            foreach($formData['orderProducts'] as $orderProduct){
                $order = $orderProduct->getOrder();
                $invoices[$order->getOrderId()] = $order;
            }

            if(count($invoices) === 1){
                if(reset($invoices)->getBuyer()->getUserId() === $authenticatedUser->getUserId()){
                    $transactionService = $this->container->get('yilinker_core.service.transaction');
                    $response['isSuccessful'] = $transactionService->cancellationRequestTransactionByUser(
                        $formData['orderProducts'],
                        $formData['reason'],
                        $formData['remark'],
                        $authenticatedUser
                    );
                    if($response['isSuccessful']){
                        $response['message'] = "Cancellation request successfuly sent.";
                    }
                }
                else{
                    $response['message'] = 'Order does not belong to the authenticated user';
                }
            }
            else{
                $response['message'] = 'Items do not belong to the same order';
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

    public function productListAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $user = $this->getUser();
        $order = $request->get('order');
        $reviews = $tbUserOrder->getReviewsOfUser($order, $user->getUserId());
        $data = compact('order', 'reviews');
           
        return $this->render('YilinkerFrontendBundle:Profile:order_product_list.html.twig', $data);
    }

    /**
     * Render Profile Help Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileHelpAction()
    {
        $frontendHostname = $this->getParameter("frontend_hostname");
        $merchantHostname = $this->getParameter("merchant_hostname");
        $supportMobile = $this->getParameter("support_contact_number");
        return $this->render('YilinkerFrontendBundle:Profile:profile_help.html.twig', 
            compact(
                "frontendHostname",
                "merchantHostname",
                "supportMobile"
            )
        );
    }

    /**
     * Render Resolution Center
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileResolutionCenterAction(Request $request)
    {
        $userEntity = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $disputeStatusType = $request->query->get('disputeStatusType', null);
        $searchKeyword = $request->query->get('searchKeyword', null);
        $page = (int) $request->get('page', 1);
        $pageLimit = self::DISPUTE_CASE_PER_PAGE;
        $validOrderStatuses = $em->getRepository('YilinkerCoreBundle:Dispute')->getValidStatusesForDispute();
        $userOrderEntity = $em->getRepository('YilinkerCoreBundle:UserOrder')
                              ->getTransactionOrderByBuyer (
                                  $userEntity->getUserId(),
                                  null,
                                  null,
                                  $validOrderStatuses,
                                  null,
                                  null, null, null,
                                  OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER,
                                  null, null, 0, PHP_INT_MAX
                              );

        $disputeStatuses = array (
            array (
                'status' => 'Refund',
                'orderProductStatusId' => OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED,
            ),
            array (
                'status' => 'Replacement',
                'orderProductStatusId' => OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED,
            )
        );
        $disputeArray = $this->get('yilinker_core.service.dispute_manager')
                             ->getCaseWithDetail(
                                 $userEntity,
                                 $disputeStatusType,
                                 null,
                                 $searchKeyword,
                                 $page,
                                 $pageLimit
                             );
        $disputeContainer = $disputeArray['cases'];
        $disputeCount = $disputeArray['count'];

        $disputeTypeStatuses = $em->getRepository('YilinkerCoreBundle:DisputeStatusType')->findAll();
        $disputeManager = $this->get('yilinker_core.service.dispute_manager');

        $reasonsForRefund = $disputeManager->getOrderProductReasonByType (
            OrderProductCancellationReason::REASON_TYPE_REFUND,
            OrderProductCancellationReason::USER_TYPE_BUYER
        );
        $reasonsForReplacement = $disputeManager->getOrderProductReasonByType (
            OrderProductCancellationReason::REASON_TYPE_REPLACEMENT,
            OrderProductCancellationReason::USER_TYPE_BUYER
        );

        $data = compact (
            'userOrderEntity',
            'disputeStatuses',
            'disputeContainer',
            'disputeTypeStatuses',
            'reasonsForRefund',
            'reasonsForReplacement',
            'pageLimit',
            'disputeCount'
        );

        return $this->render('YilinkerFrontendBundle:Profile:profile_resolution_center.html.twig', $data);
    }

    /**
     * Add Dispute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addCaseAction (Request $request)
    {
        $userEntity = $this->getUser();
        $description = $request->request->get('title', null);
        $message = $request->request->get('remarks', null);
        $orderProductStatus = $request->request->get('orderProductStatus', null);
        $orderProductIds = $request->request->get('orderProductIds', array());
        $orderProductReasonId = $request->request->get('reasonId', null);
        $csrfToken = $request->request->get('csrfToken', null);
        $transactionNumber = $request->get('transactionNumber', null);
        $formData = array (
            'description' => $description,
            'message' => $message,
            'orderProductStatus' => $orderProductStatus,
            'orderProductIds' => $orderProductIds,
            'orderProductCancellationReasonId' => $orderProductReasonId,
            '_token' => $csrfToken
        );
        $em = $this->getDoctrine()->getManager();
        $validOrderStatuses = $em->getRepository('YilinkerCoreBundle:Dispute')->getValidStatusesForDispute();
        $userOrderEntity = $em->getRepository('YilinkerCoreBundle:UserOrder')
                              ->getTransactionOrderByBuyer (
                                  $userEntity->getUserId(),
                                  null,
                                  null,
                                  $validOrderStatuses,
                                  null,
                                  null, null, null,
                                  OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER,
                                  null, null, 0, PHP_INT_MAX
                              );
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->createForm('create_new_case', null);
        $form->submit($formData);
        $responseMessage = null;
        $isSuccessful = false;
        $validOrderProductStatus = array(
            OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED => '',
            OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED => ''
        );

        if (!$form->isValid()) {
            $responseMessage = implode($formErrorService->throwInvalidFields($form), ' \n');
        }
        else if (sizeof($orderProductIds) === 0) {
            $responseMessage = 'Invalid Order Product';
        }
        else if (!isset($validOrderProductStatus[$orderProductStatus])) {
            $responseMessage = 'Invalid Order Product Status';
        }
        else if (count($userOrderEntity) == 0 || !in_array($transactionNumber,
             array_map(function($n){
                return $n['order_id'];
            }, $userOrderEntity))) {
            $responseMessage = 'Invalid transaction number/Invoice number';
        }
        else {
            $isSuccessful = true;
            $orderProductEntities = $em->getRepository('YilinkerCoreBundle:OrderProduct')->findByOrderProductId($orderProductIds);
            $disputeManager = $this->get('yilinker_core.service.dispute_manager');
            $disputeManager->addNewCase(
                                 $orderProductEntities,
                                 $this->getUser(),
                                 $description,
                                 $message,
                                 $orderProductStatus,
                                 $orderProductReasonId
                             );
        }

        return new JsonResponse(compact('responseMessage', 'isSuccessful'));
    }

    /**
     * Get Order Products by orderId
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderProductAction (Request $request)
    {
        $orderId = (int) $request->query->get('orderId', 0);
        $em = $this->getDoctrine()->getManager();
        $orderProducts = array();
        $userEntity = $this->getUser();

        if ($orderId !== 0) {
            $orderProducts = $em->getRepository('YilinkerCoreBundle:UserOrder')
                                ->getTransactionOrderProducts (
                                    $orderId,
                                    null,
                                    null,
                                    $userEntity->getUserId(),
                                    OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER
                                );
        }

        return new JsonResponse(compact('orderProducts'));
    }

    /**
     * Render left wing menu
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderLeftWingMenuAction($currentRoute = "")
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        return $this->render('YilinkerFrontendBundle:Profile:profile_left_wing_menu.html.twig', array(
            'user'         => $authenticatedUser,
            'currentRoute' => $currentRoute
        ));
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

    /**
     * Render Profile Document Action Markup
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileUploadDocumentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $document = new UserIdentificationCard();
        $form = $this->createForm('core_user_document', $document);        
        $isSuccessful = $request->get('action', false);
        $successMessage = $isSuccessful === "success" ? "Document successfully uploaded" : null;

        $documents = $em->getRepository('YilinkerCoreBundle:UserIdentificationCard')
                        ->findBy(array('user' => $authenticatedUser));

        if($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $document->setDateAdded(new \DateTime());
                $document->setUser($authenticatedUser);
                $em->persist($document);
                $em->flush();

                return $this->redirect(
                    $this->generateUrl('profile_upload_document',  array('action' => 'success')
                ));
            }
        }

        return $this->render('YilinkerFrontendBundle:Profile:profile_upload_document.html.twig', array(
            'documentForm'    => $form->createView(),
            'successMessage'  => $successMessage,
            'documents'       => $documents,
        ));
    }

    /**
     * Render Profile Notification Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileNotificationsAction()
    {

        return $this->render('YilinkerFrontendBundle:Profile:profile_notifications.html.twig');
    }

 }
