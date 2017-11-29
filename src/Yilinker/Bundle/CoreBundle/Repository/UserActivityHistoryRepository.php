<?php

namespace Yilinker\Bundle\CoreBundle\Repository;

use Yilinker\Bundle\CoreBundle\Repository\Custom\QueryRepository as EntityRepository;

class UserActivityHistoryRepository extends EntityRepository
{
    public function createActivitiesQuery($userId, $page = null, $perPage = 10)
    {
        $this->qb()
             ->andWhere('this.user = :user')
             ->setParameter('user', $userId)
             ->orderBy('this.dateAdded', 'DESC')
        ;
        if (!is_null($page)) {
            $offset = ($page - 1) * ($perPage + 1);
            $this->setFirstResult($offset);
            $this->setMaxResults($perPage);
        }

        return $this;
    }

    public function countActivities($userId)
    {
        $count = $this->qb()
                      ->select('count(this)')
                      ->andWhere('this.user = :user')
                      ->setParameter('user', $userId)
                      ->getQB()
                      ->getQuery()
                      ->getSingleScalarResult()
        ;

        return $count;
    }

    public function getTimelinedActivities($userId, $format = 'F j')
    {
        $activities = $this->createActivitiesQuery($userId)->getResult();
        $timeline = array();

        foreach ($activities as $activity) {
            $date = $activity->getDateAdded($format);
            $timeline[$date][] = $activity;
        }

        return $timeline;
    }
}