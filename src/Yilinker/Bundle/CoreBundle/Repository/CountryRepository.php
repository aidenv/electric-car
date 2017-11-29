<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class CountryRepository extends EntityRepository
{
    public function excludeByCodes($codes)
    {
        $this->getQB()
             ->andWhere('this.status = :status')
             ->setParameter('status', Country::ACTIVE_DOMAIN)
             ->andWhere('this.code NOT IN (:excludeCodes)')
             ->setParameter('excludeCodes', $codes);

        return $this;
    }

    public function findAllWithExclude($codes = array())
    {
        $this->qb()
             ->excludeByCodes($codes);

        return $this->getResult();
    }

    public function getByCodes($codes)
    {
        $this
            ->qb()
            ->andWhere('this.code IN (:codes)')
            ->setParameter('codes', $codes);
        ;

        return $this->getResult();
    }

    public function getByCode($countryCode)
    {
        return $ths->qb()
                ->andWhere('this.code = :code')
                ->setParameter('code',$countryCode)
                ->getOneOrNullResult();
    }


    public function filterBy($filter = array())
    {
        $this
            ->qb()
            ->andWhere('this.status = :status')
            ->setParameter('status', Country::ACTIVE_DOMAIN)
        ;

        if (isset($filter['q'])) {
            $this
                ->andWhere('this.name LIKE :q OR this.code LIKE :q')
                ->setParameter('q', '%'.$filter['q'].'%')
            ;
        }

        if (isset($filter['lc']) && $filter['lc']) {
            // wether en languageCode will reveal all countries, default = true
            $enAllCountries = !(isset($filter['enAllCountries'])) || $filter['enAllCountries'];
            $hasEn = in_array('en', $filter['lc']) || in_array('EN', $filter['lc']);
            if (!($enAllCountries && $hasEn)) {
                $this
                    ->innerJoin('this.languageCountries', 'languageCountries')
                    ->innerJoin('languageCountries.language', 'language')
                    ->andWhere('language.code IN (:lc)')
                    ->setParameter('lc', $filter['lc'])
                ;
            }
        }

        return $this;
    }

    public function findFirst()
    {
        return $this->qb()
                    ->getOneOrNullResult();
    }
}