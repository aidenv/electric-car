<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PromoEventRepository extends EntityRepository
{
    public function getActivePromoEvent($promoEventId, $dateStart, $dateEnd)
    {
        $queryBuilder = $this->createQueryBuilder("pe");

        $andx = $queryBuilder->expr()->andx();

        $eq = $queryBuilder->expr()->eq("pe.promoEventId", ":promoEventId");
        $gte = $queryBuilder->expr()->gte("pe.dateStart", ":dateStart");
        $lte = $queryBuilder->expr()->lte("pe.dateEnd", ":dateEnd");
        $active = $queryBuilder->expr()->eq("pe.isActive", true);
        
        $andx->add($eq)->add($gte)->add($lte)->add($active);

        $queryBuilder->where($andx)
                     ->setParameter(":promoEventId", $promoEventId)
                     ->setParameter(":dateStart", $dateStart)
                     ->setParameter(":dateEnd", $dateEnd);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
