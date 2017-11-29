<?php
namespace Yilinker\Bundle\CoreBundle\Services\Bank;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Bank;

class BankService
{
    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getEnabledBanks($searchBy = null)
    {
        $bankRepository = $this->em->getRepository("YilinkerCoreBundle:Bank");

        $banks = array();
        $bankCollection = $bankRepository->getAllEnabledBanks($searchBy);

        foreach($bankCollection as $bank){
            $bank = array(
                "bankId" => $bank->getBankId(),
                "bankName" => $bank->getBankName()
            );

            array_push($banks, $bank);
        }

        return $banks;
    }
}