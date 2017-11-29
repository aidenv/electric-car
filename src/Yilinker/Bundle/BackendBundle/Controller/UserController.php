<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Carbon\Carbon;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;

/**
 * Class UserController
 *
 * @Security("has_role('ROLE_ADMIN')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class UserController extends Controller
{
    const PAGE_LIMIT = 10;

    /**
     * Render Register Seller Page
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')  or has_role('ROLE_MARKETING')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderRegisteredSellerAction(Request $request, $type = 'seller')
    {
        $searchKeyword = $request->query->get('searchKeyword', null);
        $page = $request->query->get('page', 1) -1;
        $pageLimit = self::PAGE_LIMIT;
        $storeType = $type === 'seller' ? Store::STORE_TYPE_MERCHANT : Store::STORE_TYPE_RESELLER;

        $registeredSellers = $this->get('yilinker_backend.user_manager')
                                  ->getRegisteredSellers(
                                      $searchKeyword, 
                                      $storeType, 
                                      true, 
                                      $page * $pageLimit,
                                      $pageLimit
                                  );
        $em = $this->getDoctrine()->getManager();
        $banks = $em->getRepository('YilinkerCoreBundle:Bank')
                    ->findBy(array('isEnabled' => true));

        $data = compact(
            'registeredSellers',
            'pageLimit',
            'type',
            'banks'
        );

        return $this->render('YilinkerBackendBundle:User:registered_seller.html.twig', $data);
    }

    /**
     * Render Register Buyer Page
     * 
     * @Security("has_role('ROLE_CSR') or has_role('ROLE_MARKETING')")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderRegisteredBuyerAction (Request $request)
    {
        $dateFromCarbon = Carbon::now()->addMonth(-1)->startOfDay()->format('Y-m-d');
        $dateToCarbon = Carbon::now()->addDays(1)->endOfDay()->format('Y-m-d');
        $searchKeyword = $request->get('searchKeyword', null);
        $page = $request->get('page', 1);
        $dateFrom = $request->get('dateFrom', $dateFromCarbon);
        $dateTo = $request->get('dateTo', $dateToCarbon);
        $page = $page >= 1 ? $page - 1 : $page;
        $isActive = (int) $request->get('isActive', 1);
        $isActive = $isActive === 1;
        $pageLimit = self::PAGE_LIMIT;

        $registeredBuyer = $this->get('yilinker_backend.user_manager')
                                ->getRegisteredBuyers(
                                    $searchKeyword,
                                    $dateFrom,
                                    $dateTo,
                                    $isActive,
                                    $page * $pageLimit,
                                    $pageLimit
                                );

        $locationService = $this->get('yilinker_core.service.location.location');
        $provinces = $locationService->getAll(LocationType::LOCATION_TYPE_PROVINCE, true);

        $data = compact(
            'registeredBuyer',
            'pageLimit',
            'provinces'
        );

        return $this->render('YilinkerBackendBundle:User:registered_buyer.html.twig', $data);
    }

    /**
     * Get Seller Detail
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserDetailAction (Request $request)
    {
        $userId = $request->query->get('userId');
        $em = $this->getDoctrine()->getManager();
        $sellerEntity = $em->getRepository('YilinkerCoreBundle:User')->find($userId);
        $isSuccessful = false;
        $details = null;

        if ($sellerEntity) {
            $isSuccessful = true;
            $userAddressService = $this->get('yilinker_core.service.user_address.user_address');
            $userAddress = $userAddressService->getDefaultUserAddress($sellerEntity);
            $defaultBank = $sellerEntity->getDefaultBank() ? $sellerEntity->getDefaultBank()->toArray() : null;
            $referralCode = $sellerEntity->getReferralCode();
            
            $details = compact ('userAddress','referralCode','defaultBank');
        }

        $response = compact (
            'isSuccessful',
            'details'
        );

        return new JsonResponse($response);
    }

    /**
     * Generate Referral Code
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function generateReferralCodeAction (Request $request)
    {
        $isSuccessful = false;
        $referralCode = false;
        $userId = $request->request->get('userId');
        $em = $this->getDoctrine()->getManager();
        $sellerEntity = $em->getRepository('YilinkerCoreBundle:User')->find($userId);

        if ($sellerEntity) {
            $isSuccessful = true;
            $referralCode = $this->get('yilinker_core.service.account_manager')
                                 ->generateReferralCode($sellerEntity);
        }

        $response = compact (
            'isSuccessful',
            'referralCode'
        );

        return new JsonResponse($response);
    }

    /**
     * Update the user's bank account
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUserBankAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => '',
            'data'         => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $userId = $request->get('userId');
        $accountId = $request->get('accountId');
        $accountName = $request->get('accountName');
        $accountNumber = $request->get('accountNumber');
        $accountTitle = $request->get('accountTitle');
        $bankId = $request->get('bankId');
     
        $bankAccount = $em->getRepository('YilinkerCoreBundle:BankAccount')
                          ->findOneBy(array(
                              'bankAccountId' => $accountId,
                              'user'          => $userId,
                          ));
        if($bankAccount){

            $form = $this->createForm('core_bank_account', $bankAccount);
            $form->submit(array(
                'accountName'   => $accountName,
                'accountNumber' => $accountNumber,
                'accountTitle'  => $accountTitle,
                'bank'          => $bankId,
            ));

            if($form->isValid()){
                $em->flush();
                $bankAccount = $form->getData();
                $response['message'] = "Account successfully updated";
                $response['isSuccessful'] = true;
                $response['data'] = $bankAccount->toArray();
            }
            else{
                $response['message'] = $form->getErrors(true)[0]->getMessage();
            }
        }
        else{
            $response['message'] = 'Bank account not found.';
        }

        return new JsonResponse($response);
    }
    
    /**
     * Create the user's bank account
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function addUserBankAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => '',
            'data'         => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $userId = $request->get('userId');
        $accountId = $request->get('accountId');
        $accountName = $request->get('accountName');
        $accountNumber = $request->get('accountNumber');
        $accountTitle = $request->get('accountTitle');
        $bankId = $request->get('bankId');

        $form = $this->createForm('core_bank_account', new BankAccount());
        $form->submit(array(
            'accountName'   => $accountName,
            'accountNumber' => $accountNumber,
            'accountTitle'  => $accountTitle,
            'bank'          => $bankId,
        ));

        if($form->isValid()){
            $user = $em->getRepository('YilinkerCoreBundle:User')->find($userId);            
            $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');            
            $bankAccount = $bankAccountService->addBankAccount($user, null, $form->getData());

            if($bankAccount){
                $response['message'] = "Account successfully created";
                $response['isSuccessful'] = true;
                $response['data'] = $bankAccount->toArray();
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
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
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
            'message'      => 'Location not found',
            'data'         => array(),
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
     * Validate Address
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
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
        $userId = $request->get("userId", null);
        $userEntity = $em->getRepository('YilinkerCoreBundle:User')->find($userId);

        $formData = array (
            "title"         => $request->request->get("addressTitle", null),
            "unitNumber"    => $request->request->get("unitNumber", null),
            "buildingName"  => $request->request->get("buildingName", null),
            "streetNumber"  => $request->request->get("streetNumber", null),
            "streetName"    => $request->request->get("streetName", null),
            "subdivision"   => $request->request->get("subdivision", null),
            "zipCode"       => $request->request->get("zipCode", null),
            "streetAddress" => null,
            "longitude"     => null,
            "latitude"      => null,
        );

        $form = $this->createForm('core_user_address', new UserAddress());
        $form->submit($formData);

        if (!$locationEntity) {
            $isSuccessful = false;
            $message = array('Invalid Location');
        } else if (!$form->isValid()) {
            $isSuccessful = false;
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $message = $formErrorService->throwInvalidFields($form);
        } else if (!($userEntity instanceof User)) {
            $isSuccessful = false;
            $message = 'Invalid User Id';
        }

        $response = compact (
            'isSuccessful',
            'message'
        );

        return new JsonResponse($response);
    }

    /**
     * Bulk Edit Add or delete addresses
     *
     * @Security("has_role('ROLE_SELLER_SPECIALIST') or has_role('ROLE_CSR') or has_role('ROLE_EXPRESS_OPERATIONS')")
     * @param Request $request
     * @return JsonResponse
     */
    public function submitAddressesAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $addresses = $request->request->get('addresses', null);
        $userId = $request->get("userId", null);
        $userEntity = $em->getRepository('YilinkerCoreBundle:User')->find($userId);
        $isSuccessful = false;

        if ($addresses && $userEntity instanceof User) {
            $isSuccessful = true;
            $this->get('yilinker_core.service.user_address.user_address')->bulkCreateUpdateOrDelete($addresses, $userEntity);
        }

        return new JsonResponse($isSuccessful);
    }

}
