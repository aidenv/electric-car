<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Doctrine\ORM\Query\Expr\Join;


class InhouseProductRepository extends EntityRepository
{
    public function searchBy($criteria, $createQB = true)
    {
        parent::searchBy($criteria, $createQB);
        if (isset($criteria['query'])) {
            $this->findByQuery($criteria['query']);
        }

        if (isset($criteria['statuses']) && isset($criteria['country'])) {
            $this
                ->findByCountries($criteria['country'])
                ->findByStatuses($criteria['statuses'])
            ;
        }

        if (isset($criteria['affiliate']) && $criteria['affiliate']) {
            $this->findByAffiliate($criteria['affiliate']);

            if (isset($criteria['statuses'])) {
                $this
                    ->andWhere('inhouseProductUsers.status IN (:inhouseStatuses)')
                    ->setParameter('inhouseStatuses', $criteria['statuses'])
                ;
            }
            if (isset($criteria['statuses.exclude'])) {
                $this->createComparison('status', $criteria['statuses.exclude'], 'exclude', 'inhouseProductUsers');
            }
            if (isset($criteria['dateLastModified.from'])) {
                $this->createComparison('dateLastModified', $criteria['dateLastModified.from'], 'from', 'inhouseProductUsers');
            }
            if (isset($criteria['dateLastModified.to'])) {
                $this->createComparison('dateLastModified', $criteria['dateLastModified.to'], 'to', 'inhouseProductUsers');
            }
            if (isset($criteria['dateLastModified.DESC'])) {
                $this->createComparison('dateLastModified', $criteria['dateLastModified.DESC'], 'DESC', 'inhouseProductUsers');
            }
        }
        elseif (isset($criteria['productUnit'])) {
            $this->findByProductUnit();

            if (isset($criteria['commision.DESC'])){
                $this->orderByCommission();
            }
        }
        elseif (isset($criteria['affiliate.unselected'])) {
            $this
                ->leftJoin('this.inhouseProductUsers', 'inhouseProductUsers', Join::WITH, 'inhouseProductUsers.user = :affiliateUnselected')
                ->andWhere('inhouseProductUsers.inhouseProductUserId IS NULL')
                ->setParameter('affiliateUnselected', $criteria['affiliate.unselected'])
            ;
        }

        return $this;
    }

    public function findByAffiliate($affiliate)
    {
        if ($affiliate) {
            $this
                ->innerJoin('this.inhouseProductUsers', 'inhouseProductUsers')
                ->andWhere('inhouseProductUsers.user = :affiliate')
                ->setParameter('affiliate', $affiliate)
            ;
        }

        return $this;
    }

    public function findByCountries($country)
    {
        $this
            ->innerJoin('this.productCountries', 'productCountries')
            ->andWhere('productCountries.country = :country')
            ->setParameter('country', $country)
        ;

        return $this;
    }

    public function findByProductUnit()
    {
        $this->
            innerJoin("YilinkerCoreBundle:InhouseProductUnit", "ipu", Join::WITH, "ipu.product = this");

        return $this;
    }

    public function orderByCommission($owner = 'ipu' , $modifier = 'DESC')
    {
        $this->orderBy("$owner.commission" , $modifier);

        return $this;
    }

    /**
     * needs findByCountries
     */
    public function findByStatuses($statuses)
    {
        $this
            ->andWhere('productCountries.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
        ;

        return $this;
    }

    public function findByQuery($query)
    {
        if ($query) {
            $this
                ->andWhere('this.name LIKE :query')
                ->setParameter('query', '%'.$query.'%')
            ;
        }

        return $this;
    }
}