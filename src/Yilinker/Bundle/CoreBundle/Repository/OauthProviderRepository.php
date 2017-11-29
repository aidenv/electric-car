<?php
namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

class OauthProviderRepository extends EntityRepository
{
    public function getOauthProvidersIn($oauthProviderIds = array())
    {
        return $this->_em->createQueryBuilder()
                    ->select("op")
                    ->from("YilinkerCoreBundle:OauthProvider", "op", "op.oauthProviderId")
                    ->where("op.oauthProviderId IN (:oauthProviderIds)")
                    ->setParameter(":oauthProviderIds", $oauthProviderIds)
                    ->getQuery()
                    ->getResult();
    }
}
