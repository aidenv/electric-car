<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\Message;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class MessageRepository
 *
 * @package Yilinker\Bundle\CoreBundle\Repository
 */
class MessageRepository extends EntityRepository
{
    /**
     * Retrieve unopened messages by the recipient
     *
     * @param int $userId
     * @return Yilinker\Bundle\CoreBundle\Entity\Message[]
     */
    public function getUnonepenedMessagesByUser(User $user)
    {
        return $this->createQueryBuilder("m")
                    ->where("m.recipient = :user")
                    ->andWhere("m.isSeen = :seen")
                    ->setParameter(":user", $user)
                    ->setParameter(":seen", true)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * Retrieve number of unopened messages by the recipient
     *
     * @param int $user
     * @return int
     */
    public function getCountUnonepenedMessagesByUser(User $user)
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $count = $queryBuilder->select("count(m)")
                              ->from("YilinkerCoreBundle:Message", "m")
                              ->where("m.recipient = :user")
                              ->andWhere("NOT m.isSeen = :seen")
                              ->setParameter("user", $user)
                              ->setParameter("seen", true)
                              ->getQuery()
                              ->getSingleScalarResult();
        
        return (int) $count;
    }

    public function getConversationMessages(User $recipient, User $sender, User $authenticatedUser, $limit = 10, $offset = 0, $excludedTimeSent = null)
    {
        $queryBuilder = $this->createQueryBuilder("m");

        $orx1 = $queryBuilder->expr()->orx();

        $andx1 = $queryBuilder->expr()->andx();
        $andx1->add($queryBuilder->expr()->eq("m.sender", ":sender"));
        $andx1->add($queryBuilder->expr()->eq("m.recipient", ":recipient"));

        $andx2 = $queryBuilder->expr()->andx();
        $andx2->add($queryBuilder->expr()->eq("m.sender", ":recipient"));
        $andx2->add($queryBuilder->expr()->eq("m.recipient", ":sender"));

        $orx1->add($andx1);
        $orx1->add($andx2);

        $orx2 = $queryBuilder->expr()->orx();

        $andx3 = $queryBuilder->expr()->andx();
        $andx3->add($queryBuilder->expr()->eq("m.recipient", ":authenticatedUser"));
        $andx3->add($queryBuilder->expr()->eq("m.isDeleteRecipient", ":boolean"));
        
        $andx4 = $queryBuilder->expr()->andx();
        $andx4->add($queryBuilder->expr()->eq("m.sender", ":authenticatedUser"));
        $andx4->add($queryBuilder->expr()->eq("m.isDeleteSender", ":boolean"));

        $orx2->add($andx3);
        $orx2->add($andx4);

        $queryBuilder->select()
                     ->where($orx1)
                     ->andWhere($orx2)
                     ->orderBy("m.timeSent", "DESC")
                     ->setFirstResult($offset)
                     ->setMaxResults($limit)
                     ->setParameter(":sender", $sender)
                     ->setParameter(":recipient", $recipient)
                     ->setParameter(":authenticatedUser", $authenticatedUser)
                     ->setParameter(":boolean", false);

        if(!is_null($excludedTimeSent)){
            $queryBuilder->andWhere("m.timeSent < :excludedTimeSent")
                         ->setParameter(":excludedTimeSent", $excludedTimeSent);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}

