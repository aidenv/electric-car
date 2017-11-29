<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class LanguageRepository extends EntityRepository
{
    public function getByCodes($codes)
    {
        $this
            ->qb()
            ->andWhere('this.code IN (:codes)')
            ->setParameter('codes', $codes);
        ;
        
        return $this->getResult();
    }

    public function filterBy($filter = array())
    {
        $this
            ->qb()
            ->innerJoin('this.languageCountries', 'languageCountries')
            ->innerJoin('languageCountries.country', 'country')
            ->andWhere('country.status = :status')
            ->setParameter('status', Country::ACTIVE_DOMAIN)
        ;

        if (isset($filter['q'])) {
            $this
                ->andWhere('this.name LIKE :q OR this.code LIKE :q')
                ->setParameter('q', '%'.$filter['q'].'%')
            ;
        }

        return $this;
    }
}