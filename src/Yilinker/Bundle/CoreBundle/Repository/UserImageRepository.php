<?php
namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;

/**
 * Class UserImageRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserImageRepository extends EntityRepository
{
    public function loadUserImageByName(
    	$imageLocation,
    	$user = null,
    	$userImageType = null,
    	$isHidden = null
	){
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select("ui")
                    ->from("YilinkerCoreBundle:UserImage", "ui")
                    ->where("ui.imageLocation = :imageLocation")
                    ->setParameter(":imageLocation", $imageLocation);

        if(!is_null($user)){
        	$queryBuilder->andWhere("ui.user = :user")->setParameter(":user", $user);
        }

        if(!is_null($userImageType)){
        	$queryBuilder->andWhere("ui.userImageType = :userImageType")->setParameter(":userImageType", $userImageType);
        }

        if(!is_null($isHidden)){
        	$queryBuilder->andWhere("ui.isHidden = :isHidden")->setParameter(":isHidden", $isHidden);
        }

        return $queryBuilder->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
}
