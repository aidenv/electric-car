<?php
namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class BankAccountApiController extends Controller
{

     /**
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Bank Account",
     * )
     */
    public function getBankAccountsAction(Request $request)
    {
        $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Bank account collection.",
            "data" => $bankAccountService->getBankAccounts($this->getAuthenticatedUser())
        ), 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addBankAccountAction(Request $request)
    {
        $this->isBankUpdateAllowed();
        $bankId = $request->request->get("bankId", null);
        $entityManager = $this->getDoctrine()->getManager();
        $bankRepository = $entityManager ->getRepository("YilinkerCoreBundle:Bank");

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $data = array(
            'accountTitle'  => $request->request->get('accountTitle', null),
            'accountName'   => $request->request->get('accountName', null),
            'accountNumber' => $request->request->get('accountNumber', null),
            'bank'          => $bankId,
        );

        $form = $this->transactForm('core_bank_account', new BankAccount(), $data);

        if($form->isValid()){
            $authenticatedUser = $this->getAuthenticatedUser();
            $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');            
            $bankAccount = $bankAccountService->addBankAccount($authenticatedUser, null, $form->getData());
            $bank = $bankAccount->getBank();
            $accreditationApplication = $entityManager->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                                      ->findOneByUser($authenticatedUser);
            if ($accreditationApplication instanceof AccreditationApplication) {
                $accreditationApplication->setIsBankEditable(false);
            }

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Bank account successfully added.",
                "data" => array(
                    "bankAccountId" => $bankAccount->getBankAccountId(),
                    "bankId" => $bank->getBankId(),
                    "bankName" => $bank->getBankName(),
                    "accountTitle" => $bankAccount->getAccountTitle(),
                    "accountName" => $bankAccount->getAccountName(),
                    "accountNumber" => $bankAccount->getAccountNumber(),
                    "isDefault" => $bankAccount->getIsDefault(),
                )
            ), 200);
        }

        $errors = $formErrorService->throwInvalidFields($form);
        return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function editBankAccountAction(Request $request)
    {
        $this->isBankUpdateAllowed();
        $authenticatedUser = $this->getAuthenticatedUser();
        $bankId = $request->request->get("bankId", null);

        $entityManager = $this->getDoctrine()->getManager();
        $bankRepository = $entityManager->getRepository("YilinkerCoreBundle:Bank");

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $bankAccountRepository = $entityManager->getRepository("YilinkerCoreBundle:BankAccount");

        $bankAccount = $bankAccountRepository->findOneBy(array(
                            "bankAccountId" => $request->request->get('bankAccountId', null),
                            "user" => $authenticatedUser
                        ));

        if(is_null($bankAccount)){
            return $formErrorService->throwCustomErrorResponse(array("Invalid bank account"), "Invalid bank account.");
        }

        $data = array(
            'accountTitle'  => $request->request->get('accountTitle', null),
            'accountName'   => $request->request->get('accountName', null),
            'accountNumber' => $request->request->get('accountNumber', null),
            'bank'          => $bankId,
        );

        $form = $this->transactForm('core_bank_account', $bankAccount, $data);

        if($form->isValid()){
            $accreditationApplication = $entityManager->getRepository('YilinkerCoreBundle:AccreditationApplication')
                                                      ->findOneByUser($authenticatedUser);
            if ($accreditationApplication instanceof AccreditationApplication) {
                $accreditationApplication->setIsBankEditable(false);
            }

            $bankAccount = $form->getData();
            $bank = $bankAccount->getBank();
            $entityManager->flush();
            
            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Bank account successfully edited.",
                "data" => array(
                    "bankAccountId" => $bankAccount->getBankAccountId(),
                    "bankId" => $bank->getBankId(),
                    "bankName" => $bank->getBankName(),
                    "accountTitle" => $bankAccount->getAccountTitle(),
                    "accountName" => $bankAccount->getAccountName(),
                    "accountNumber" => $bankAccount->getAccountNumber(),
                    "isDefault" => $bankAccount->getIsDefault(),
                )
            ), 200);
        }

        $errors = $formErrorService->throwInvalidFields($form);
        return $formErrorService->throwCustomErrorResponse($errors, "Invalid inputs.");
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteBankAccountAction(Request $request)
    {
        $this->isBankUpdateAllowed();
        $authenticatedUser = $this->getAuthenticatedUser();

        $entityManager = $this->getDoctrine()->getManager();

        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        $bankAccountRepository = $entityManager->getRepository("YilinkerCoreBundle:BankAccount");

        $bankAccount = $bankAccountRepository->findOneBy(array(
            "bankAccountId" => $request->request->get('bankAccountId', null),
            "user" => $authenticatedUser
        ));

        if(is_null($bankAccount)){
            return $formErrorService->throwCustomErrorResponse(array("Invalid bank account"), "Invalid bank account.");
        }

        if($bankAccount->getIsDefault()){
            return $formErrorService->throwCustomErrorResponse(array("Cant delete primary bank account."), "Delete failed.");
        }

        $bankAccount->setIsDelete(true);
        $entityManager->flush();

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Bank account successfully deleted.",
            "data" => array()
        ), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function setDefaultBankAccountAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $this->isBankUpdateAllowed();
        $bankAccountId = (int)$request->request->get("bankAccountId", 0);

        $bankAccountRepository = $this->getDoctrine()->getManager()->getRepository("YilinkerCoreBundle:BankAccount");
        $bankAccount = $bankAccountRepository->find($bankAccountId);
        $authenticatedUser = $this->getAuthenticatedUser();

        if(!is_null($bankAccount) && $bankAccount->getUser() == $authenticatedUser){

            $bankAccountService = $this->get('yilinker_core.service.bank_account.bank_account');
            $bankAccountService->setDefaultBankAccount($bankAccount, $authenticatedUser);

            $bankAccount->setIsDefault(1);
            $em->flush();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Bank account set to default.",
                "data" => array(
                    "bankAccountId" => $bankAccount->getBankAccountId(),
                    "bankId" => $bankAccount->getBank()->getBankId(),
                    "bankName" => $bankAccount->getBank()->getBankName(),
                    "accountTitle" => $bankAccount->getAccountTitle(),
                    "accountName" => $bankAccount->getAccountName(),
                    "accountNumber" => $bankAccount->getAccountNumber(),
                    "isDefault" => $bankAccount->getIsDefault(),
                )
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Bank account not found.",
            "data" => array()
        ), 402);
    }

    public function isBankUpdateAllowed()
    {
        $isBankEditable = $this->container->getParameter('yilinker_merchant')['bank']['is_editable'];
        if(!$isBankEditable){
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Edit Seller Banks Accounts is not allowed.",
                "data" => array()
            ), 403);
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

