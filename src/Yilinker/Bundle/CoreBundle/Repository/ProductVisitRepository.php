<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Yilinker\Bundle\CoreBundle\Entity\Product;

/**
 * ProductVisitRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductVisitRepository extends EntityRepository
{
	public function getProductVisitsThisDayByProduct(Product $product, $ipAddress)
	{
		$queryBuilder = $this->_em->createQueryBuilder();

		$queryBuilder->select("pv")
					 ->from("YilinkerCoreBundle:ProductVisit", "pv")
					 ->where("pv.product = :product")
					 ->andWhere("pv.ipAddress = :ipAddress")
					 ->andWhere(
					 	$queryBuilder->expr()->between(
					 		"pv.dateAdded", 
					 		":startOfDay", 
					 		":endOfDay"
			 		))
					->setParameter(":product", $product)
					->setParameter(":ipAddress", $ipAddress)
					->setParameter(":startOfDay", Carbon::now()->startOfDay())
					->setParameter(":endOfDay", Carbon::now()->endOfDay());

		return $queryBuilder->getQuery()->getResult();
	}
}
