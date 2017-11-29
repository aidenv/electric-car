<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

class ManufacturerRepository extends EntityRepository
{
    public function search($q, $limit = 10)
    {
        $this
            ->qb()
            ->andWhere('this.name LIKE :name')
            ->setParameter('name', '%'.$q.'%')
            ->setMaxResults($limit)
        ;

        return $this->getQB()->getQuery()->getResult();
    }
}