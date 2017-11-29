<?php

namespace Yilinker\Bundle\CoreBundle\Repository;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Class ShippingCategoryRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class ShippingCategoryRepository extends EntityRepository
{
    public function filterBy(array $args)
    {
        if (isset($args['name']) && trim($args['name'])) {
            $this->qb()->andWhere('this.name LIKE :name')
                       ->setParameter('name', '%'.$args['name'].'%');
        }

        return $this;
    }
}
