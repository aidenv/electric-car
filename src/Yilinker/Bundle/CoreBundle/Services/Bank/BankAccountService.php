<?php

namespace Yilinker\Bundle\CoreBundle\Services\Bank;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Bank;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\User;

class BankAccountService
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getBankAccounts(User $user, $orderBy = "DESC")
    {
        $bankAccountRepository = $this->em->getRepository("YilinkerCoreBundle:BankAccount");

        $data = array();
        $bankAccounts = $bankAccountRepository->getEnabledBankAccounts($user, $orderBy);

        foreach($bankAccounts as $bankAccount){
            array_push($data, array(
                "bankAccountId" => $bankAccount->getBankAccountId(),
                "bankId" => $bankAccount->getBank()->getBankId(),
                "bankName" => $bankAccount->getBank()->getBankName(),
                "accountTitle" => $bankAccount->getAccountTitle(),
                "accountName" => $bankAccount->getAccountName(),
                "accountNumber" => $bankAccount->getAccountNumber(),
                "isDefault" => $bankAccount->getIsDefault(),
            ));
        }

        return $data;
    }

    public function getDefaultBankAccount(User $user)
    {
        $bankAccountRepository = $this->em->getRepository("YilinkerCoreBundle:BankAccount");
        $bankAccount = $bankAccountRepository->findOneBy(array(
                            "user" => $user,
                            "isDefault" => true
                        ));

        return array(
            "bankAccountId" => !is_null($bankAccount)? $bankAccount->getBankAccountId() : null,
            "bankId" => !is_null($bankAccount)? $bankAccount->getBank()->getBankId() : null,
            "bankName" => !is_null($bankAccount)? $bankAccount->getBank()->getBankName() : null,
            "accountTitle" => !is_null($bankAccount)? $bankAccount->getAccountTitle() : null,
            "accountName" => !is_null($bankAccount)? $bankAccount->getAccountName() : null,
            "accountNumber" => !is_null($bankAccount)? $bankAccount->getAccountNumber() : null,
            "isDefault" => !is_null($bankAccount)? $bankAccount->getIsDefault() : null,
        );
    }

    public function setDefaultBankAccount(BankAccount $bankAccount, User $user)
    {
        $bankAccountRepository = $this->em->getRepository("YilinkerCoreBundle:BankAccount");
        $bankAccountRepository->resetDefaultBankAccount($user);

        $bankAccount->setIsDefault(true);
        $this->em->persist($bankAccount);
        $this->em->flush();
    }

    public function addBankAccount(User $user, Bank $bank = null, BankAccount $bankAccount, $isDefault = false)
    {
        $bankAccounts = $this->getBankAccounts($user);

        $bankAccount->setUser($user)
                    ->setIsDefault($isDefault);

        if($bank !== null){
            $bankAccount->setBank($bank);
        }

        if(empty($bankAccounts)){
            $bankAccount->setIsDefault(true);
        }

        $this->em->persist($bankAccount);
        $this->em->flush();

        return $bankAccount;
    }

    /**
     * Update Bank Account Information
     *
     * @param BankAccount $bankAccount
     * @param Bank $bank
     * @param $accountTitle
     * @param $accountName
     * @param $accountNumber
     * @param $isDefault
     * @return BankAccount
     */
    public function updateBankAccount (BankAccount $bankAccount, Bank $bank, $accountTitle, $accountName, $accountNumber, $isDefault = 0)
    {
        $bankAccount->setBank($bank);
        $bankAccount->setAccountTitle($accountTitle);
        $bankAccount->setAccountName($accountName);
        $bankAccount->setAccountNumber($accountNumber);
        $bankAccount->setIsDefault($isDefault);

        $this->em->flush();

        return $bankAccount;
    }

    /**
     * Soft Delete Bank Account
     *
     * @param BankAccount $bankAccount
     * @return BankAccount
     */
    public function deleteBankAccount (BankAccount $bankAccount)
    {
        $bankAccount->setIsDelete(true);
        $this->em->flush();

        return $bankAccount;
    }
}
