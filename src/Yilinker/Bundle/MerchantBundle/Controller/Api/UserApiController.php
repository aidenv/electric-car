<?php
namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use OAuth2\OAuth2ServerException;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use DateTime;
use Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType;

class UserApiController extends YilinkerBaseController
{
    /**
     * Retrieve the total sales report of a seller
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return  Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getUserSalesReportAction(Request $request)
    {
        $dateFrom = $request->get('dateFrom', null);
        $dateTo = $request->get('dateTo', null);
        $dateFrom = $dateFrom ? new DateTime($dateFrom." 00:00:00") : null;
        $dateTo = $dateTo ? new DateTime($dateTo." 23:59:59") : null;

        $entityManager = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $productService = $this->get("yilinker_core.service.product.product");
        if($authenticatedUser->getStore()->isAffiliate()){
            $vieweableProductStatuses = $productService->viewableAffiliateProductStatuses();
        }
        else{
            $vieweableProductStatuses = $productService->viewableSellerProductStatuses();
        }

        $translationService = $this->get("yilinker_core.translatable.listener");
        $country = $translationService->getCountry();
        $country = $this->get("doctrine.orm.entity_manager")
                        ->getRepository("YilinkerCoreBundle:Country")
                        ->findOneByCode($country);

        $productCount = $entityManager->getRepository("YilinkerCoreBundle:User")
                                      ->getUserUploadCount(
                                        $authenticatedUser->getUserId(),
                                        null,
                                        null,
                                        $vieweableProductStatuses,
                                        $country
                                    );

        $orderStatuses = $this->container->get('yilinker_core.service.transaction')
                              ->getOrderStatusesValid();
        $totalOrderCount = $entityManager->getRepository('YilinkerCoreBundle:UserOrder')
                                         ->getNumberOfOrdersBy(
                                             $authenticatedUser->getUserId(), null,
                                             null, null, null,
                                             $orderStatuses
                                         );
        $totalSales = $this->get('yilinker_core.service.transaction')
                           ->getSellerTotalSales( $authenticatedUser->getUserId(), null, null);
        $confirmedTransactionCountPerDay = $this->get('yilinker_core.service.transaction')
                                                ->getCountConfirmedSellerTransactionPerDay(
                                                    $authenticatedUser->getUserId(),
                                                    $dateFrom,
                                                    $dateTo
                                                );

        $cancelledTransactionCountPerDay = $this->get('yilinker_core.service.transaction')
                                                ->getCountCancelledSellerTransactionPerDay(
                                                    $authenticatedUser->getUserId(),
                                                    $dateFrom,
                                                    $dateTo
                                                );

        $response = array(
            'isSuccessful' => true,
            'message' => 'Sales report retrieved',
            'data' => array(
                'productCount'               => $productCount,
                'totalTransactionCount'      => $totalOrderCount,
                'totalSales'                 => number_format($totalSales, 2, '.', ','),
                'confirmedTransactionPerDay' => array_values($confirmedTransactionCountPerDay),
                'cancelledTransactionPerDay' => array_values($cancelledTransactionCountPerDay),
            ),
        );

        return new JsonResponse($response);
    }

    public function getQrCodeAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if(
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ){

            $authenticatedUser = $this->getAuthenticatedUser();

            if($authenticatedUser->getUserType() != User::USER_TYPE_SELLER){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Unauthorize.",
                    "data" => array()
                ), 401);
            }

            $assetsHelper = $this->container->get('templating.helper.assets');

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Qr Code.",
                "data" => array(
                    "qrcodeUrl" => $assetsHelper->getUrl($authenticatedUser->getStore()->getQrCodeLocation(), 'qr_code')
                )
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "User not found.",
            "data" => array()
        ), 404);
    }

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
    public function getUserInfoAction(Request $request)
    {
        $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');
        $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
        $entityManager = $this->getDoctrine()->getManager();
        $assetsHelper = $this->container->get('templating.helper.assets');
        $authenticatedUser = $this->getAuthenticatedUser();

        $store = $authenticatedUser->getStore();
        $image = $authenticatedUser->getPrimaryImage();
        $cover = $authenticatedUser->getPrimaryCoverPhoto();

        $profilePhoto = "";
        $profilePhotoFileName = "";
        $coverPhoto = "";
        $coverPhotoFileName = "";

        if($image){
            $profilePhoto = $assetsHelper->getUrl($image->getImageLocation(), 'user');
            $profilePhotoFileName = $image->getImageLocation(true);
        }
        if($cover){
            $coverPhoto = $assetsHelper->getUrl($cover->getImageLocation(), 'user');
            $coverPhotoFileName = $cover->getImageLocation(true);
        }

        $birthdate = null;
        if(!is_null($authenticatedUser->getBirthdate())){
            $birthdate = $authenticatedUser->getBirthdate()->format('M d, Y');
        }

        $isReseller = is_null($store) ? false : $store->getStoreType() == STORE::STORE_TYPE_RESELLER;
        $emailSubscription = $entityManager->getRepository("YilinkerCoreBundle:EmailNewsletterSubscription")
                                           ->findOneBy(array(
                                               'isActive'      => true,
                                               'userId'        => $authenticatedUser->getUserId(),
                                               'email'         => $authenticatedUser->getContactNumber(),
                                           ));
        $smsSubscription = $entityManager->getRepository("YilinkerCoreBundle:SmsNewsletterSubscription")
                                         ->findOneBy(array(
                                             'isActive'      => true,
                                             'userId'        => $authenticatedUser->getUserId(),
                                             'contactNumber' => $authenticatedUser->getEmail(),
                                         ));
        $specialty = null;
        $productCategory = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory")
                                         ->getUserSpecialty($authenticatedUser);
        if(!is_null($productCategory) && $productCategory){
            $specialty = $productCategory->getName();
        }

        $productService = $this->get("yilinker_core.service.product.product");
        if($authenticatedUser->getStore()->isAffiliate()){
            $vieweableProductStatuses = $productService->viewableAffiliateProductStatuses();
        }
        else{
            $vieweableProductStatuses = $productService->viewableSellerProductStatuses();
        }


        $translationService = $this->get("yilinker_core.translatable.listener");
        $country = $translationService->getCountry();
        $country = $this->get("doctrine.orm.entity_manager")
                        ->getRepository("YilinkerCoreBundle:Country")
                        ->findOneByCode($country);

        $productCount = $entityManager->getRepository("YilinkerCoreBundle:User")
                                      ->getUserUploadCount(
                                        $authenticatedUser->getUserId(),
                                        null,
                                        null,
                                        $vieweableProductStatuses,
                                        $country
        );

        $affiliateProductCount = $entityManager
                                   ->getRepository("YilinkerCoreBundle:InhouseProduct")
                                   ->searchBy(
                                        array('affiliate' => $authenticatedUser )
                                    )
                                   ->getCount();

        $saleableOrderStatuses  = $this->get('yilinker_core.service.transaction')
                                       ->getOrderStatusesValid();

        $totalOrderCount = $entityManager->getRepository('YilinkerCoreBundle:UserOrder')
                                         ->getNumberOfOrdersBy(
                                             $authenticatedUser->getUserId(),
                                             null, null, null, null,
                                             $saleableOrderStatuses
                                         );

        $unreadMessageCount = $entityManager->getRepository('YilinkerCoreBundle:Message')
                                            ->getCountUnonepenedMessagesByUser($authenticatedUser);

        $totalSales = $this->get('yilinker_core.service.transaction')
                           ->getSellerTotalSales($authenticatedUser->getUserId());

        $storeCategories = $this->get('yilinker_core.service.store_category_service')
                                ->getCategoryWithSelectedStoreCategory($authenticatedUser->getStore());
        $storeCategoriesArray = array();

        if (sizeof($storeCategories['data']) > 0) {

            foreach ($storeCategories['data'] as $storeCategory) {
                $storeCategoriesArray['categories'][] = array (
                    'productCategoryId' => $storeCategory['productCategory']->getProductCategoryId(),
                    'name' => $storeCategory['productCategory']->getName(),
                    'isSelected' => $storeCategory['isSelected']
                );
            }

            $storeCategoriesArray['hasSelected'] = $storeCategories['hasSelected'];
        }

        $referrerCode = "";
        $referrerName = "";
        $userReferrer = $authenticatedUser->getUserReferral();
        if ($userReferrer) {
            $referrerCode = $userReferrer->getReferrer()->getReferralCode();
            $referrerName = $userReferrer->getReferrer()->getFullName();
        }

        $validId = null;
        if($store && $authenticatedUser->getAccreditationApplication()){
            $legalDocumentType = $entityManager->getRepository("YilinkerCoreBundle:LegalDocumentType")
                                           ->find(LegalDocumentType::TYPE_VALID_ID);

            $legalDocument = $authenticatedUser->getAccreditationApplication()
                                         ->getLegalDocumentByType($legalDocumentType);

            $validId = $legalDocument? $legalDocument->getName() : null;
        }

        $accreditation = $this->getAccreditationApp();

        $earningGroup = $this->get('yilinker_core.service.earning.group');
        $totalEarnings = $earningGroup->getUserPoint($this->getUser());

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "User information retrieved",
            "data" => array(
                "userId"                => $authenticatedUser->getUserId(),
                "fullName"              => $authenticatedUser->getFullName(),
                "firstName"             => $authenticatedUser->getFirstName(),
                "lastName"              => $authenticatedUser->getLastName(),
                "email"                 => $authenticatedUser->getEmail(),
                "contactNumber"         => $authenticatedUser->getContactNumber(),
                "specialty"             => $specialty,
                "storeName"             => $store->getStoreName(),
                "storeDescription"      => $store->getStoreDescription(),
                "storeSlug"             => $store->getStoreSlug(),
                "profilePhoto"          => $profilePhoto,
                "profilePhotoFileName"  => $profilePhotoFileName,
                "coverPhoto"            => $coverPhoto,
                "coverPhotoFileName"    => $coverPhotoFileName,
                "bankAccount"           => $bankAccountService->getDefaultBankAccount($authenticatedUser),
                "userAddress"           => $userAddressService->getDefaultUserAddress($authenticatedUser),
                "productCount"          => $authenticatedUser->isAffiliate() ? (int)$affiliateProductCount : $productCount,
                "transactionCount"      => $totalOrderCount,
                "totalSales"            => $totalSales,
                "isReseller"            => $isReseller,
                "slugChanged"           => $store->getSlugChanged(),
                "isMobileVerified"      => $authenticatedUser->getIsMobileVerified(),
                "isEmailVerified"       => $authenticatedUser->getIsEmailVerified(),
                "isEmailSubscribed"     => $emailSubscription !== null,
                "isSmsSubscribed"       => $smsSubscription !== null,
                "storeCategory"         => $storeCategoriesArray,
                "tin"                   => $authenticatedUser->getTin() ? $authenticatedUser->getTin() : "",
                "messageCount"          => $unreadMessageCount,
                "referralCode"          => (string) $authenticatedUser->getReferralCode(),
                "referrerCode"          => (string) $referrerCode,
                "referrerName"          => (string) $referrerName,
                "validId"               => $validId,
                "isBankEditable"        => $accreditation['isBankEditable'] == 0 ? false : true,
                "isBusinessEditable"    => $accreditation['isBusinessEditable'] == 0 ? false : true,
                "isLegalDocsEditable"   => $accreditation['isLegalDocsEditable'],
                "validIdMessage"        => $accreditation['validIdMessage'],
                "totalPoints"           => number_format($totalEarnings,2),
            )
        ), 200);
    }

    /**
     * Update / Create StoreCategory
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitStoreCategoryAction (Request $request)
    {
        $selectedStoreCategoryIds = json_decode($request->request->get('selectedStoreCategoryIds', null), true);
        $authenticatedUser = $this->getAuthenticatedUser();
        $response = array (
            'isSuccessful' => true,
            'data' => null,
            'message' => null
        );

        if ($selectedStoreCategoryIds === null || !is_array($selectedStoreCategoryIds) || sizeof($selectedStoreCategoryIds) == 0) {
            $response = array (
                'isSuccessful' => false,
                'data' => null,
                'message' => 'Invalid Product Category.'
            );
        }
        else if (!($authenticatedUser instanceof User)) {
            $response = array (
                'isSuccessful' => false,
                'data' => null,
                'message' => 'Invalid Access.'
            );
        }

        /**
         * Persist
         */
        if ($response['isSuccessful'] === true) {
            $em = $this->getDoctrine()->getManager();
            $storeEntity = $em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($authenticatedUser);
            $this->get('yilinker_core.service.store_category_service')
                 ->processSelectedCategory($storeEntity, $selectedStoreCategoryIds);
        }

        return new JsonResponse($response);
    }

    /**
     * Update the user information
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="User",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when error occured in updating or invalid data",
     *         401="Returned when the user is not authorized to update information",
     *         404={
     *           "Returned when the user is not found"
     *         }
     *     },
     *     input={
     *         "class"="Yilinker\Bundle\MerchantBundle\Form\Type\Api\UpdateUserInfoFormType",
     *         "name"=""
     *     },
     *     parameters={
     *         {"name"="referralCode", "dataType"="string", "required"=false, "description"="referral code"},
     *     }
     * )
     */
    public function updateUserInfoAction(Request $request)
    {
        $data = array();
        $referralCode = $request->request->get('referralCode', '');

        $this->assignIfNotNull($data, 'profilePhoto', $request->files->get('profilePhoto', null));
        $this->assignIfNotNull($data, 'coverPhoto', $request->files->get('coverPhoto', null));
        $this->assignIfNotNull($data, 'firstName', $request->request->get('firstName', null));
        $this->assignIfNotNull($data, 'lastName', $request->request->get('lastName', null));
        $this->assignIfNotNull($data, 'nickname', $request->request->get('nickname', null));
        $this->assignIfNotNull($data, 'gender', $request->request->get('gender', null));
        $this->assignIfNotNull($data, 'birthdate', $request->request->get('birthdate', null));
        $this->assignIfNotNull($data, 'storeName', $request->request->get('storeName', null));
        $this->assignIfNotNull($data, 'storeDescription', $request->request->get('storeDescription', null));

        $categoryIds = json_decode($request->request->get('categoryIds', null), true);

        if(!json_last_error() == JSON_ERROR_NONE){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid JSON format for categories",
                "data" => array(
                    "errors" => array("Invalid category")
                )
            ), 400);
        }

        $this->assignIfNotNull($data, 'categoryIds', $categoryIds);

        $slug = $request->request->get('storeSlug', null);
        if(!is_null($slug) AND trim($slug) != ""){
            $this->assignIfNotNull($data, 'storeSlug', $slug);
        }

        $accountManager = $this->get('yilinker_merchant.service.user.account_manager');
        $coreAccountManager = $this->get('yilinker_core.service.account_manager');
        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        if(count($data)){
            $authenticatedUser = $this->getAuthenticatedUser();

            if($authenticatedUser->getUserType() !== 1){
                $formErrorService->throwCustomErrorResponse(array(), "User not found.");
            }

            $em = $this->getDoctrine()->getManager();
            $productCategoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');

            $rootNode = $productCategoryRepository->find(ProductCategory::ROOT_CATEGORY_ID);
            $productCategories = $productCategoryRepository->loadParentProductCategories($rootNode);

            $options = array(
                "user" => $authenticatedUser,
                "productCategories" => $productCategories
            );

            $updateReferralCode = strlen($referralCode) > 0 && !$authenticatedUser->getUserReferral();

            if (strlen($referralCode) && $authenticatedUser->getUserReferral()) {
                return $formErrorService->throwCustomErrorResponse(['You can only refer once per account.'], "Invalid inputs.");
            }

            $form = $this->transactForm('update_merchant_info', null, $data, $options);

            if($form->isValid()){

                if(array_key_exists('categoryIds', $data)){
                    $data["categoryIds"] = $form->getData()["categoryIds"];
                }

                if ($updateReferralCode) {
                    $processReferralCode = $coreAccountManager->processReferralCode($referralCode, $authenticatedUser);
                    if ((bool) $processReferralCode['isSuccessful'] === false) {
                        return $formErrorService->throwCustomErrorResponse([$processReferralCode['message']], "Invalid inputs.");
                    }
                }

                return $accountManager->updateUserInfo($authenticatedUser, $data);
            }
            else{
                $errors = $formErrorService->throwInvalidFields($form);
                return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
            }
        }

        return $formErrorService->throwNoFieldsSupplied();
    }


    protected function getAccreditationApp()
    {
        $user = $this->getAuthenticatedUser();
        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');

        if(!$user->getAccreditationApplication()){
            $applicationManager->createApplication(
                $user,
                "",
                $user->getStore()->getStoreType(),
                true
            );
        }

        $applicationDetails = $applicationManager->getApplicationDetailsBySeller($user);

        $accreditation = $applicationDetails['accreditationApplication'];

        $validIdMessage = "";
        if (isset($applicationDetails['remarkArray'][ApplicationRemarkType::TYPE_VALID_ID]) ) {
            $v = end($applicationDetails['remarkArray'][ApplicationRemarkType::TYPE_VALID_ID]);
            $validIdMessage = $v->getMessage();
        }

        return array(
            'isBankEditable' => $accreditation->getIsBankEditable(),
            'isBusinessEditable' => $accreditation->getIsBusinessEditable(),
            'isLegalDocsEditable' => count($applicationDetails['legalDocuments']) == 0 || $applicationDetails['isLegalDocsEditable'],
            'validIdMessage' => $validIdMessage
        );
}

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $postData
     * @return \Symfony\Component\Form\Form
     */
    private function transactForm($formType, $entity, $postData, $options = array())
    {
        $form = $this->createForm($formType, $entity, $options);

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

    public function tokenAction(Request $request)
    {
        $oauthServer = $this->get('fos_oauth_server.server');
        try {
            $request->request->set('version', $request->get('version'));

            $response = $oauthServer->grantAccessToken($request);
            $content = $response->getContent();
            $jsonContent = json_decode($content, true);
            $token = $jsonContent['access_token'];

            $accessToken = $oauthServer->verifyAccessToken($token);

            return $response;
        }
        catch (OAuth2ServerException $e) {
            return $e->getHttpResponse();
        }
    }

}
