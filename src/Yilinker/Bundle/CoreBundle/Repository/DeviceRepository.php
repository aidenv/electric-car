<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class DeviceRepository extends EntityRepository
{
    /**
     * Loads the registration ids of the users involved in the conversation
     *
     * @param $authenticatedUser
     * @param $recipient
     * @return array
     */
    public function loadConversationRegistrationIds($authenticatedUser, $recipient)
    {
        $registrationIds = $this->createQueryBuilder("d")
                                   ->where("d.user = :authenticatedUser")
                                   ->orWhere("d.user = :recipient")
                                   ->setParameter(":authenticatedUser", $authenticatedUser)
                                   ->setParameter(":recipient", $recipient)
                                   ->getQuery()
                                   ->getResult();

        return $registrationIds;
    }

    /**
     * Loads the registration id of the authenticated user.
     *
     * @param $user
     * @return int
     */
    public function loadUserRegistrationIds($user)
    {
        $registrationIdsObj = $this->createQueryBuilder("d")
                                   ->where("d.user = :user")
                                   ->setParameter(":user", $user)
                                   ->getQuery()
                                   ->getResult();

        return $registrationIdsObj;
    }

    /**
     * Loads the registration id of the authenticated user.
     *
     * @param $user
     * @return int
     */
    public function loadUserNotIdleRegistrationIds($user, $tokenTypes = array(), $isDelete = false)
    {
        $registrationIdsObj = $this->createQueryBuilder("d")
                                   ->where("d.user = :user")
                                   ->andWhere("d.tokenType IN (:tokenTypes)")
                                   ->andWhere("d.isIdle = :isIdle")
                                   ->andWhere("d.isDelete = :isDelete")
                                   ->setParameter(":user", $user)
                                   ->setParameter(":tokenTypes", $tokenTypes)
                                   ->setParameter(":isDelete", $isDelete)
                                   ->setParameter(":isIdle", false)
                                   ->getQuery()
                                   ->getResult();

        return $registrationIdsObj;
    }

    /**
     * Loads all registration ids of connected(contacts) to the authenticated user
     *
     * @param $contacts
     * @return array
     */
    public function loadAllTokens($users, $tokenTypes = array(), $isDelete = false)
    {
        $tokens = $this->createQueryBuilder("d")
                       ->where("d.user IN (:users)")
                       ->andWhere("d.tokenType IN (:tokenTypes)")
                       ->andWhere("d.isDelete = :isDelete")
                       ->setParameter(":users", $users)
                       ->setParameter(":tokenTypes", $tokenTypes)
                       ->setParameter(":isDelete", $isDelete)
                       ->getQuery()
                       ->getResult();

        return $tokens;
    }

    public function getNotificationDevices(
        $deviceType = null,
        $tokenType = null,
        $isDelete = null,
        $isNotificationSubscribe = null,
        $mustGroup = false,
        $limit = 30,
        $offset = 0
    ){
        $queryBuilder = $this->createQueryBuilder("d");

        if(!is_null($deviceType)){
            $deviceTypeQuery = is_array($deviceType)? "d.deviceType IN (:deviceType)" : "d.deviceType = :deviceType";
            $queryBuilder->andWhere($deviceTypeQuery)->setParameter(":deviceType", $deviceType);
        }

        if(!is_null($tokenType)){
            $tokenTypeQuery = is_array($tokenType)? "d.tokenType IN (:tokenType)" : "d.tokenType = :tokenType";
            $queryBuilder->andWhere($tokenTypeQuery)->setParameter(":tokenType", $tokenType);
        }

        if(!is_null($isDelete)){
            $queryBuilder->andWhere("d.isDelete = :isDelete")->setParameter(":isDelete", $isDelete);
        }

        if(!is_null($isNotificationSubscribe)){
            $queryBuilder->andWhere("d.isNotificationSubscribe = :isNotificationSubscribe")
                         ->setParameter(":isNotificationSubscribe", $isNotificationSubscribe);
        }

        if(!is_null($offset) && !is_null($limit)){
          $queryBuilder->setMaxResults($limit)->setFirstResult($offset);
        }

        if($mustGroup){
            $queryBuilder->groupBy("d.token");
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
