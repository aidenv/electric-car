<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccountType;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AccreditationController
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class AccreditationController extends Controller
{

    /**
     * Render Accreditation Page
     */
    public function renderAccreditationAction()
    {
        $this->checkRoleChange();
        $authenticatedUser = $this->getAuthenticatedUser();

        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
        $applicationDetails = $applicationManager->getApplicationDetailsBySeller($authenticatedUser);
        $baseUri = $this->getParameter('frontend_hostname');

        $response = compact (
            'applicationDetails',
            'baseUri'
        );

        return $this->render('YilinkerMerchantBundle:Accreditation:accreditation_summary.html.twig', $response);
    }

    /**
     * Renders Business Information Page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBusinessInformationAction ()
    {
        $this->checkRoleChange();
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();

        $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
        $userAddresses = $userAddressService->getUserAddresses($authenticatedUser);

        $locationService = $this->get('yilinker_core.service.location.location');
        $provinces = $locationService->getAll(LocationType::LOCATION_TYPE_PROVINCE, true);

        $storeEntity = $em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($authenticatedUser);

        $accreditationApplication = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($authenticatedUser);

        $socialMediaAccounts = $em->getRepository('YilinkerCoreBundle:UserSocialMediaAccount')->getUserSocialMediaAccounts($authenticatedUser);
        $hasOnlineStore = false;

        $userOccupation = $em->getRepository('YilinkerCoreBundle:UserOccupation')->findOneByUser($authenticatedUser);

        foreach ($socialMediaAccounts as $socialMediaAccount) {

            if ($socialMediaAccount['url'] !== '') {
                $hasOnlineStore = true;
                break;
            }

        }

        if ($accreditationApplication && $accreditationApplication->getBusinessWebsiteUrl() !== '') {
            $hasOnlineStore = true;
        }

        if ( $accreditationApplication && (bool) $accreditationApplication->getIsBusinessEditable() === false) {
            return $this->redirect($this->generateUrl('merchant_accreditation'));
        }

        $storeCategories = $this->get('yilinker_core.service.store_category_service')
                                ->getCategoryWithSelectedStoreCategory($storeEntity)['data'];

        $baseUri = $this->getParameter('frontend_hostname');

        $data = compact (
            'authenticatedUser',
            'userAddresses',
            'provinces',
            'storeEntity',
            'socialMediaAccounts',
            'hasOnlineStore',
            'accreditationApplication',
            'userOccupation',
            'storeCategories',
            'baseUri'
        );

        return $this->render('YilinkerMerchantBundle:Accreditation:accreditation_business_information.html.twig', $data);
    }

    /**
     * Render Bank Information Page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBankInformationAction ()
    {
        $this->checkRoleChange();
        $authenticatedUser = $this->getAuthenticatedUser();

        $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');
        $bankAccounts = $bankAccountService->getBankAccounts($authenticatedUser);

        $bankService = $this->get('yilinker_core.service.bank.bank');
        $banks = $bankService->getEnabledBanks();

        $em = $this->getDoctrine()->getManager();
        $accreditationApplication = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($authenticatedUser);

        if ( ($accreditationApplication === '' || $accreditationApplication === null ) && (bool) $accreditationApplication->getIsBankEditable() === false) {
            return $this->redirect($this->generateUrl('merchant_accreditation'));
        }

        $data = compact (
            'authenticatedUser',
            'bankAccounts',
            'banks'
        );

        return $this->render('YilinkerMerchantBundle:Accreditation:accreditation_bank_information.html.twig', $data);
    }

    /**
     * Render Legal Documents Page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderLegalDocumentsAction ()
    {
        $this->checkRoleChange();
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $accreditationApplicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($authenticatedUser);

        if (!($accreditationApplicationEntity instanceof AccreditationApplication)) {
            return $this->redirectToRoute('merchant_accreditation');
        }

        $legalDocuments = $em->getRepository('YilinkerCoreBundle:LegalDocument')
                             ->findByAccreditationApplication($accreditationApplicationEntity->getAccreditationApplicationId());

        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');

        if (sizeof($legalDocuments) > 0 && !$applicationManager->isLegalDocumentEditable($legalDocuments)) {
            return $this->redirect($this->generateUrl('merchant_accreditation'));
        }

        $editableFiles = $applicationManager->getEditableApplication($accreditationApplicationEntity, $legalDocuments);

        $data = compact (
            'accreditationApplicationEntity',
            'authenticatedUser',
            'legalDocuments',
            'editableFiles'
        );

        if ( (int) $accreditationApplicationEntity->getSellerType() === AccreditationApplication::SELLER_TYPE_MERCHANT) {
            return $this->render('YilinkerMerchantBundle:Accreditation:accreditation_legal_documents.html.twig', $data);
        }
        else {
            $data['validIds'] = array (
                array (
                    'id' => LegalDocumentType::TYPE_SSS,
                    'name' => 'SSS'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_PAG_IBIG,
                    'name' => 'PAG IBIG'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_POSTAL,
                    'name' => 'Postal'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_PASSPORT,
                    'name' => 'Passport'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_DRIVERS_LICENSE,
                    'name' => 'Driver\'s License'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_PRC,
                    'name' => 'PRC'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_VOTERS_ID,
                    'name' => 'Voter\'s ID'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_SCHOOL_ID,
                    'name' => 'School ID'
                ),
                array (
                    'id' => LegalDocumentType::TYPE_TIN,
                    'name' => 'TIN'
                )
            );
            return $this->render('YilinkerMerchantBundle:Accreditation:accreditation_legal_documents_affiliate.html.twig', $data);
        }

    }

    /**
     * Handles Submission of legal Docs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitLegalDocumentsAction (Request $request)
    {
        $isSuccessful = true;
        $message = null;

        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $accreditationApplicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($authenticatedUser);
        $dtiPermitFile = $request->files->get('dtiPermit', null);
        $mayorsPermitFile = $request->files->get('mayorsPermit', null);
        $birPermitFile = $request->files->get('birPermit', null);
        $otherFile = $request->files->get('otherFile');
        $isUpdate = $request->request->get('isUpdate');
        $isMerchant = (int) $request->request->get('isMerchant', AccreditationApplication::SELLER_TYPE_MERCHANT);
        $existingOtherLegalDocs = json_decode($request->request->get('otherFileArray', '{}'), true);

        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        if (intval($_SERVER['CONTENT_LENGTH']) > 0 && count($_POST) === 0 ) {
            $isSuccessful = false;
            $message = 'Total file upload max size exceed, Files are too large.';
        }

        if ( (int) $isMerchant !== AccreditationApplication::SELLER_TYPE_MERCHANT && $isSuccessful === true) {
            $isSuccessful = false;
            $message = 'Invalid Access.';
        }
        else if ( (int) $isUpdate === 0 && (
                !($dtiPermitFile instanceof File) ||
                !($birPermitFile instanceof File)
            ) && $isSuccessful === true
        ) {
            $isSuccessful = false;
            $message = 'File is not a valid file.';
        }
        else if ($dtiPermitFile !== null && $isSuccessful === true) {
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formData = array (
                'images' => array ($dtiPermitFile)
            );
            $formFiles->submit($formData);

            if (!($dtiPermitFile instanceof File)) {
                $isSuccessful = false;
                $message = 'DTI Permit is not a valid file.';
            }
            else if (!$formFiles->isValid()) {
                $isSuccessful = false;
                $message =  implode($formErrorService->throwInvalidFields($formFiles), ' \n');
            }

        }

        if ($mayorsPermitFile !== null && $isSuccessful === true) {
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formData = array (
                'images' => array ($mayorsPermitFile)
            );
            $formFiles->submit($formData);

            if (!($mayorsPermitFile instanceof File)) {
                $isSuccessful = false;
                $message = 'Mayors Permit is not a valid file.';
            }
            else if (!$formFiles->isValid()) {
                $isSuccessful = false;
                $message =  implode($formErrorService->throwInvalidFields($formFiles), ' \n');
            }

        }

        if ($birPermitFile !== null && $isSuccessful === true) {
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formData = array (
                'images' => array ($birPermitFile)
            );
            $formFiles->submit($formData);

            if (!($birPermitFile instanceof File)) {
                $isSuccessful = false;
                $message = 'BIR Permit is not a valid file.';
            }
            else if (!$formFiles->isValid()) {
                $isSuccessful = false;
                $message =  implode($formErrorService->throwInvalidFields($formFiles), ' \n');
            }

        }

        if ($otherFile !== null && $isSuccessful === true) {
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formData = array (
                'images' => $otherFile
            );
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formFiles->submit($formData);

            if (!$formFiles->isValid()) {
                $isSuccessful = false;
                $message =  implode($formErrorService->throwInvalidFields($formFiles), ' \n');
            }
        }
        else if (!($authenticatedUser instanceof User)) {
            $isSuccessful = false;
            $message = 'Login to continue.';
        }
        else if (!($accreditationApplicationEntity instanceof AccreditationApplication)) {
            $isSuccessful = false;
            $message = 'Kindly Proceed to step one';
        }

        /**
         * Persist
         */
        if ($isSuccessful === true) {
            $userId = $authenticatedUser->getUserId();

            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $documentUploader = $this->get('yilinker_core.service.upload.document_uploader');

            if ($dtiPermitFile !== null) {
                $legalDocumentTypeDti = LegalDocumentType::TYPE_DTI_SEC_PERMIT;
                $fileNameDti = trim($userId . '_' . rand(1, 9999). '_' . $legalDocumentTypeDti . '_' . strtotime(Carbon::now()));
                $dtiPermitFileName = $documentUploader->uploadFile ($dtiPermitFile, $userId, $fileNameDti);
                $applicationManager->submitLegalDocument ($accreditationApplicationEntity, $legalDocumentTypeDti, $dtiPermitFileName);
            }

            if ($mayorsPermitFile !== null) {
                $legalDocumentTypeMayorsPermit = LegalDocumentType::TYPE_MAYORS_PERMIT;
                $fileNameMayors = trim($userId . '_' . rand(1, 9999). '_' . $legalDocumentTypeMayorsPermit . '_' . strtotime(Carbon::now()));
                $mayorsPermitFileName = $documentUploader->uploadFile ($mayorsPermitFile, $userId, $fileNameMayors);
                $applicationManager->submitLegalDocument ($accreditationApplicationEntity, $legalDocumentTypeMayorsPermit, $mayorsPermitFileName);
            }

            if ($birPermitFile !== null) {
                $legalDocumentTypeBir = LegalDocumentType::TYPE_BIR_PERMIT;
                $fileNameBir = trim($userId . '_' . rand(1, 9999). '_' . $legalDocumentTypeBir . '_' . strtotime(Carbon::now()));
                $birPermitFileName = $documentUploader->uploadFile ($birPermitFile, $userId, $fileNameBir);
                $applicationManager->submitLegalDocument ($accreditationApplicationEntity, $legalDocumentTypeBir, $birPermitFileName);
            }

            if ($otherFile !== null) {
                $legalDocumentTypeOther = LegalDocumentType::TYPE_OTHERS;

                foreach ($otherFile as $otherFile) {
                    $fileNameOther = trim($userId . '_' . rand(1, 9999). '_' . $legalDocumentTypeOther . '_' . strtotime(Carbon::now()));
                    $otherFileFileName = $documentUploader->uploadFile ($otherFile, $userId, $fileNameOther);
                    $applicationManager->submitLegalDocument ($accreditationApplicationEntity, $legalDocumentTypeOther, $otherFileFileName);
                }

            }

            if ( (int) $isUpdate === 1 && sizeof($existingOtherLegalDocs) > 0) {
                $legalDocumentRepository = $em->getRepository('YilinkerCoreBundle:LegalDocument');

                foreach ($existingOtherLegalDocs as $otherFile) {

                    if ( (bool) $otherFile['isRemoved'] === true) {
                        $legalDocumentEntity = $legalDocumentRepository->find($otherFile['otherLegalDocumentId']);
                        $applicationManager->removeLegalDocument ($legalDocumentEntity);
                    }
                }

            }
        }

        $response = compact (
            'isSuccessful',
            'message'
        );

        return new JsonResponse($response);
    }

    /**
     * Handles Submission of legal Docs for Affiliate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitAffiliateLegalDocumentsAction (Request $request)
    {
        $isSuccessful = true;
        $message = null;

        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->getAuthenticatedUser();
        $accreditationApplicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($authenticatedUser);
        $tinFile = $request->files->get('tinFile', null);
        $validIdFile = $request->files->get('validIdFile', null);
        $legalDocumentTypeId = $request->request->get('legalDocumentTypeId');
        $isUpdate = $request->request->get('isUpdate');
        $oldLegalDocumentTypeId = $request->request->get('oldLegalDocumentTypeId', 0);

        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        $validIds = array (
            LegalDocumentType::TYPE_SSS,
            LegalDocumentType::TYPE_PAG_IBIG,
            LegalDocumentType::TYPE_POSTAL,
            LegalDocumentType::TYPE_PASSPORT,
            LegalDocumentType::TYPE_DRIVERS_LICENSE,
            LegalDocumentType::TYPE_PRC,
            LegalDocumentType::TYPE_VOTERS_ID,
            LegalDocumentType::TYPE_SCHOOL_ID,
            LegalDocumentType::TYPE_TIN
        );

        if ( (int) $accreditationApplicationEntity->getSellerType() !== AccreditationApplication::SELLER_TYPE_RESELLER) {
            $isSuccessful = false;
            $message = 'Invalid Access.';
        }
        else if ( (int) $isUpdate == 1 && (int) $oldLegalDocumentTypeId === 0) {
            $isSuccessful = false;
            $message = 'Invalid Access.';
        }
        else if (!in_array($legalDocumentTypeId, $validIds)) {
            $isSuccessful = false;
            $message = 'Invalid Id';
        }
        else if ( (int) $isUpdate === 0 && (
                !($tinFile instanceof File) ||
                !($validIdFile instanceof File)
            )
        ) {
            $isSuccessful = false;
            $message = 'File is not a valid file.';
        }
        else if ($tinFile !== null) {
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formData = array (
                'images' => array ($tinFile)
            );
            $formFiles->submit($formData);

            if (!($tinFile instanceof File)) {
                $isSuccessful = false;
                $message = 'TIN is not a valid file.';
            }
            else if (!$formFiles->isValid()) {
                $isSuccessful = false;
                $message = 'Tin: ' . implode($formErrorService->throwInvalidFields($formFiles), ' \n');
            }

        }

        if ($validIdFile !== null && $isSuccessful === true) {
            $formFiles = $this->createForm('legal_document_upload', null, array('csrf_protection' => false));
            $formData = array (
                'images' => array ($validIdFile)
            );
            $formFiles->submit($formData);

            if (!($validIdFile instanceof File)) {
                $isSuccessful = false;
                $message = 'Invalid Id file.';
            }
            else if (!$formFiles->isValid()) {
                $isSuccessful = false;
                $message = 'Valid Id: ' . implode($formErrorService->throwInvalidFields($formFiles), ' \n');
            }

        }
        else if (!($authenticatedUser instanceof User)) {
            $isSuccessful = false;
            $message = 'Login to continue.';
        }
        else if (!($accreditationApplicationEntity instanceof AccreditationApplication)) {
            $isSuccessful = false;
            $message = 'Kindly Proceed to step one';
        }

        /**
         * Persist
         */
        if ($isSuccessful === true) {
            $userId = $authenticatedUser->getUserId();

            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $documentUploader = $this->get('yilinker_core.service.upload.document_uploader');

            if ($tinFile !== null) {
                $legalDocumentTypeTin = LegalDocumentType::TYPE_TIN;
                $fileNameDti = trim($userId . '_' . rand(1, 9999). '_' . $legalDocumentTypeTin . '_' . strtotime(Carbon::now()));
                $tinFileName = $documentUploader->uploadFile ($tinFile, $userId, $fileNameDti);
                $applicationManager->submitLegalDocument ($accreditationApplicationEntity, $legalDocumentTypeTin, $tinFileName);
            }

            if ($validIdFile !== null) {

                if ( (int) $isUpdate === 1) {
                    $legalDocumentTypeReference = $em->getReference('YilinkerCoreBundle:LegalDocumentType', $oldLegalDocumentTypeId);
                    $legalDocument = $em->getRepository('YilinkerCoreBundle:LegalDocument')
                                        ->findOneBy(
                                            array(
                                                'accreditationApplication' => $accreditationApplicationEntity->getAccreditationApplicationId(),
                                                'legalDocumentType' => $legalDocumentTypeReference->getLegalDocumentTypeId()
                                            )
                                        );
                    $applicationManager->removeLegalDocument($legalDocument);
                }

                $fileNameValidId = trim($userId . '_' . rand(1, 9999). '_' . $legalDocumentTypeId . '_' . strtotime(Carbon::now()));
                $validIdFileName = $documentUploader->uploadFile ($validIdFile, $userId, $fileNameValidId);
                $applicationManager->submitLegalDocument ($accreditationApplicationEntity, $legalDocumentTypeId, $validIdFileName);
            }

        }

        $response = compact (
            'isSuccessful',
            'message'
        );

        return new JsonResponse($response);
    }

    /**
     * Create/Update Business Information
     * This includes:
     *  - User Address
     *  - Social Media Accounts
     *  - AccreditationApplication
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitBusinessInformationAction (Request $request)
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $accreditationId = $request->request->get('accreditationId', null);
        $storeSellerType = $request->request->get('sellerType', null);
        $storeName = $request->request->get('storeName', null);
        $storeSlug = $request->request->get('storeSlug', null);
        $websiteUrl = $request->request->get('websiteUrl', null);
        $facebookUrl = $request->request->get('facebookUrl', null);
        $googleUrl = $request->request->get('googleUrl', null);
        $twitterUrl = $request->request->get('twitterUrl', null);
        $facebookUserSocialMediaId = $request->request->get('facebookUserSocialMediaId', null);
        $googleUserSocialMediaId = $request->request->get('googleUserSocialMediaId', null);
        $twitterUserSocialMediaId = $request->request->get('twitterUserSocialMediaId', null);
        $selectedStoreCategoryIds = $request->request->get('selectedStoreCategoryIds', null);
        $addresses = $request->request->get('addresses', null);
        $company = $request->request->get('company', null);
        $job = $request->request->get('job', null);
        $tin = $request->request->get('tin', null);
        $firstName = $request->get('firstName', null);
        $lastName = $request->get('lastName', null);
        $email = $request->get('email', null);

        $router = $this->get("router");
        $routeCollection = $router->getRouteCollection()->all();

        $registeredRoutes = array();
        foreach ($routeCollection as $route) {
            array_push($registeredRoutes, $route->getPath());
        }

        $em = $this->getDoctrine()->getManager();
        $userRepository = $em->getRepository('YilinkerCoreBundle:User');
        $accreditationRepository = $em->getRepository('YilinkerCoreBundle:AccreditationApplication');

        $isAddressesValid = true;
        $isSuccessful = true;
        $message = '';
        $isRemoveCount = 0;

        if ($addresses) {
            foreach ($addresses as $address) {

                if ($address['isRemoved'] === 'true') {
                    ++ $isRemoveCount;
                }
                else {
                    $isFormAddressValid = $this->validateAddress(
                                                     $address['locationId'],
                                                     $address['addressTitle'],
                                                     $address['unitNumber'],
                                                     $address['buildingName'],
                                                     $address['streetNumber'],
                                                     $address['streetName'],
                                                     $address['subdivision'],
                                                     $address['zipCode']
                                                 );
                    if (!$isFormAddressValid) {
                        $isAddressesValid = false;
                        break;
                    }
                }
            }
        }

        $storeEntity = $em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($authenticatedUser);
        $accreditationEntity = $accreditationRepository->findOneBy(array(
                                   'accreditationApplicationId' => $accreditationId,
                                   'user' => $authenticatedUser->getUserId()
                               ));
        $sellerTypes = array (
            Store::STORE_TYPE_MERCHANT,
            Store::STORE_TYPE_RESELLER,
        );

        $duplicateSlug = $em->getRepository('YilinkerCoreBundle:Store')->getStoreByStoreSlug($storeSlug, $authenticatedUser);

        if($email){
            $duplicateUser = $userRepository->findUserByEmailExcludeId(
                                $email,
                                $authenticatedUser->getUserId(),
                                null,
                                $authenticatedUser->getUserType(),
                                null,
                                $storeEntity->getStoreType()
                            );

            if($duplicateUser instanceof User){
                $isSuccessful = false;
                $message = 'This email is already taken.';
            }
        }

        if (!$isAddressesValid) {
            $isSuccessful = false;
            $message = 'Invalid Address';
        }
        else if ($tin === null || $tin === '' || $tin == 0) {
            $isSuccessful = false;
            $message = 'TIN is required';
        }
        else if ($company === null || $company === '') {
            $isSuccessful = false;
            $message = 'Company / School is required';
        }
        else if ($job === null || $job === '') {
            $isSuccessful = false;
            $message = 'Current Job / Course & Year Level is required';
        }
        else if ($storeName === null || $storeName === '') {
            $isSuccessful = false;
            $message = 'Store name is required';
        }
        else if(count($duplicateSlug)) {
            $isSuccessful = false;
            $message = 'Store link is already taken.';
        }
        else if (
            $storeSlug === null ||
            !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $storeSlug, $matches) ||
            in_array(DIRECTORY_SEPARATOR.$storeSlug, $registeredRoutes)
        ) {
            $isSuccessful = false;
            $message = 'Store link is invalid';
        }
        else if (!in_array($storeSellerType, $sellerTypes)) {
            $isSuccessful = false;
            $message = 'Invalid Seller Type';
        }
        else if ($accreditationId !== '' && !$accreditationEntity) {
            $isSuccessful = false;
            $message = 'Application does not exists, Try Refreshing the page.';
        }
        else if ($isRemoveCount === sizeof($addresses)) {
            $isSuccessful = false;
            $message = 'Address should not be empty';
        }
        else if ((int)$storeEntity->isAffiliate() && (
                 $selectedStoreCategoryIds === null ||
                 !is_array($selectedStoreCategoryIds) ||
                 sizeof($selectedStoreCategoryIds) == 0)
        ){
            $isSuccessful = false;
            $message = 'Invalid Product Category.';
        }

        /**
         * Persist
         */
        if ($isSuccessful) {

            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $sellerType = $applicationManager->getApplicationTypeByStoreType($storeSellerType);

            $storeEntity->setStoreName($storeName)->setStoreSlug($storeSlug);
            $this->get('yilinker_core.service.store_category_service')
                 ->processSelectedCategory($storeEntity, $selectedStoreCategoryIds);

            if (!$accreditationEntity) {
                $accreditationEntity = $applicationManager->createApplication ($authenticatedUser, $websiteUrl, $sellerType);
            }
            else {
                $accreditationEntity = $applicationManager->updateApplication ($accreditationEntity, $websiteUrl, $sellerType);
                $applicationManager->updateBusinessIfEditable ($accreditationEntity, 0);
            }

            $facebookUserSocialMediaAccountEntity = $applicationManager->manageUserSocialMediaAccount (
                                                                             $authenticatedUser,
                                                                             $facebookUserSocialMediaId,
                                                                             $facebookUrl,
                                                                             UserSocialMediaAccountType::FACEBOOK_TYPE
                                                                         );
            $googleUserSocialMediaAccountEntity = $applicationManager->manageUserSocialMediaAccount (
                                                                          $authenticatedUser,
                                                                          $googleUserSocialMediaId,
                                                                          $googleUrl,
                                                                          UserSocialMediaAccountType::GOOGLE_TYPE
                                                                      );
            $twitterUserSocialMediaAccountEntity = $applicationManager->manageUserSocialMediaAccount (
                                                                           $authenticatedUser,
                                                                           $twitterUserSocialMediaId,
                                                                           $twitterUrl,
                                                                           UserSocialMediaAccountType::TWITTER_TYPE
                                                                       );

            if ($addresses) {
                $this->get('yilinker_core.service.user_address.user_address')->bulkCreateUpdateOrDelete($addresses, $authenticatedUser);
            }

            $applicationManager->submitOccupation($authenticatedUser, $company, $job);
            $applicationManager->updateUserInfo($authenticatedUser, $tin, $firstName, $lastName, $email);

            $jwtService = $this->get("yilinker_core.service.jwt_manager");
            $request = $jwtService->setKey("ylo_secret_key")->encodeUser($authenticatedUser)->encodeToken(null);

            $ylaService = $this->get("yilinker_core.service.yla_service");
            $ylaService->setEndpoint(false);

            $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));
        }

        $response = compact (
            'isSuccessful',
            'message'
        );

        return new JsonResponse($response);
    }

    /**
     * Download Legal Documents
     *
     * @param $legalDocumentId
     */
    public function downloadLegalDocumentsAction ($legalDocumentId)
    {
        $em = $this->getDoctrine()->getManager();
        $legalDocumentEntity = $em->getRepository('YilinkerCoreBundle:LegalDocument')->find($legalDocumentId);
        $userEntity = $legalDocumentEntity->getAccreditationApplication()->getUser();
        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
        $fileFullPath = $applicationManager::LEGAL_DOCUMENTS_DIR . $userEntity->getUserId() . DIRECTORY_SEPARATOR . $legalDocumentEntity->getName();

        $pathParts = pathinfo($fileFullPath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header("Content-Disposition: attachment; filename=\"" . $pathParts['basename'] . "\";");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileFullPath));
        ob_clean();
        flush();
        readfile($fileFullPath);
    }

    /**
     * Validate Address
     *
     * @param $locationId
     * @param $title
     * @param $unitNumber
     * @param $buildingName
     * @param $streetNumber
     * @param $streetName
     * @param $subdivision
     * @param $zipCode
     * @return array
     */
    private function validateAddress (
        $locationId,
        $title,
        $unitNumber,
        $buildingName,
        $streetNumber,
        $streetName,
        $subdivision,
        $zipCode
    )
    {
        $isSuccessful = true;
        $message = null;

        $em = $this->getDoctrine()->getManager();
        $locationRepository = $em->getRepository("YilinkerCoreBundle:Location");
        $locationEntity = $locationRepository->find($locationId);

        $formData = array (
            "title" => $title,
            "unitNumber" => $unitNumber,
            "buildingName" => $buildingName,
            "streetNumber" => $streetNumber,
            "streetName" => $streetName,
            "subdivision" => $subdivision,
            "zipCode" => $zipCode,
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

        return $response;
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
     * Check role change and update session, then redirect
     *
     */
    private function checkRoleChange()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        $token = $tokenStorage->getToken();
        $user = $token->getUser();
        if($user instanceof User){
            $isEqual = count($user->getRoles()) == count($token->getRoles());
            if ($isEqual) {
                foreach($token->getRoles() as $role) {
                    $isEqual = $isEqual && in_array($role->getRole(), $user->getRoles());
                }
            }

            if($isEqual === false){
                $token->setAuthenticated(false);
            }
         }
    }

}
