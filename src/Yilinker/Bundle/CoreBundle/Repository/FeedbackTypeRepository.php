<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class FeedbackTypeRepository extends EntityRepository
{
    public function getAllHydrated()
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $feedbackTypes = $queryBuilder->select("ft")
                                      ->from("YilinkerCoreBundle:FeedbackType", "ft", "ft.feedbackTypeId")
                                      ->getQuery()->getResult();
        
        return $feedbackTypes;
    }
}
