<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;

/**
 * ContactRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ContactRepository extends EntityRepository
{
	public function getUserContact(User $requestor, User $requestee)
	{
		$queryBuilder = $this->createQueryBuilder("c");

		$orx = $queryBuilder->expr()->orx();

		$andx1 = $queryBuilder->expr()->andx();
		$andx1->add($queryBuilder->expr()->eq("c.requestor", ":requestor"));
		$andx1->add($queryBuilder->expr()->eq("c.requestee", ":requestee"));

		$andx2 = $queryBuilder->expr()->andx();
		$andx2->add($queryBuilder->expr()->eq("c.requestor", ":requestee"));
		$andx2->add($queryBuilder->expr()->eq("c.requestee", ":requestor"));

		$orx->add($andx1);
		$orx->add($andx2);

		$queryBuilder->where($orx)
					 ->setParameter(":requestor", $requestor)
					 ->setParameter(":requestee", $requestee);

	 	return $queryBuilder->getQuery()->getOneOrNullResult();
	}

	public function getUserContactBySlug(User $authenticatedUser, $slug, $contactFiltered = true)
	{
        $queryBuilder = $this->_em->createQueryBuilder();

        $orx1 = $queryBuilder->expr()->orx();
        $orx1->add($queryBuilder->expr()->eq("u.slug", ":slug"));
        $orx1->add($queryBuilder->expr()->eq("s.storeSlug", ":slug"));

		$orx2 = $queryBuilder->expr()->orx();

		$andx1 = $queryBuilder->expr()->andx();
		$andx1->add($queryBuilder->expr()->eq("c.requestor", "u"));
		$andx1->add($queryBuilder->expr()->eq("c.requestee", ":user"));

		$andx2 = $queryBuilder->expr()->andx();
		$andx2->add($queryBuilder->expr()->eq("c.requestor", ":user"));
		$andx2->add($queryBuilder->expr()->eq("c.requestee", "u"));

		$orx2->add($andx1);
		$orx2->add($andx2);

        $queryBuilder->select("u")
                     ->from("YilinkerCoreBundle:User", "u")
                     ->leftJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u")
                     ->where($orx1)
                     ->setParameter(":slug", $slug);

        if($contactFiltered){
        	$queryBuilder->innerJoin("YilinkerCoreBundle:Contact", "c", Join::WITH, "c.requestor = u OR c.requestee = u")
        				 ->andWhere($orx2)
        				 ->setParameter(":user", $authenticatedUser);
        }

	 	$user = $queryBuilder->getQuery()->getOneOrNullResult();

	 	if($user === $authenticatedUser){
	 		return null;
	 	}
	 	else{
	 		return $user;
	 	}
	}

	public function getUserContacts(User $user, $keyword = null, $limit = null, $offset = null)
	{
		$queryBuilder = $this->_em->createQueryBuilder();

		$orx = $queryBuilder->expr()->orx();

		$orx->add($queryBuilder->expr()->eq("c.requestor", ":user"));
		$orx->add($queryBuilder->expr()->eq("c.requestee", ":user"));

		$queryBuilder->select("c")
					 ->from("YilinkerCoreBundle:Contact", "c")
					 ->where($orx)
					 ->setParameter(":user", $user);

		if(!is_null($keyword) && $keyword != ""){
			$orx = $queryBuilder->expr()->orx();
			$orx->add($queryBuilder->expr()->like("u.firstName", ":keyword"));
			$orx->add($queryBuilder->expr()->like("u.lastName", ":keyword"));
			$orx->add($queryBuilder->expr()->like("u.email", ":keyword"));
			$orx->add($queryBuilder->expr()->like("s.storeName", ":keyword"));

			$queryBuilder->innerJoin("YilinkerCoreBundle:User", "u", Join::WITH, "c.requestee = u OR c.requestor = u")
						 ->leftJoin("YilinkerCoreBundle:Store", "s", Join::WITH, "s.user = u")
						 ->andWhere($orx)
						 ->andWhere($queryBuilder->expr()->neq("u.email", ":email"))
						 ->setParameter(":email", $user->getEmail())
						 ->setParameter(":keyword", "%".$keyword."%");
		}

		if(!is_null($limit) && !is_null($offset)){
			$queryBuilder->setFirstResult($offset)
						 ->setMaxResults($limit);
		}

	 	return $queryBuilder->getQuery()->getResult();
	}
}