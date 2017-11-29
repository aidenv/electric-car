<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Yilinker\Bundle\CoreBundle\Entity\SearchKeyword;
use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

/**
 * Clas SearchKeywordRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class SearchKeywordRepository extends EntityRepository
{

    /**
     * Retrieve search keyword by queryString
     *
     * @param string $queryString
     * @param int $limit
     * @return Yilinker\Bundle\CoreBundle\Entity\SearchKeyword
     */
    public function findByQueryString($queryString = null, $limit = 25)
    {
        $queryBuilder = $this->_em
                             ->createQueryBuilder()
                             ->select("k")
                             ->from("YilinkerCoreBundle:SearchKeyword", "k");
        if($queryString !== null){
            $queryBuilder->where("k.keyword LIKE :queryString")
                         ->setParameter(":queryString", $queryString.'%');
        }

        $keywords = $queryBuilder->setMaxResults( $limit )
                                 ->getQuery()
                                 ->useResultCache(true, 86400)
                                 ->getResult();

        return $keywords;
    }

}
