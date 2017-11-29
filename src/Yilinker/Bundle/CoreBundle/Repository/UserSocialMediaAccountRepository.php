<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class UserSocialMediaAccountRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class UserSocialMediaAccountRepository extends EntityRepository
{

    /**
     * Get SocialMediaAccounts by user Id
     *
     * @param User $user
     * @return array
     */
    public function getUserSocialMediaAccounts (User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder->select(array(
                            'socialMediaType.userSocialMediaAccountTypeId as socialMediaTypeId',
                            'userSmAccount.userSocialMediaAccountId AS userSocialMediaAccountId',
                            'userSmAccount.name AS url',
                            'socialMediaType.name AS socialMediaTypeName'
                        ))
                     ->from("YilinkerCoreBundle:UserSocialMediaAccountType", "socialMediaType")
                     ->leftJoin("YilinkerCoreBundle:UserSocialMediaAccount", "userSmAccount", "WITH",
                                "userSmAccount.userSocialMediaAccountType = socialMediaType.userSocialMediaAccountTypeId AND userSmAccount.user = :userId")
                     ->setParameter('userId', $user->getUserId())
                     ->groupBy('socialMediaType.userSocialMediaAccountTypeId');
        $query = $queryBuilder->getquery();

        return $query->getResult();
    }

}
