<?php
namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BankRepository extends EntityRepository
{
    /**
     * Get all enabled banks
     *
     * @param null $searchBy
     * @param int $limit
     * @return array
     */
    public function getAllEnabledBanks($searchBy = null, $limit = 10)
    {
        $queryBuilder =  $this->createQueryBuilder("b")->where("b.isEnabled = true");

        if (!is_null($searchBy)) {
            $queryBuilder->andWhere('b.bankName LIKE :searchBy ')
                         ->setParameter('searchBy', '%' . $searchBy . '%');
        }

        $queryBuilder->orderBy('b.bankName', 'asc');

        if (!is_null($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
