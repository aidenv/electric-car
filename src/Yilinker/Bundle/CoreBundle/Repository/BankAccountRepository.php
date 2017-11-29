<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\User;

class BankAccountRepository extends EntityRepository
{
    public function getEnabledBankAccounts(User $user, $orderBy = "DESC")
    {
        $queryBuilder = $this->createQueryBuilder("ba")
                             ->innerJoin("YilinkerCoreBundle:Bank", "b", Join::WITH, "ba.bank = b")
                             ->where("ba.user = :user")
                             ->andWhere("b.isEnabled = true")
                             ->andWhere("ba.isDelete = :isDelete")
                             ->orderBy("ba.isDefault", $orderBy)
                             ->setParameter(":user", $user)
                             ->setParameter(":isDelete", BankAccount::STATUS_ACTIVE);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Bulk reset bank account
     *
     * @param User $user
     */
    public function resetDefaultBankAccount(User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->update("YilinkerCoreBundle:BankAccount", "ba")
                     ->set("ba.isDefault", "false")
                     ->where("ba.user = :user")
                     ->andWhere("ba.isDefault = true")
                     ->setParameter(":user", $user)
                     ->getQuery()
                     ->execute();
    }
}
