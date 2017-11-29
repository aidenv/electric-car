<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Controller\Custom\CustomController;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\PayoutRequest;
use Yilinker\Bundle\CoreBundle\Traits;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Yilinker\Bundle\CoreBundle\Traits\AuthenticatedUserHandler;
use Yilinker\Bundle\CoreBundle\Traits\PaginationHandler;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;

class PayoutApiController extends CustomController
{
    use AuthenticatedUserHandler;
    use PaginationHandler;

    public function balanceRecordDetailsAction(Request $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateFrom = $dateFrom ? Carbon::createFromFormat('Y-m-d', $dateFrom): Carbon::minValue();
        $dateTo = $request->get('dateTo');
        $dateTo = $dateTo ? Carbon::createFromFormat('Y-m-d', $dateTo): Carbon::maxValue();

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();
        $dateFilter = array(
            'startdate' => $dateFrom,
            'enddate' => $dateTo,
            'order' => array(
                'dateLastModified' => 'DESC'
            )
        );

        $authenticatedUser = $this->getAuthenticatedUser();
        $earningRepository = $this->getDoctrine()->getManager()->getRepository("YilinkerCoreBundle:Earning");
        $activeEarning = $earningRepository->getEarningTotal(
                            $authenticatedUser,
                            null,
                            null,
                            Earning::COMPLETE
                        );
        $tentativeEarning = $earningRepository->getEarningTotal(
                            $authenticatedUser,
                            null,
                            null,
                            Earning::TENTATIVE
                        );
        $totalEarning = (floatval($activeEarning) + floatval($tentativeEarning));
        $totalWithdrew = $store->service->getTotalWithdrawn($dateFilter);
        $totalWithdrewInProcess = $store->service->getInProcessWithdrawal($dateFilter);
        $availableBalance = $store->service->getAvailableBalance();
        $activeEarning = number_format($activeEarning, 2);
        $tentativeEarning = number_format($tentativeEarning, 2);
        $totalEarning = number_format($totalEarning, 2);
        $totalWithdrew = number_format($totalWithdrew, 2);
        $totalWithdrewInProcess = number_format($totalWithdrewInProcess, 2);
        $availableBalance = number_format($availableBalance, 2);

        $currencyCode = 'P';
        $earningsResult = $store->service->getDailyEarning($dateFilter);
        $earnings = array();
        foreach ($earningsResult as $earning) {
            $date = Carbon::createFromFormat('m/d/Y', $earning['dayEarned']);
            $earnings[] = array(
                'date'      => $date->format('Y-m-d'),
                'amount'    => number_format($earning['amountEarned'], 2)
            );
        }

        $contactNumber = $authenticatedUser->getContactNumber();
        $fullName = $authenticatedUser->getFullName();

        $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');

        $accreditationErrors = $this->getAccreditationErrors($store);

        $ableToWithdraw = is_null($accreditationErrors)? true : false;

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = compact(
            'fullName',
            'contactNumber',
            'totalEarning',
            'activeEarning',
            'tentativeEarning',
            'totalWithdrew',
            'totalWithdrewInProcess',
            'availableBalance',
            'currencyCode',
            'earnings',
            'ableToWithdraw',
            'accreditationErrors'
        );

        $this->jsonResponse['data']['bankDetails'] = $bankAccountService->getDefaultBankAccount($authenticatedUser);


        return $this->jsonResponse();
    }

    /** @TODO: Refactor */
    public function withdrawRequestAction(Request $request)
    {
        $requestedAmount = $request->get('amount');
        $withdrawalMethod = $request->get('withdrawalMethod');
        $payoutRequestMethod = $withdrawalMethod == 'cheque' ?
                               PayoutRequest::PAYOUT_METHOD_CHEQUE:
                               PayoutRequest::PAYOUT_METHOD_BANK;

        $confirmationCode = $request->get('otp').":".$this->getUser()->getContactNumber();

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();

        $accreditationErrors = $this->getAccreditationErrors($store);

        if(!is_null($accreditationErrors)){
            $this->jsonResponse['message'] = $accreditationErrors;
            $this->jsonResponse['data']['errors'] = $accreditationErrors;
            return $this->jsonResponse();
        }

        $formData = compact('requestedAmount', 'payoutRequestMethod', 'confirmationCode');
        $form = $this->createForm('payout_request', null, array(
            'csrf_protection' => false
        ));
        $form->submit($formData);

        $user = $this->getUser();
        $accreditationStatus = $store->getAccreditationStatus();

        if ($form->isValid() && is_null($errors)) {
            $payoutRequest = $form->getData();
            $payoutRequest = $storeService->bindPayoutRequest($payoutRequest, $store);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($payoutRequest);
            $em->flush();

            $this->jsonResponse['isSuccessful'] = true;
        }
        else {
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $errors = $formErrorService->throwInvalidFields($form);
            $errors = array_merge(explode("\n", $this->jsonResponse['data']['errors']), $errors);
            $this->jsonResponse['message'] = implode("\n", $errors);
            $this->jsonResponse['data']['errors'] = $errors;
        }

        return $this->jsonResponse();
    }

    /**
     * Get withdrawal list of user
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Payout",
     *     statusCodes={
     *         200="Returned when successful",
     *         400={
     *             "Invalid parameter request",
     *             "Unauthorized request"
     *         },
     *     },
     *     parameters={
     *         {"name"="page", "dataType"="string", "required"=false, "description"="page number"},
     *         {"name"="perPage", "dataType"="string", "required"=false, "description"="display data per page"},
     *     }
     * )
     */
    public function withdrawalListAction(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 15);

        $storeService = $this->get('yilinker_core.service.entity.store');
        $store = $storeService->getStore();

        $em = $this->getDoctrine()->getEntityManager();
        $tbPayoutRequest = $em->getRepository('YilinkerCoreBundle:PayoutRequest');
        $payoutRequests = $tbPayoutRequest->getOfStore($store, $page, $perPage, true);
        $requests = array();
        foreach ($payoutRequests as $payoutRequest) {

            $bank = $payoutRequest->getBank();
            $hasActivePayoutRequests = $payoutRequest->hasActivePayoutRequests();

            $requests[] = array(
                'payoutRequestId'   => $payoutRequest->getPayoutRequestId(),
                'date'              => $payoutRequest->getDateAdded()->format('m/d/Y'),
                'withdrawalMethod'  => $payoutRequest->getPayoutRequestMethod(true, true),
                'totalAmount'       => number_format($payoutRequest->getRequestedAmount(), 2),
                'charge'            => number_format($payoutRequest->getCharge(), 2),
                'netAmount'         => number_format($payoutRequest->getNetAmount(), 2),
                'currencyCode'      => 'P',
                'status'            => $payoutRequest->getPayoutRequestStatus(true, true),
                'statusId'          => $hasActivePayoutRequests? PayoutRequest::PAYOUT_STATUS_IN_PROCESS : $payoutRequest->getPayoutRequestStatus(),
                'payTo'             => $payoutRequest->getRequestBy()->getFullName(),
                'bankName'          => $bank ? $bank->getBankName(): '',
                'accountNumber'     => $payoutRequest->getBankAccountNumber(),
                'accountName'       => $payoutRequest->getBankAccountName()
            );
        }

        $this->jsonResponse['isSuccessful'] = true;
        $this->jsonResponse['data'] = compact('requests');

        return $this->jsonResponse();
    }

    public function earningGroupsAction(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        $earningGroupService = $this->get("yilinker_core.service.earning.group");
        $earningGroups = $earningGroupService->getUserEarningGroups($user, Earning::INVALID);

        $this->jsonResponse["isSuccessful"] = true;
        $this->jsonResponse["message"] = "List of earning groups";
        $this->jsonResponse["data"]["earningGroups"] = $earningGroups;

        return $this->jsonResponse();
    }

    public function earningListAction(Request $request)
    {
        $earningGroupId = $request->get("earningGroupId", 0);
        $perPage = $request->get("perPage", 1);
        $page = $request->get("page", 1);

        $offset = $this->getOffset($perPage, $page);
        $user = $this->getAuthenticatedUser();

        $em = $this->getDoctrine()->getManager();
        $earningGroup = $em->getRepository("YilinkerCoreBundle:EarningGroup")->find($earningGroupId);

        if(!$earningGroup){
            $this->jsonResponse["isSuccessful"] = false;
            $this->jsonResponse["message"] = "Invalid earning group.";
            $this->jsonResponse["data"]["errors"] = array("Invalid earning group.");
        }

        $earningGroupService = $this->get('yilinker_core.service.earning.group');
        $earnings = $earningGroupService->getUserEarningsByGroup($user, $earningGroup, $perPage, $offset, array(Earning::INVALID));

        $this->jsonResponse["isSuccessful"] = true;
        $this->jsonResponse["message"] = "Earning list.";
        $this->jsonResponse["data"] = compact("earnings");

        return $this->jsonResponse();
    }

    private function getAccreditationErrors($store)
    {
        $errors = array();
        $user = $store->getUser();

        if($store->getStoreType() == Store::STORE_TYPE_RESELLER){

            switch($store->getAccreditationStatus()){
                case Store::ACCREDITATION_WAITING:

                    if(!$store->hasApprovedLegalDoc()){
                        array_push($errors, "Legal document is not yet approved");
                    }

                    if(!$store->hasApprovedBank()){
                        array_push($errors, "Bank is not yet approved");
                    }

                    break;

                case Store::ACCREDITATION_INCOMPLETE:

                    if(!$user->getDefaultBank()){
                        array_push($errors, "Bank account is required");
                    }

                    if(
                        $this->getUser()->getAccreditationApplication() &&
                        !$this->getUser()->getAccreditationApplication()->hasLegalDocument()
                    ){
                        array_push($errors, "Legal document is required");
                    }
                    elseif(!$this->getUser()->getAccreditationApplication()){
                        array_push($errors, "Accreditation application is incomplete.");
                    }

                    break;
            }
        }

        $contactCsr = "Please complete your bank information and wait to be accredited.";
        if(!$errors){
            if (!$store->ableToWithdraw()) {
                $errors = "Please complete the requirements and wait to be accredited. {$contactCsr}";
            }
            else{
                $errors = null;
            }
        }
        else{
            $errors = implode(", ", $errors);
            $errors = $errors.". {$contactCsr}";
        }

        return $errors;
    }
}
