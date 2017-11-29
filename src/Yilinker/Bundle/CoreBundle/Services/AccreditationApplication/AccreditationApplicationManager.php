<?php
namespace Yilinker\Bundle\CoreBundle\Services\AccreditationApplication;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\File;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\Entity\ApplicationRemark;
use Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocument;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus;
use Yilinker\Bundle\CoreBundle\Entity\UserOccupation;
use Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccountType;
use Yilinker\Bundle\CoreBundle\Entity\UserSocialMediaAccount;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Services\SMS\SmsService;
use Yilinker\Bundle\CoreBundle\Services\StoreCategory\StoreCategoryService;
use Yilinker\Bundle\CoreBundle\Services\UserAddress\UserAddressService;

/**
 * Class AccreditationApplicationManager
 */
class AccreditationApplicationManager
{

    const LEGAL_DOCUMENTS_DIR = 'assets/legal_documents/';

    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    private $addressService;

    private $storeCategoryService;

    private $qrCodeGenerator;

    private $elasticaObjectPersister;

    private $smsService;

    /**
     * @param EntityManager $entityManager
     * @param UserAddressService $addressService
     * @param StoreCategoryService $storeCategoryService
     * @param $qrCodeGenerator
     * @param $elasticaObjectPersister
     * @param SmsService $smsService
     */
    public function __construct(
        EntityManager $entityManager,
        UserAddressService $addressService,
        StoreCategoryService $storeCategoryService,
        $qrCodeGenerator,
        $elasticaObjectPersister,
        SmsService $smsService
    )
    {
        $this->em = $entityManager;
        $this->addressService = $addressService;
        $this->storeCategoryService = $storeCategoryService;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->elasticaObjectPersister = $elasticaObjectPersister;
        $this->smsService = $smsService;
    }

    /**
     * Returns all valid ID's
     *
     * @return array
     */
    public function validIdsArray ()
    {
        return array (
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
    }
    /**
     * Create Accreditation Application
     *
     * @param User $user
     * @param string $businessWebSiteUrl
     * @param $sellerType
     * @param bool $isBusinessInfoEditable
     * @return AccreditationApplication
     */
    public function createApplication (User $user, $businessWebSiteUrl = '', $sellerType, $isBusinessInfoEditable = false)
    {
        $accreditationApplicationStatusOpen = $this->em->getReference('YilinkerCoreBundle:AccreditationApplicationStatus', AccreditationApplicationStatus::STATUS_OPEN);

        $accreditationApplication = new AccreditationApplication();
        $accreditationApplication->setUser($user);
        $accreditationApplication->setBusinessWebsiteUrl($businessWebSiteUrl);
        $accreditationApplication->setSellerType($sellerType);
        $accreditationApplication->setDateAdded(Carbon::now());
        $accreditationApplication->setLastModifiedDate(Carbon::now());
        $accreditationApplication->setIsBusinessEditable($isBusinessInfoEditable);
        $accreditationApplication->setIsBankEditable(true);
        $accreditationApplication->setAccreditationApplicationStatus($accreditationApplicationStatusOpen);
        $accreditationApplication->setResourceId($user->getResourceId());
        if ((int) $sellerType === AccreditationApplication::SELLER_TYPE_RESELLER) {
            $user->getStore()->setIsEditable(true);
            $productCategories = $this->em->getRepository('YilinkerCoreBundle:ProductCategory')
                                          ->findBy(array(
                                              'parent'   => ProductCategory::ROOT_CATEGORY_ID,
                                              'isDelete' => false,
                                          ));

            if (sizeof($productCategories) > 0) {
                $productCategoryIds = array();

                foreach ($productCategories as $productCategory) {
                    if ((int) $productCategory->getProductCategoryId() !== ProductCategory::ROOT_CATEGORY_ID) {
                        $productCategoryIds[] = $productCategory->getProductCategoryId();
                    }
                }

                $this->storeCategoryService->processSelectedCategory($user->getStore(), $productCategoryIds);

            }

        }

        $this->em->persist($accreditationApplication);
        $this->em->flush();

        return $accreditationApplication;
    }

    /**
     * Update Accreditation Application
     *
     * @param AccreditationApplication $accreditationApplication
     * @param $sellerType
     * @param $webSiteUrl
     * @return AccreditationApplication
     */
    public function updateApplication (AccreditationApplication $accreditationApplication, $webSiteUrl, $sellerType)
    {
        if ( (int) $sellerType === AccreditationApplication::SELLER_TYPE_MERCHANT ||
            (int) $sellerType === AccreditationApplication::SELLER_TYPE_RESELLER
        ) {
            $accreditationApplication->setSellerType($sellerType);
        }

        $accreditationApplication->setBusinessWebsiteUrl($webSiteUrl);
        $this->em->flush();

        return $accreditationApplication;
    }

    /**
     * Create UserSocialMediaAccount
     *
     * @param User $user
     * @param $name
     * @param UserSocialMediaAccountType $userSocialMediaAccountType
     * @return UserSocialMediaAccount
     */
    public function createUserSocialMediaAccount (User $user, $name, UserSocialMediaAccountType $userSocialMediaAccountType)
    {
        $socialMediaAccount = new UserSocialMediaAccount();
        $socialMediaAccount->setUser($user);
        $socialMediaAccount->setName($name);
        $socialMediaAccount->setUserSocialMediaAccountType($userSocialMediaAccountType);

        $this->em->persist($socialMediaAccount);
        $this->em->flush();

        return $socialMediaAccount;
    }

    /**
     * Update UserSocialMediaAccount
     *
     * @param UserSocialMediaAccount $userSocialMediaAccount
     * @param $name
     * @return UserSocialMediaAccount
     */
    public function updateUserSocialMediaAccount (UserSocialMediaAccount $userSocialMediaAccount, $name)
    {
        $userSocialMediaAccount->setName($name);
        $this->em->flush();

        return $userSocialMediaAccount;
    }

    public function manageUserSocialMediaAccount (User $user, $userSocialMediaAccountId, $name, $userSocialMediaAccountTypeId)
    {
        $userSocialMediaAccountEntity = $this->em->getRepository('YilinkerCoreBundle:UserSocialMediaAccount')
                                                 ->findOneBy(array(
                                                     'userSocialMediaAccountId' => $userSocialMediaAccountId,
                                                     'userSocialMediaAccountType' => $userSocialMediaAccountTypeId
                                                 ));
        $userSocialMediaAccountTypeReference = $this->em->getReference('YilinkerCoreBundle:UserSocialMediaAccountType', $userSocialMediaAccountTypeId);

        if ($userSocialMediaAccountEntity) {
            $userSocialMediaAccountEntity = $this->updateUserSocialMediaAccount($userSocialMediaAccountEntity, $name);
        }
        else {
            $userSocialMediaAccountEntity = $this->createUserSocialMediaAccount ($user, $name, $userSocialMediaAccountTypeReference);
        }

        return $userSocialMediaAccountEntity;
    }

    /**
     * Update StoreName
     *
     * @param Store $store
     * @param $storeName
     * @param $storeType
     * @return Store
     */
    public function updateStoreName (Store $store , $storeName, $storeType)
    {

        if ( (int) $storeType === AccreditationApplication::SELLER_TYPE_RESELLER) {
            $storeType = Store::STORE_TYPE_RESELLER;
        }
        else {
            $storeType = Store::STORE_TYPE_MERCHANT;
        }

        $store->setStoreName($storeName);
        $store->setStoreType($storeType);
        $this->em->flush();

        return $store;
    }

    /**
     * Create or Update Legal Document
     *
     * @param AccreditationApplication $accreditationApplication
     * @param $legalDocumentType
     * @param $name
     * @return null|object|LegalDocument
     * @throws \Doctrine\ORM\ORMException
     */
    public function submitLegalDocument (
        AccreditationApplication $accreditationApplication,
        $legalDocumentType,
        $name
    )
    {
        $legalDocumentTypeReference = $this->em->getReference('YilinkerCoreBundle:LegalDocumentType', $legalDocumentType);
        $legalDocument = $this->em->getRepository('YilinkerCoreBundle:LegalDocument')
                                  ->findOneBy(
                                      array(
                                          'accreditationApplication' => $accreditationApplication->getAccreditationApplicationId(),
                                          'legalDocumentType' => $legalDocumentTypeReference->getLegalDocumentTypeId()
                                      )
                                  );

        if (!$legalDocument || (int) $legalDocumentType === LegalDocumentType::TYPE_OTHERS) {
            $legalDocument = new LegalDocument();
        }
        else {
            $this->updateLegalDocumentIfEditable($legalDocument, 0);
        }

        $legalDocument->setAccreditationApplication($accreditationApplication);
        $legalDocument->setLegalDocumentType($legalDocumentTypeReference);
        $legalDocument->setName($name);
        $legalDocument->setDateAdded(Carbon::now());
        $legalDocument->setDateLastModified(Carbon::now());

        $this->em->persist($legalDocument);
        $this->em->flush();

        return $legalDocument;
    }

    /**
     * Add Tax Identification Number
     *
     * @param User $user
     * @param $tin
     * @return User
     */
    public function updateUserTin (User $user, $tin)
    {
        $user->setTin($tin);
        $this->em->flush();

        return $user;
    }

    /**
     * Remove legal document
     *
     * @param LegalDocument $legalDocument
     */
    public function removeLegalDocument (LegalDocument $legalDocument)
    {
        $this->em->remove($legalDocument);
        $this->em->flush();
    }

    /**
     * Get Application Details
     *
     * @param AccreditationApplication $accreditationApplication
     * @return array
     */
    public function getApplicationDetails (AccreditationApplication $accreditationApplication)
    {
        $bankData = array();
        $userEntity = $accreditationApplication->getUser();
        $bankAccounts = $this->em->getRepository("YilinkerCoreBundle:BankAccount")
                                 ->getEnabledBankAccounts($userEntity);

        if ($bankAccounts) {

            foreach ($bankAccounts as $bankAccount) {
                array_push($bankData, array (
                    "bankAccountId" => $bankAccount->getBankAccountId(),
                    "bankId" => $bankAccount->getBank()->getBankId(),
                    "bankName" => $bankAccount->getBank()->getBankName(),
                    "accountTitle" => $bankAccount->getAccountTitle(),
                    "accountName" => $bankAccount->getAccountName(),
                    "accountNumber" => $bankAccount->getAccountNumber(),
                    "isDefault" => $bankAccount->getIsDefault(),
                ));
            }

        }

        $legalDocuments = array();
        $legalDocumentEntity = null;
        $remarks = null;

        if ($accreditationApplication) {
            $legalDocumentEntity = $this->em->getRepository('YilinkerCoreBundle:LegalDocument')
                                            ->findByAccreditationApplication($accreditationApplication->getAccreditationApplicationId());
        }

        if ($legalDocumentEntity) {

            foreach($legalDocumentEntity as $legalDocument) {
                $remarkType = 0;
                $legalDocumentType = (int) $legalDocument->getLegalDocumentType()->getLegalDocumentTypeId();

                if ($legalDocumentType == LegalDocumentType::TYPE_BIR_PERMIT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_BIR_PERMIT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_MAYORS_PERMIT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_MAYORS_PERMIT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_DTI_SEC_PERMIT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_DTI_SEC_PERMIT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_FORM_M11501 ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_FORM;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_OTHERS ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_OTHER;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_SSS ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_SSS;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_PAG_IBIG ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_PAG_IBIG;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_POSTAL ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_POSTAL;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_PASSPORT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_PASSPORT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_DRIVERS_LICENSE ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_DRIVERS_LICENSE;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_PRC ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_PRC;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_VOTERS_ID ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_VOTERS_ID;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_SCHOOL_ID ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_SCHOOL_ID;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_TIN ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_TIN;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_VALID_ID ) {
                    $remarkType = ApplicationRemarkType::TYPE_VALID_ID;
                }

                $legalDocuments[] = array(
                    'entity' => $legalDocument,
                    'remarkTypeId' => $remarkType
                );

            }

        }

        $socialMediaAccounts = $this->em->getRepository('YilinkerCoreBundle:UserSocialMediaAccount')->getUserSocialMediaAccounts($userEntity);
        $storeEntity = $this->em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($userEntity);

        $userAddresses = $this->addressService->getUserAddresses($userEntity);

        if ($accreditationApplication) {
            $remarks = $this->em->getRepository('YilinkerCoreBundle:ApplicationRemark')->findByAccreditationApplication($accreditationApplication);
        }

        $editableApplication = $this->getEditableApplication($accreditationApplication, $legalDocumentEntity);

        $details = compact (
            'bankData',
            'legalDocuments',
            'socialMediaAccounts',
            'storeEntity',
            'accreditationApplication',
            'userEntity',
            'userAddresses',
            'remarks',
            'editableApplication'
        );

        return $details;
    }

    /**
     * Get Application Details By Seller
     *
     * @param User $userEntity
     * @return array
     */
    public function getApplicationDetailsBySeller (User $userEntity)
    {
        $bankData = array();
        $accreditationApplication = $this->em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($userEntity);
        $bankAccounts = $this->em->getRepository("YilinkerCoreBundle:BankAccount")
                                 ->getEnabledBankAccounts($userEntity);
        $accreditationApplicationStatus = 'Unaccredited';

        if ($bankAccounts) {

            foreach ($bankAccounts as $bankAccount) {
                array_push($bankData, array (
                    "bankAccountId" => $bankAccount->getBankAccountId(),
                    "bankId" => $bankAccount->getBank()->getBankId(),
                    "bankName" => $bankAccount->getBank()->getBankName(),
                    "accountTitle" => $bankAccount->getAccountTitle(),
                    "accountName" => $bankAccount->getAccountName(),
                    "accountNumber" => $bankAccount->getAccountNumber(),
                    "isDefault" => $bankAccount->getIsDefault(),
                ));
            }

        }
        $legalDocuments = array();
        $legalDocumentEntity = null;
        $remarks = null;

        if ($accreditationApplication) {
            $legalDocumentEntity = $this->em->getRepository('YilinkerCoreBundle:LegalDocument')
                                            ->findByAccreditationApplication($accreditationApplication->getAccreditationApplicationId());

            if ($accreditationApplication->getAccreditationApplicationId() !== null && $accreditationApplication->getAccreditationLevel() !== null) {
                $accreditationApplicationStatus = 'Accredited';
            }
            else if ($accreditationApplication->getAccreditationApplicationId() !== null && $accreditationApplication->getAccreditationLevel() === null) {
                $accreditationApplicationStatus = 'Waiting for accreditation';
            }

        }

        if ($legalDocumentEntity) {

            foreach($legalDocumentEntity as $legalDocument) {
                $remarkType = 0;
                $legalDocumentType = (int) $legalDocument->getLegalDocumentType()->getLegalDocumentTypeId();

                if ($legalDocumentType == LegalDocumentType::TYPE_BIR_PERMIT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_BIR_PERMIT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_MAYORS_PERMIT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_MAYORS_PERMIT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_DTI_SEC_PERMIT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_DTI_SEC_PERMIT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_FORM_M11501 ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_FORM;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_OTHERS ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_OTHER;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_SSS ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_SSS;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_PAG_IBIG ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_PAG_IBIG;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_POSTAL ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_POSTAL;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_PASSPORT ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_PASSPORT;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_DRIVERS_LICENSE ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_DRIVERS_LICENSE;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_PRC ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_PRC;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_VOTERS_ID ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_VOTERS_ID;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_SCHOOL_ID ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_SCHOOL_ID;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_TIN ) {
                    $remarkType = ApplicationRemarkType::TYPE_FILE_TIN;
                }
                else if ($legalDocumentType == LegalDocumentType::TYPE_VALID_ID) {
                    $remarkType = ApplicationRemarkType::TYPE_VALID_ID;
                }

                $legalDocuments[] = array(
                    'entity' => $legalDocument,
                    'remarkTypeId' => $remarkType
                );

            }

        }

        $socialMediaAccounts = $this->em->getRepository('YilinkerCoreBundle:UserSocialMediaAccount')->getUserSocialMediaAccounts($userEntity);
        $storeEntity = $this->em->getRepository('YilinkerCoreBundle:Store')->findOneByUser($userEntity);

        $userAddresses = $this->addressService->getUserAddresses($userEntity);
        $remarkArray = array();

        if ($accreditationApplication) {
            $remarks = $this->em->getRepository('YilinkerCoreBundle:ApplicationRemark')->findByAccreditationApplication($accreditationApplication);

            if (sizeof($remarks) > 0) {

                foreach ($remarks as $remark) {
                    $remarkType = $remark->getApplicationRemarkType()->getApplicationRemarkTypeId();
                    $remarkArray[$remarkType][] = $remark;
                }

            }

        }

        $progress = $this->getApplicationCompletionPercentage($accreditationApplication, $legalDocumentEntity);
        $editableApplication = $this->getEditableApplication($accreditationApplication, $legalDocumentEntity);
        $isLegalDocsEditable = $this->isLegalDocumentEditable($legalDocumentEntity);
        $userOccupation = $this->em->getRepository('YilinkerCoreBundle:UserOccupation')->findOneByUser($userEntity);
        $storeCategory = $this->storeCategoryService->getCategoryWithSelectedStoreCategory($storeEntity);

        return compact (
            'bankData',
            'legalDocuments',
            'socialMediaAccounts',
            'storeEntity',
            'accreditationApplication',
            'userEntity',
            'userAddresses',
            'remarks',
            'remarkArray',
            'progress',
            'editableApplication',
            'isLegalDocsEditable',
            'accreditationApplicationStatus',
            'userOccupation',
            'storeCategory'
        );
    }

    /**
     * Create Application Remark
     *
     * @param AccreditationApplication $accreditationApplication
     * @param AdminUser $adminUser
     * @param $applicationRemarkTypeId
     * @param $message
     * @return ApplicationRemark
     */
    public function createApplicationRemark (
        AccreditationApplication $accreditationApplication,
        AdminUser $adminUser,
        $applicationRemarkTypeId,
        $message
    )
    {
        $applicationRemarkTypeReference = $this->em->getReference('YilinkerCoreBundle:ApplicationRemarkType', $applicationRemarkTypeId);

        $applicationRemark = new ApplicationRemark();
        $applicationRemark->setAccreditationApplication($accreditationApplication);
        $applicationRemark->setAdminUser($adminUser);
        $applicationRemark->setApplicationRemarkType($applicationRemarkTypeReference);
        $applicationRemark->setMessage($message);
        $applicationRemark->setDateAdded(Carbon::now());

        $this->em->persist($applicationRemark);
        $this->em->flush();

        if ( (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_BUSINESS_INFORMATION) {
            $this->updateBusinessIfEditable ($accreditationApplication, 1);
        }
        else if ( (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_BANK_INFORMATION) {
            $this->updateBankIfEditable ($accreditationApplication, 1);
        }
        else if (
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_DTI_SEC_PERMIT ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_MAYORS_PERMIT ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_BIR_PERMIT ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_FORM ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_SSS ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_PAG_IBIG ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_POSTAL ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_PASSPORT ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_DRIVERS_LICENSE ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_PRC ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_VOTERS_ID ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_SCHOOL_ID ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_FILE_TIN ||
            (int) $applicationRemarkTypeId === ApplicationRemarkType::TYPE_VALID_ID
        ) {
            $legalDocuments = $this->em->getRepository('YilinkerCoreBundle:LegalDocument')
                                       ->findOneBy(array(
                                           'accreditationApplication' => $accreditationApplication,
                                           'legalDocumentType' => $this->getLegalDocumentTypeByRemarkId($applicationRemarkTypeId)
                                       ));

            $this->updateLegalDocumentIfEditable ($legalDocuments, 1);
        }

        return $applicationRemark;
    }

    /**
     * Get Legal Document Type By RemarkTypeId
     *
     * @param $remarkTypeId
     * @return int
     */
    public function getLegalDocumentTypeByRemarkId ($remarkTypeId)
    {
        $legalDocumentType = 0;

        if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_BIR_PERMIT ) {
            $legalDocumentType = LegalDocumentType::TYPE_BIR_PERMIT;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_MAYORS_PERMIT ) {
            $legalDocumentType = LegalDocumentType::TYPE_MAYORS_PERMIT;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_DTI_SEC_PERMIT) {
            $legalDocumentType = LegalDocumentType::TYPE_DTI_SEC_PERMIT;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_FORM) {
            $legalDocumentType = LegalDocumentType::TYPE_FORM_M11501;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_SSS) {
            $legalDocumentType = LegalDocumentType::TYPE_SSS;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_PAG_IBIG) {
            $legalDocumentType = LegalDocumentType::TYPE_PAG_IBIG;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_POSTAL) {
            $legalDocumentType = LegalDocumentType::TYPE_POSTAL;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_PASSPORT) {
            $legalDocumentType = LegalDocumentType::TYPE_PASSPORT;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_DRIVERS_LICENSE) {
            $legalDocumentType = LegalDocumentType::TYPE_DRIVERS_LICENSE;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_PRC) {
            $legalDocumentType = LegalDocumentType::TYPE_PRC;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_VOTERS_ID) {
            $legalDocumentType = LegalDocumentType::TYPE_VOTERS_ID;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_SCHOOL_ID) {
            $legalDocumentType = LegalDocumentType::TYPE_SCHOOL_ID;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_FILE_TIN) {
            $legalDocumentType = LegalDocumentType::TYPE_TIN;
        }
        else if ($remarkTypeId == ApplicationRemarkType::TYPE_VALID_ID) {
            $legalDocumentType = LegalDocumentType::TYPE_VALID_ID;
        }

        return $legalDocumentType;
    }

    /**
     * Update Accreditation Application Type
     *
     * @param AccreditationApplication $accreditationApplication
     * @param $accreditationApplicationTypeId
     * @param $store
     * @return AccreditationApplication
     */
    public function updateApplicationType (AccreditationApplication $accreditationApplication, $accreditationApplicationTypeId, Store $store)
    {
        $accreditationReference = $this->em->getReference('YilinkerCoreBundle:AccreditationLevel', $accreditationApplicationTypeId);

        if(!is_null($accreditationReference)){
            $store->setIsEditable(false);
        }

        $store->setAccreditationLevel($accreditationReference);
        $accreditationApplication->setAccreditationLevel($accreditationReference);

        $this->qrCodeGenerator->generateStoreQrCode($store, $store->getStoreSlug());

        if(!is_null($accreditationReference)){
            $this->elasticaObjectPersister->insertOne($store);
        }

        $this->em->flush();

        return $accreditationApplication;
    }

    /**
     * Get base64 image.
     *
     * @param $fileDir
     * @return string
     */
    public function getLegalDocumentFile ($fileDir)
    {
        $fullPath = self::LEGAL_DOCUMENTS_DIR . $fileDir;
        $pathParts = pathinfo($fullPath);
        $contents = file_get_contents($fullPath);

        $base64   = base64_encode($contents);

        return ('data:' . $pathParts['extension'] . ';base64,' . $base64);
    }

    /**
     * Get Application Completion Percentage
     *
     * @param AccreditationApplication|null $accreditationApplication
     * @param null $legalDocuments
     * @return int
     */
    public function getApplicationCompletionPercentage (AccreditationApplication $accreditationApplication = null, $legalDocuments = null)
    {
        $progress = 0;

        if ($accreditationApplication !== null) {

            if ((bool)$accreditationApplication->getIsBusinessApproved() === true) {
                $progress = AccreditationApplication::BUSINESS_INFORMATION_PERCENTAGE;
            }

            if ((bool)$accreditationApplication->getIsBankApproved() === true) {
                $progress += AccreditationApplication::BANK_INFORMATION_PERCENTAGE;
            }

            if ($legalDocuments !== null && sizeof($legalDocuments) > 0) {

                foreach ($legalDocuments as $legalDocument) {
                    $legalDocumentTypeId = $legalDocument->getLegalDocumentType()->getLegalDocumentTypeId();
                    $isApproved = (bool)$legalDocument->getIsApproved();

                    if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_DTI_SEC_PERMIT && $isApproved) {
                        $progress += AccreditationApplication::DTI_FILE_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_MAYORS_PERMIT && $isApproved === true) {
                        $progress += AccreditationApplication::MAYORS_FILE_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_BIR_PERMIT && $isApproved === true) {
                        $progress += AccreditationApplication::BIR_FILE_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_FORM_M11501 && $isApproved === true) {
                        $progress += AccreditationApplication::FORM_FILE_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_SSS && $isApproved === true) {
                        $progress += AccreditationApplication::SSS_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_PAG_IBIG && $isApproved === true) {
                        $progress += AccreditationApplication::PAGIBIG_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_POSTAL&& $isApproved === true) {
                        $progress += AccreditationApplication::POSTAL_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_PASSPORT && $isApproved === true) {
                        $progress += AccreditationApplication::PASSPORT_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_DRIVERS_LICENSE && $isApproved === true) {
                        $progress += AccreditationApplication::DRIVERS_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_PRC && $isApproved === true) {
                        $progress += AccreditationApplication::PRC_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_VOTERS_ID && $isApproved === true) {
                        $progress += AccreditationApplication::VOTERS_PERCENTAGE;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_SCHOOL_ID && $isApproved === true) {
                        $progress += AccreditationApplication::SCHOOL_PERCENTAGE;
                    }

                }

            }

        }

        return $progress;
    }

    /**
     * Get Editable Application
     *
     * @param AccreditationApplication|null $accreditationApplication
     * @param null $legalDocuments
     * @return array
     */
    public function getEditableApplication (AccreditationApplication $accreditationApplication = null, $legalDocuments = null)
    {
        $editableApplications = array();

        if ($accreditationApplication !== null) {

            if ( (bool) $accreditationApplication->getIsBusinessEditable()) {
                $editableApplications[ApplicationRemarkType::TYPE_BUSINESS_INFORMATION] = true;
            }

            if ( (bool) $accreditationApplication->getIsBankEditable()) {
                $editableApplications[ApplicationRemarkType::TYPE_BANK_INFORMATION] = true;
            }

            if ($legalDocuments !== null && sizeof($legalDocuments) > 0) {

                foreach ($legalDocuments as $legalDocument) {
                    $legalDocumentTypeId = $legalDocument->getLegalDocumentType()->getLegalDocumentTypeId();
                    $isEditable = (bool) $legalDocument->getIsEditable();

                    if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_DTI_SEC_PERMIT && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_DTI_SEC_PERMIT] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_MAYORS_PERMIT && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_MAYORS_PERMIT] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_BIR_PERMIT && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_BIR_PERMIT] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_FORM_M11501 && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_FORM] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_SSS && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_SSS] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_PAG_IBIG && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_PAG_IBIG] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_POSTAL && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_POSTAL] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_PASSPORT && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_PASSPORT] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_DRIVERS_LICENSE && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_DRIVERS_LICENSE] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_PRC && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_PRC] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_VOTERS_ID && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_VOTERS_ID] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_SCHOOL_ID && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_SCHOOL_ID] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_TIN && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_FILE_TIN] = true;
                    }
                    else if ( (int) $legalDocumentTypeId === LegalDocumentType::TYPE_VALID_ID && $isEditable) {
                        $editableApplications[ApplicationRemarkType::TYPE_VALID_ID] = true;
                    }

                }

            }

        }

        return $editableApplications;
    }

    /**
     * Checks if legal document file is Editable
     *
     * @param $legalDocuments
     * @return bool
     */
    public function isLegalDocumentEditable ($legalDocuments = array())
    {
        $isEditable = false;

        if (sizeof($legalDocuments) > 0) {

            foreach ($legalDocuments as $legalDocument) {

                if ( (bool) $legalDocument->getIsEditable() === true) {
                    $isEditable = true;
                    break;
                }

            }

        }

        return $isEditable;
    }

    /**
     * Change AccreditationApplication isBusinessEditable
     *
     * @param AccreditationApplication $accreditationApplication
     * @param int $isEditable
     */
    public function updateBusinessIfEditable (AccreditationApplication $accreditationApplication, $isEditable = 0)
    {
        $accreditationApplication->setIsBusinessEditable($isEditable);
        $this->em->flush();
    }

    /**
     * Change AccreditationApplication isBankEditable
     *
     * @param AccreditationApplication $accreditationApplication
     * @param int $isEditable
     */
    public function updateBankIfEditable (AccreditationApplication $accreditationApplication, $isEditable = 0)
    {
        $accreditationApplication->setIsBankEditable($isEditable);
        $this->em->flush();
    }

    /**
     * Change LegalDocument isEditable
     *
     * @param LegalDocument $legalDocument
     * @param int $isEditable
     */
    public function updateLegalDocumentIfEditable (LegalDocument $legalDocument, $isEditable = 0)
    {
        $legalDocument->setIsEditable($isEditable);
        $legalDocument->setDateLastModified(Carbon::now());
        $this->em->flush();
    }

    /**
     * Change AccreditationApplication isBusinessComplete
     *
     * @param AccreditationApplication $accreditationApplication
     * @param int $isEditable
     */
    public function updateBusinessIsApproved (AccreditationApplication $accreditationApplication, $isEditable = 0)
    {
        $accreditationApplication->setIsBusinessApproved($isEditable);
        $this->em->flush();
    }

    /**
     * Change AccreditationApplication isBankComplete
     *
     * @param AccreditationApplication $accreditationApplication
     * @param int $isEditable
     */
    public function updateBankIsApproved (AccreditationApplication $accreditationApplication, $isEditable = 0)
    {
        $accreditationApplication->setIsBankApproved($isEditable);
        $this->em->flush();
    }

    /**
     * Change LegalDocument isComplete
     *
     * @param LegalDocument $legalDocument
     * @param int $isEditable
     */
    public function updateLegalDocumentIsApproved (LegalDocument $legalDocument, $isEditable = 0)
    {
        $legalDocument->setIsApproved($isEditable);
        $this->em->flush();
    }

    /**
     * Manage Create or Update of User Occupation
     *
     * @param User $user
     * @param $company
     * @param $job
     */
    public function submitOccupation (User $user, $company, $job)
    {
        $userOccupation = $this->em->getRepository('YilinkerCoreBundle:UserOccupation')->findOneByUser($user);

        if ($userOccupation instanceof UserOccupation) {
            $this->updateOccupation($userOccupation, $company, $job);
        }
        else {
            $this->createOccupation ($user, $company, $job);
        }

    }

    /**
     * Create UserOccupation
     *
     * @param User $user
     * @param $company
     * @param $job
     * @return UserOccupation
     */
    public function createOccupation (User $user, $company, $job)
    {
        $userOccupation = new UserOccupation();
        $userOccupation->setUser($user);
        $userOccupation->setName($company);
        $userOccupation->setJob($job);
        $userOccupation->setDateAdded(Carbon::now());

        $this->em->persist($userOccupation);
        $this->em->flush();

        return $userOccupation;
    }

    /**
     * Update User Occupation
     *
     * @param UserOccupation $userOccupation
     * @param $company
     * @param $job
     * @return UserOccupation
     */
    public function updateOccupation (UserOccupation $userOccupation, $company, $job)
    {
        $userOccupation->setName($company);
        $userOccupation->setJob($job);
        $this->em->flush();

        return $userOccupation;
    }

    /**
     * Update User info
     *
     * @param User $user
     * @param $tin
     * @param null $firstName
     * @param null $lastName
     * @param null $email
     * @return User
     */
    function updateUserInfo (User $user, $tin, $firstName = null, $lastName = null, $email = null)
    {
        $user->setTin($tin);

        if (!is_null($firstName) && $firstName != '') {
            $user->setFirstName($firstName);
        }

        if (!is_null($lastName) && $lastName != '') {
            $user->setLastName($lastName);
        }

        if (!is_null($email) && $email != '') {
            $user->setEmail($email);
        }

        $this->em->flush();

        return $user;
    }

    /**
     * Get Application Type by store type
     *
     * @param $storeType
     * @return int
     */
    public function getApplicationTypeByStoreType ($storeType)
    {
        if ( (int) $storeType === AccreditationApplication::SELLER_TYPE_RESELLER) {
            $storeType = AccreditationApplication::SELLER_TYPE_RESELLER;
        }
        else {
            $storeType = AccreditationApplication::SELLER_TYPE_MERCHANT;
        }

        return $storeType;
    }

    /**
     * Send sms notification
     *
     * @param User $user
     * @param bool|true $isApproved
     * @return array|mixed
     */
    public function sendSmsNotification (User $user, $isApproved = true)
    {
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid contact number'
        );

        if (!is_null($user->getContactNumber())) {
            $userType = (int) $user->getStore()->getStoreType() === Store::STORE_TYPE_MERCHANT ? 'Seller' : 'Affiliate';
            $message = 'Hi ' . $user->getFullName() . ', Congratulations! Your ' . $userType . ' accreditation has just been approved. You can now login to your account & start selling right away!';

            if ($isApproved === false) {
                $message = 'Hi ' . $user->getFullName() . ', Your accreditation is currently on-hold. Please login to your account, to review your accreditation details.';
            }

            $response = $this->smsService
                             ->sendAccreditationNotification (
                                 $user->getContactNumber(),
                                 $message
                             );
        }

        return $response;
    }

    /**
     * Process affiliate accreditation application
     *
     * @param User $userEntity
     * @param $firstName
     * @param $lastName
     * @param $tinId
     * @param $storeName
     * @param $storeSlug
     * @param $storeDesc
     * @return mixed
     */
    public function processAffiliateAccreditationApplication (
        User $userEntity,
        $firstName,
        $lastName,
        $tinId,
        $storeName,
        $storeSlug,
        $storeDesc
    )
    {
        $storeEntity = $userEntity->getStore();
        $accreditationApplication = $this->em->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                             ->findOneByUser($userEntity);
        $userEntity->setFirstName($firstName);
        $userEntity->setLastName($lastName);

        $isStorenameEmpty = is_null($storeEntity->getStoreName()) || $storeEntity->getStoreName() == '';
        $isStoreSlugEmpty = is_null($storeEntity->getStoreSlug()) || $storeEntity->getStoreSlug() == '';


        if ($storeEntity->getIsEditable() || ($isStorenameEmpty && $isStoreSlugEmpty)) {
            $storeEntity->setStoreName($storeName);
            $storeEntity->setStoreSlug($storeSlug);
            $storeEntity->setIsEditable(false);
        }

        $storeEntity->setStoreDescription($storeDesc);

        if ($accreditationApplication instanceof AccreditationApplication) {

            if (
                    !is_null($tinId) &&
                    (
                        $userEntity->getTin() == '' ||
                        (int) $userEntity->getTin() == 0 ||
                        $accreditationApplication->getIsBusinessEditable()
                    )
                ) {
                $userEntity->setTin($tinId);
            }

            $accreditationApplication->setIsBusinessEditable(false);
        }

        $this->em->flush();

        return $accreditationApplication;
    }

    /**
     * Revert accreditation level to `Waiting for accreditation`
     *
     * @param User $user
     * @return User
     */
    public function revertAccreditationLevel(User $user)
    {
        $user->getAccreditationApplication()->setAccreditationLevel(null);
        $user->getStore()->setAccreditationLevel(null);

        $this->em->flush();

        return $user;
    }
}
