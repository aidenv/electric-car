<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel;
use Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class AccreditationController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_PRODUCT_SPECIALIST') or has_role('ROLE_ACCOUNTING') or has_role('ROLE_EXPRESS_OPERATIONS') or has_role('ROLE_MARKETING')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class AccreditationController extends Controller
{

    const PAGE_LIMIT = 30;

    /**
     * Render accreditation application list
     *
     * @param Request $request
     * @param $sellerType
     * @return Response
     */
    public function renderApplicationListAction(Request $request, $sellerType)
    {
        $sellerTypeId = $sellerType === 'Affiliate' ? AccreditationApplication::SELLER_TYPE_RESELLER : AccreditationApplication::SELLER_TYPE_MERCHANT;
        $em = $this->getDoctrine()->getManager();
        $searchKeyword = $request->query->get('searchKeyword', null);
        $userAccreditationTypeId = $request->query->get('userApplicationType', null);
        $resourceId = $request->query->get('resourceId', USER::RESOURCE_ALL_ID);
        $page = $request->query->get('page', 1) - 1;
        $pageLimit = self::PAGE_LIMIT;
        $accreditationApplications = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                        ->getAccreditationApplication ($searchKeyword, $userAccreditationTypeId, $sellerTypeId, $page, $pageLimit,$resourceId);
        $userAccreditationTypes = array (
            0 => array(
                'name' => 'Accredited',
                'id' => AccreditationApplication::USER_APPLICATION_TYPE_ACCREDITED,
            ),
            1 => array(
                'name' => 'Unaccredited',
                'id' => AccreditationApplication::USER_APPLICATION_TYPE_UNACCREDITED,
            ),
            2 => array(
                'name' => 'Waiting for Accreditation',
                'id' => AccreditationApplication::USER_APPLICATION_TYPE_WAITING,
            ),
            3 => array(
                'name' => 'All',
                'id' => AccreditationApplication::USER_APPLICATION_TYPE_ALL,
            )
        );

        $resourceIds = array(
        	0 => array(
                'name' => 'From Buyer Page',
                'id' => User::RESOURCE_BUYER_ID,
            ),
            1 => array(
                'name' => 'From Affiliate Page',
                'id' => User::RESOURCE_AFFILIATE_ID,
            ),
        	2 => array(
        		'name' => 'From All',
        		'id' =>USER::RESOURCE_ALL_ID,
        	)
        );

        $data = compact (
            'accreditationApplications',
            'userAccreditationTypes',
            'sellerType',
        	'resourceIds',
            'pageLimit'
        );

        return $this->render('YilinkerBackendBundle:Accreditation:accreditation_application_list.html.twig', $data);
    }

    /**
     * Render accreditation application detail
     *
     * @param $userId
     * @return Response
     */
    public function renderApplicationDetailAction($userId)
    {
        $em = $this->getDoctrine()->getManager();
        $applicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($userId);
        $applicationDetails = null;
        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');

        if (!($applicationEntity instanceof AccreditationApplication)) {
            $userEntity = $em->getRepository('YilinkerCoreBundle:User')->find($userId);
            $storeEntity = $userEntity->getStore();
            $applicationEntity = $applicationManager->createApplication ($userEntity, '', $storeEntity->getStoreType(), true);
        }

        $applicationDetails = $applicationManager->getApplicationDetails($applicationEntity);

        $accreditationTypes = array (
            0 => array(
                'id' => AccreditationLevel::TYPE_LEVEL_ONE,
                'name' => 'Accreditation Level 1'
            ),
            1 => array(
                'id' => AccreditationLevel::TYPE_LEVEL_TWO,
                'name' => 'Accreditation Level 2'
            )
        );

        $response = compact (
            'applicationDetails',
            'accreditationTypes'
        );

        $view = $applicationEntity->getSellerType() === AccreditationApplication::SELLER_TYPE_MERCHANT ?
            'YilinkerBackendBundle:Accreditation:accreditation_application_detail.html.twig' :
            'YilinkerBackendBundle:Accreditation:affiliate_accreditation_application_detail.html.twig';

        return $this->render($view, $response);
    }

    /**
     * Create Application Remark
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitApplicationRemarkAction (Request $request)
    {
        $authenticatedUser = $this->getUser();
        $accreditationApplicationId = $request->request->get('accreditationApplicationId');
        $message = $request->request->get('message');
        $applicationRemarkTypeId = $request->request->get('applicationRemarkTypeId');

        $em = $this->getDoctrine()->getManager();
        $accreditationApplicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->find($accreditationApplicationId);
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid Application'
        );

        if ($accreditationApplicationEntity instanceof AccreditationApplication) {
            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $mailer = $this->container->get('yilinker_core.service.user.mailer');
            $sellerEntity = $accreditationApplicationEntity->getUser();
            $applicationManager->createApplicationRemark (
                                     $accreditationApplicationEntity,
                                     $authenticatedUser,
                                     $applicationRemarkTypeId,
                                     $message
                                 );

            if (!is_null($sellerEntity->getEmail())) {
                $mailer->sendEmailNotification($sellerEntity, $this->__getMerchantUrls(), false);
            }

            $response = $applicationManager->sendSmsNotification($sellerEntity, false);

        }

        return new JsonResponse($response);
    }

    /**
     * Update Accreditation Type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAccreditationTypeAction (Request $request)
    {
        $accreditationApplicationTypeId = $request->request->get('accreditationApplicationTypeId');
        $accreditationApplicationId = $request->request->get('accreditationApplicationId');

        $em = $this->getDoctrine()->getManager();
        $accreditationApplicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                             ->find($accreditationApplicationId);
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Invalid Application'
        );

        if ($accreditationApplicationEntity) {
            $mailer = $this->container->get('yilinker_core.service.user.mailer');
            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
            $sellerEntity = $accreditationApplicationEntity->getUser();
            $storeEntity = $em->getRepository('YilinkerCoreBundle:Store')
                              ->findOneByUser($sellerEntity);

            $accreditationApplicationEntity = $applicationManager->updateApplicationType (
                                                                       $accreditationApplicationEntity,
                                                                       $accreditationApplicationTypeId,
                                                                       $storeEntity
                                                                   );

            if ($accreditationApplicationEntity instanceof AccreditationApplication) {

                if (!is_null($sellerEntity->getEmail())) {
                    $mailer->sendEmailNotification($sellerEntity, $this->__getMerchantUrls());
                }

                $response = $applicationManager->sendSmsNotification($sellerEntity);
            }

            $this->get('yilinker_core.service.account_manager')->verifyAccount($sellerEntity);
        }

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
     * Update Application Status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateApplicationStatusAction (Request $request)
    {
        $isSuccessful = false;
        $isComplete = (int) $request->request->get('isComplete', 0);
        $remarkTypeId = (int) $request->request->get('remarkTypeId', 0);
        $accreditationApplicationId = (int) $request->request->get('accreditationApplicationId', null);

        $em = $this->getDoctrine()->getManager();
        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
        $accreditationApplicationEntity = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->find($accreditationApplicationId);

        if ( $remarkTypeId === ApplicationRemarkType::TYPE_FILE_DTI_SEC_PERMIT ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_MAYORS_PERMIT ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_BIR_PERMIT ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_FORM ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_SSS ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_PAG_IBIG ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_POSTAL ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_PASSPORT ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_DRIVERS_LICENSE ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_PRC ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_VOTERS_ID ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_SCHOOL_ID ||
             $remarkTypeId === ApplicationRemarkType::TYPE_FILE_TIN ||
             $remarkTypeId === ApplicationRemarkType::TYPE_VALID_ID
        ) {

            $legalDocumentType = $applicationManager->getLegalDocumentTypeByRemarkId($remarkTypeId);

            $legalDocumentEntity = $em->getRepository('YilinkerCoreBundle:LegalDocument')
                                      ->findOneBy(array(
                                          'accreditationApplication' => $accreditationApplicationEntity,
                                          'legalDocumentType' => $legalDocumentType
                                      ));
            $applicationManager->updateLegalDocumentIsApproved ($legalDocumentEntity, $isComplete);
            $isSuccessful = true;
        }
        else if ($remarkTypeId === ApplicationRemarkType::TYPE_BUSINESS_INFORMATION) {
            $applicationManager->updateBusinessIsApproved($accreditationApplicationEntity, $isComplete);
            $isSuccessful = true;
        }
        else if ($remarkTypeId === ApplicationRemarkType::TYPE_BANK_INFORMATION) {
            $applicationManager->updateBankIsApproved($accreditationApplicationEntity, $isComplete);
            $isSuccessful = true;
        }

        return new JsonResponse($isSuccessful);
    }

    /**
     * Get merchant Urls
     *
     * @return array
     */
    private function __getMerchantUrls ()
    {
        $merchantDomain = $this->getParameter('merchant_hostname');

        return array (
            'seller' => array (
                'login'        => $merchantDomain . $this->generateUrl('backend_merchant_login'),
                'registration' => $merchantDomain . $this->generateUrl('backend_merchant_register')
            ),
            'affiliate' => array (
                'login'        => $merchantDomain . $this->generateUrl('backend_affiliate_login'),
                'registration' => $merchantDomain . $this->generateUrl('backend_affiliate_register')
            )
        );
    }
}
