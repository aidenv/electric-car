<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use stdClass;
use Symfony\Component\Form\FormError;
use Yilinker\Bundle\CoreBundle\Entity\Location;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OAuth2\OAuth2ServerException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Exception;

class UserApiController extends Controller
{
    /**
     * Get current authenticated user information
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="User",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Invalid parameter request",
     *         401="Unauthorized Request",
     *     },
     * )
     */
    public function getUserDetailsAction()
    {
        $assetsHelper = $this->container->get('templating.helper.assets');
        $authenticatedUser = $this->get('security.token_storage')->getToken()->getUser();

        if(is_null($authenticatedUser)){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "User not found",
                "data" => array()
            ), 404);
        }

        $image = $authenticatedUser->getPrimaryImage();
        $cover = $authenticatedUser->getPrimaryCoverPhoto();
        $permanentAddress = $authenticatedUser->getDefaultAddress();
        $location = null;
        $locationDetails = array();
        if($permanentAddress){
            $location = $permanentAddress->getLocation();
            if(!is_null($location)){
                $locationDetails = $location->getLocalizedLocationTree(true);
            }
        }

        $imageUrl = $image ? $assetsHelper->getUrl($image->getImageLocation(), 'user') : "";
        $coverUrl = $cover ? $assetsHelper->getUrl($cover->getImageLocation(), 'user') : "";

        $birthdate = null;
        if(!is_null($birthdate)){
            $birthdate = $authenticatedUser->getBirthdate()->format('M d, Y');
        }

        $entityManager = $this->get('doctrine')->getManager();
        $cartService = $this->get('yilinker_front_end.service.cart')->apiMode(true);
        $saleableOrderStatuses  = $this->get('yilinker_core.service.transaction')
                                       ->getOrderStatusesValid();
        $transactionCount = $entityManager->getRepository('YilinkerCoreBundle:UserOrder')
                                          ->getNumberOfOrdersBy(
                                              null, $authenticatedUser->getUserId(), null,
                                              null, null, $saleableOrderStatuses,
                                              null, null, null, null, null, null, null, true
                                          );

        $unreadMessageCount = $entityManager->getRepository('YilinkerCoreBundle:Message')
                                            ->getCountUnonepenedMessagesByUser($authenticatedUser);

        $followeeCount = $entityManager->getRepository('YilinkerCoreBundle:UserFollow')
                                       ->getNumberOfFollowedSellers($authenticatedUser);
        $cart = $cartService->getCart(true);
        $wishlist = $cartService->getWishlist(true);

        $emailSubscription = $entityManager->getRepository("YilinkerCoreBundle:EmailNewsletterSubscription")
                                           ->findOneBy(array(
                                               'isActive' => true,
                                               'userId' => $authenticatedUser->getUserId(),
                                           ));
        $smsSubscription = $entityManager->getRepository("YilinkerCoreBundle:SmsNewsletterSubscription")
                                         ->findOneBy(array(
                                             'isActive' => true,
                                             'userId' => $authenticatedUser->getUserId(),
                                         ));

        $document = $entityManager->getRepository('YilinkerCoreBundle:UserIdentificationCard')
                                   ->getMostRecentId($authenticatedUser);
        $userDocuments = new stdClass();
        if($document){
            $userDocuments = array(
                'id'        => $document->getUserIdentificationCardId(),
                'file'      => $assetsHelper->getUrl($document->getFilepath(), 'user_document'),
                'dateAdded' => $document->getDateAdded(),
            );
        }

        $referrerCode = "";
        $referrerName = "";
        $userReferrer = $authenticatedUser->getUserReferral();
        if ($userReferrer) {
            $referrerCode = $userReferrer->getReferrer()->getReferralCode();
            $referrerName = $userReferrer->getReferrer()->getFullName();
        }

        $earningGroup = $this->get('yilinker_core.service.earning.group');
        $totalEarnings = $earningGroup->getUserPoint($this->getUser());

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Retrieved user details",
            "data" => array(
                "userId" => $authenticatedUser->getUserId(),
                "fullName" => $authenticatedUser->getFullName(),
                "firstName" => $authenticatedUser->getFirstName(),
                "lastName" => $authenticatedUser->getLastName(),
                "email" => $authenticatedUser->getEmail(),
                "contactNumber" => $authenticatedUser->getContactNumber(),
                "profileImageUrl" => $imageUrl,
                "coverPhoto" => $coverUrl,
                "gender" => $authenticatedUser->getGender(),
                "language" =>  $authenticatedUser->getLanguage() ? $authenticatedUser->getLanguage()->toArray() : null,
                "country" => $authenticatedUser->getCountry() ? $this->get('yilinker_core.service.location.location')->countryDetail($authenticatedUser->getCountry()) : null,
                "birthdate" => $birthdate,
                "address" => array(
                    "userAddressId" => $permanentAddress? $permanentAddress->getUserAddressId() : null,
                    "locationId" => !is_null($location)? $location->getLocationId() : null,
                    "title" => $permanentAddress? $permanentAddress->getTitle() : null,
                    "unitNumber" => $permanentAddress? $permanentAddress->getUnitNumber() : null,
                    "buildingName" => $permanentAddress? $permanentAddress->getBuildingName() : null,
                    "streetNumber" => $permanentAddress? $permanentAddress->getStreetNumber() : null,
                    "streetName" => $permanentAddress? $permanentAddress->getStreetName() : null,
                    "subdivision" => $permanentAddress? $permanentAddress->getSubdivision() : null,
                    "zipCode" => $permanentAddress? $permanentAddress->getZipCode() : null,
                    "streetAddress" => $permanentAddress? $permanentAddress->getStreetAddress() : null,
                    "provinceId" => array_key_exists('province', $locationDetails) ? $locationDetails["province"]["locationId"] : null,
                    "province" => array_key_exists('province', $locationDetails) ? $locationDetails["province"]["location"] : null,
                    "cityId" => array_key_exists('city', $locationDetails) ? $locationDetails["city"]["locationId"] : null,
                    "city" => array_key_exists('city', $locationDetails) ? $locationDetails["city"]["location"] : null,
                    "barangayId" => array_key_exists('barangay', $locationDetails) ? $locationDetails["barangay"]["locationId"] : null,
                    "barangay" =>  array_key_exists('barangay', $locationDetails) ? $locationDetails["barangay"]["location"] : null,
                    "longitude" => $permanentAddress? $permanentAddress->getLongitude() : null,
                    "latitude" => $permanentAddress? $permanentAddress->getLatitude() : null,
                    "landline" => $permanentAddress? $permanentAddress->getLandline() : null,
                    "fullLocation" => $permanentAddress? $permanentAddress->getAddressString() : null,
                    "isDefault" => $permanentAddress? $permanentAddress->getIsDefault() : null,
                ),
                "transactionCount" => $transactionCount,
                "wishlistCount" => $wishlist['total'],
                "cartCount" => $cart['total'],
                "messageCount" => $unreadMessageCount,
                "followingCount" => $followeeCount,
                "isEmailSubscribed" => $emailSubscription !== null,
                "isSmsSubscribed"   => $smsSubscription !== null,
                "isEmailVerified"   => $authenticatedUser->getIsEmailVerified(),
                "isMobileVerified"  => $authenticatedUser->getIsMobileVerified(),
                'userDocuments'     => $userDocuments,
                'referralCode' => (string) $authenticatedUser->getReferralCode(),
                'referrerCode' => (string) $referrerCode,
                'referrerName' => (string) $referrerName,
                "totalPoints"  => number_format($totalEarnings,2),
            )
        ), 200);

    }

    /**
     * Update the user information
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when error occured in updating or invalid data",
     *         401="Returned when the user is not authorized to update information",
     *         404={
     *           "Returned when the user is not found"
     *         }
     *     },
     *     input={
     *         "class"="Yilinker\Bundle\FrontendBundle\Form\Type\Api\UpdateUserInfoFormType",
     *         "name"=""
     *     },
     *     parameters={
     *         {"name"="referralCode", "dataType"="string", "required"=false, "description"="referral code"},
     *         {"name"="languageId", "dataType"="int", "required"=false, "description"="LanguageId"},
     *         {"name"="countryId", "dataType"="int", "required"=false, "description"="CountryId"},
     *     },
     *     section="User"
     * )
     */
    public function updateUserInfoAction(Request $request)
    {
        $data = array();
        $authenticatedUser = $this->getAuthenticatedUser();
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        //user tbl
        $this->assignIfNotNull($data, 'profilePhoto', $request->files->get('profilePhoto', null));
        $this->assignIfNotNull($data, 'coverPhoto', $request->files->get('coverPhoto', null));
        $this->assignIfNotNull($data, 'userDocument', $request->files->get('userDocument', null));
        $this->assignIfNotNull($data, 'firstName', $request->request->get('firstName', null));
        $this->assignIfNotNull($data, 'lastName', $request->request->get('lastName', null));
        $this->assignIfNotNull($data, 'gender', $request->request->get('gender', null));
        $this->assignIfNotNull($data, 'nickname', $request->request->get('nickname', null));
        $this->assignIfNotNull($data, 'birthdate', $request->request->get('birthdate', null));
        $this->assignIfNotNull($data, 'languageId', $request->request->get('languageId', null));
        $this->assignIfNotNull($data, 'countryId', $request->request->get('countryId', null));

        $contactNumber = $request->request->get('contactNumber', null);

        if($authenticatedUser->getContactNumber() != $contactNumber){
            $this->assignIfNotNull($data, 'contactNumber', $contactNumber);
        }

        $referralCode = $request->request->get('referralCode', '');
        $oldPassword = $request->request->get('oldPassword', null);
        $newPassword = $request->request->get('newPassword', null);
        $newPasswordConfirm = $request->request->get('newPasswordConfirm', null);

        if(!is_null($oldPassword) OR !is_null($newPassword)){

            $encoder = $this->get('security.encoder_factory')->getEncoder($authenticatedUser);
            $isPasswordValid = $encoder->isPasswordValid($authenticatedUser->getPassword(), $oldPassword, null);
            $isOldPassword = $encoder->isPasswordValid($authenticatedUser->getPassword(), $newPassword, null);

            if($isOldPassword){
                return $formErrorService->throwCustomErrorResponse(array("Password is same to the old password."), "Invalid password.");
            }

            if(!$isPasswordValid){
                return $formErrorService->throwCustomErrorResponse(array("Invalid password."), "Invalid password.");
            }

            $this->assignIfNotNull($data, 'plainPassword', array(
                "first" => $newPassword,
                "second" => $newPasswordConfirm
            ));
        }

        $slug = $request->request->get('slug', null);

        if($authenticatedUser->getSlug() != $slug AND !is_null($slug) AND trim($slug) != ""){
            if($authenticatedUser->getSlugChanged()){
                return $formErrorService->throwCustomErrorResponse(array("Slug can only be changed once."), "Slug can only be changed once.");
            }

            $this->assignIfNotNull($data, 'slug', $slug);
        }

        $userAddressId = $request->request->get('userAddressId', 0);
        $locationId = $request->request->get('locationId', 0);

        $request->request->remove('userAddressId');

        $accountManager = $this->get('yilinker_front_end.service.user.account_manager');
        $coreAccountManager = $this->get('yilinker_core.service.account_manager');

        if(count($data)){

            if($authenticatedUser->getUserType() !== User::USER_TYPE_BUYER){
                return $formErrorService->throwCustomErrorResponse(array(), "User not found.");
            }

            $form = $this->transactForm('update_buyer_info', null, $data);

            $updateReferralCode = strlen($referralCode) > 0 && !$authenticatedUser->getUserReferral();

            if (strlen($referralCode) && $authenticatedUser->getUserReferral()) {
                return $formErrorService->throwCustomErrorResponse(['You can only refer once per account.'], "Invalid inputs.");
            }

            if($form->isValid()){
                if(!is_null($oldPassword) OR !is_null($newPassword)){
                    $this->assignIfNotNull($data, 'oldPassword', $oldPassword);
                }

                if ($updateReferralCode) {
                    $processReferralCode = $coreAccountManager->processReferralCode($referralCode, $authenticatedUser);
                    if ((bool) $processReferralCode['isSuccessful'] === false) {
                        return $formErrorService->throwCustomErrorResponse([$processReferralCode['message']], "Invalid inputs.");
                    }
                }

                return $accountManager->updateUserInfo($authenticatedUser, $data, $userAddressId, $locationId, $slug);
            }
            else{
                $errors = $formErrorService->throwInvalidFields($form);
                return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
            }
        }

        return $formErrorService->throwNoFieldsSupplied();
    }

    /**
     * Handles Request For Register User
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerUserAction (Request $request)
    {
        $entityManager = $this->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');

        $formData = [
            'email' => $request->get('email', null),
            'plainPassword' => array(
                "first" => $request->get('password', null),
                "second" => $request->get('password', null)
            ),
            'firstName' => $request->get('firstName', null),
            'lastName' => $request->get('lastName', null),
            'contactNumber' => $request->get('contactNumber', null),
        ];
        
        $referralCode = $request->get("referralCode", "");

        $guest = $userRepository->findGuestByEmailContact($request->get('email', null), $request->get('contactNumber', null));

        $form = $this->createForm('core_v1_user_add', new User(), array(
            'csrf_protection' => false,
            'excludeUserId' => !is_null($guest)? $guest->getUserId() : null
        ));

        $form->submit($formData);

        $isSuccessful = false;
        if ($form->isValid()) {

            $entityManager->beginTransaction();

            try{
            
                $referrerEntity = false;

                if ($referralCode !== '') {
                    $referrerEntity = $entityManager->getRepository('YilinkerCoreBundle:User')
                                                    ->findOneBy(array('referralCode' => $referralCode));
                    if (!$referrerEntity) {
                        throw new Exception("Referral code does not exist");
                    }

                }

                $accountManager = $this->get('yilinker_core.service.account_manager');
                $user = $form->getData();

                if(!is_null($guest)){
                    $guest->setFirstName($user->getFirstname())
                          ->setLastName($user->getLastName())
                          ->setPlainPassword($user->getPlainPassword())
                          ->setContactNumber($user->getContactNumber())
                          ->setFailedLoginCount(0)
                          ->setIsEmailVerified(false)
                          ->setIsMobileVerified(false)
                          ->setUserType(User::USER_TYPE_BUYER);

                    $entityManager->flush();
                    $user = $guest;
                }
                else{
                    $accountManager->registerUser($user, false);
                }

                if ($referrerEntity) {
                    $accountManager->addReferrer($user, $referrerEntity);
                }

                $jwtService = $this->get("yilinker_core.service.jwt_manager");
                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

                $ylaService = $this->get("yilinker_core.service.yla_service");
                $ylaService->setEndpoint(false);

                $response = $ylaService->sendRequest("user_create", "post", array("request" => $request));

                if(is_array($response) && array_key_exists("isSuccessful", $response) && $response["isSuccessful"]){
                    $user->setAccountId($response["data"]["userId"]);
                }

                $slug = $accountManager->generateUniqueSlug($user);

                $user->setSlug($slug);
                $user->setIsActive(true);
                $user->setRegistrationType(1);
                $entityManager->flush();

                $authService = $this->get('yilinker_core.security.authentication');
                $authService->authenticateUser($user);

                $entityManager->commit();
            }
            catch(Exception $e){
                $entityManager->rollback();

                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Registration failed.",
                    "data" => array(
                        "errors" => array($e->getMessage())
                    )
                ), 400);
            }
            
            $isSuccessful = true;
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'message' => $isSuccessful ? "" : $form->getErrors(true)[0]->getMessage(),
            'data' => array(),
        ));
    }

    public function tokenAction(Request $request)
    {
        $oauthServer = $this->get('fos_oauth_server.server');
        try {
            $response = $oauthServer->grantAccessToken($request);
            $content = $response->getContent();
            $jsonContent = json_decode($content, true);
            $token = $jsonContent['access_token'];

            $accessToken = $oauthServer->verifyAccessToken($token);
            $user = $accessToken->getUser();

            $cartService = $this->container->get('yilinker_front_end.service.cart');
            $cartSession = $cartService->cartSessionToDB($user);
            $cartItems = $this->get('session')->get('checkout/cartItems');
            $this->get('session')->remove('checkout');

            if ($cartItems) {
                $dbCartItems = array();
                foreach ($cartItems as $itemId) {
                    if (array_key_exists($itemId, $cartSession)) {
                        $dbCartItems[] = $cartSession[$itemId]->getId();
                    }
                }
                $this->get('session')->set('checkout/cartItems', $dbCartItems);
            }

            return $response;
        } catch (OAuth2ServerException $e) {
            return $e->getHttpResponse();
        }
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
