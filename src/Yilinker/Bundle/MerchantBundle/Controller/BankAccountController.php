<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;

/**
 * Class BankAccountController
 * @package Yilinker\Bundle\MerchantBundle\Controller
 */
class BankAccountController extends Controller
{

    /**
     * Render Dashboard Bank Account Information Markup
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBankAccountsAction()
    {
        $authenticatedUser = $this->getAuthenticatedUser();
        $bankAccounts = $this->get('yilinker_core.service.bank_account.bank_account')
                             ->getBankAccounts($authenticatedUser, "DESC");
        $banks = $this->get('yilinker_core.service.bank.bank')
                      ->getEnabledBanks();
        $accreditationApplication = $this->getDoctrine()->getManager()
                                                        ->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                                        ->findOneByUser($authenticatedUser);
        $isBankEditable = $this->container->getParameter('yilinker_merchant')['bank']['is_editable'] &&
                          is_null($authenticatedUser->getStore()->getAccreditationLevel()) &&
                          ($accreditationApplication instanceof AccreditationApplication && $accreditationApplication->getIsBankEditable());
        $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');
        $applicationDetails = $applicationManager->getApplicationDetailsBySeller($authenticatedUser);
        $data = compact(
            'bankAccounts',
            'banks',
            'isBankEditable',
            'applicationDetails'
        );

        return $this->render('YilinkerMerchantBundle:BankAccount:bank_account.html.twig', $data);
    }

    /**
     * Validate Bank Information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateBankInformationAction (Request $request)
    {       
        $isSuccessful = true;
        $message = null;

        $formData = array (
            'accountTitle'  => $request->request->get('accountTitle', null),
            'accountName'   => $request->request->get('accountName', null),
            'accountNumber' => $request->request->get('accountNumber', null),
            'bank'          => $request->request->get('bankId', null),
        );
        $form = $this->createForm('core_bank_account', new BankAccount());
        $form->submit($formData);

        if (!$form->isValid()) {
            $isSuccessful = false;
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $message = $formErrorService->throwInvalidFields($form);
        }

        $response = compact(
            'isSuccessful',
            'message'
        );

        return new JsonResponse($response);
    }

    /**
     * Create, Update or Delete Bank Information base on request data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitBankInformationAction (Request $request)
    {
        $bankInformationContainer = $request->request->get('bankInformationContainer', null);
        $isSuccessful = true;

        $em = $this->getDoctrine()->getManager();

        if (is_array($bankInformationContainer) && sizeof($bankInformationContainer) === 0) {
            $isSuccessful = false;
        }
        else {

            foreach ($bankInformationContainer as $bankInformation) {
                $formData = array (
                    'accountTitle'  => $bankInformation['accountTitle'],
                    'accountName'   => $bankInformation['accountName'],
                    'accountNumber' => $bankInformation['accountNumber'],
                    'bank'          => $bankInformation['bankId'],
                );
                $form = $this->createForm('core_bank_account', new BankAccount());
                $form->submit($formData);
                
                if (!$form->isValid()) {
                    $isSuccessful = false;
                    break;
                }
            }

        }
        $bankAccountRepository = $em->getRepository('YilinkerCoreBundle:BankAccount');

        if ($isSuccessful) {
            $accreditationApplication = $em->getRepository('YilinkerCoreBundle:AccreditationApplication')->findOneByUser($this->getAuthenticatedUser());
            $applicationManager = $this->get('yilinker_core.service.accreditation_application_manager');

            if ($accreditationApplication && $accreditationApplication->getAccreditationLevel() === null) {
                $applicationManager->updateBankIfEditable ($accreditationApplication, 0);
            }

            $authenticatedUser = $this->getAuthenticatedUser();
            $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');

            foreach ($bankInformationContainer as $bankInformation) {
                $bankAccountEntity = $form->getData();
                $bankEntity = $bankAccountEntity->getBank();
                $isNew = $bankInformation['isNew'] === 'true' ? true : false;
                $isChanged = $bankInformation['isChanged'] === 'true' ? true : false;
                $isRemoved = $bankInformation['isRemoved'] === 'true' ? true : false;

                /**
                 * Update
                 */
                if (!$isNew && $isChanged) {
                    $isDefault = $bankInformation['isDefault'] === 'true' ? 1 : 0;
                    $bankAccountEntity = $bankAccountRepository->findOneBy(array(
                        'bankAccountId' => $bankInformation['id'],
                    ));
                    $bankAccountService->updateBankAccount(
                                             $bankAccountEntity,
                                             $bankEntity,
                                             $bankInformation['accountTitle'],
                                             $bankInformation['accountName'],
                                             $bankInformation['accountNumber'],
                                             $isDefault
                                         );
                }
                /**
                 * Delete
                 */
                else if (!$isNew && $isRemoved) {
                    $bankAccountEntity = $bankAccountRepository->findOneBy(array(
                        'bankAccountId' => $bankInformation['id'],
                    ));
                    $bankAccountService->deleteBankAccount($bankAccountEntity);
                }
                /**
                 * Create
                 */
                else if ($isNew) {
                    $isDefault = $bankInformation['isDefault'] === 'true' ? 1 : 0;
                    $bankAccountService->addBankAccount($authenticatedUser, $bankEntity, $bankAccountEntity, $isDefault);
                }

            }

        }

        return new JsonResponse($isSuccessful);
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
