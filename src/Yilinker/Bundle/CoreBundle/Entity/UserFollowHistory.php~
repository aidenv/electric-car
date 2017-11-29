<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * UserFollowHistory
 */
class UserFollowHistory
{
    /**
     * @var integer
     */
    private $userFollowHistoryId;

    /**
     * @var boolean
     */
    private $isFollow;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var User
     */
    private $follower;

    /**
     * @var User
     */
    private $followee;


    /**
     * Get userFollowHistoryId
     *
     * @return integer 
     */
    public function getUserFollowHistoryId()
    {
        return $this->userFollowHistoryId;
    }

    /**
     * Set isFollow
     *
     * @param boolean $isFollow
     * @return UserFollowHistory
     */
    public function setIsFollow($isFollow)
    {
        $this->isFollow = $isFollow;

        return $this;
    }

    /**
     * Get isFollow
     *
     * @return boolean 
     */
    public function getIsFollow()
    {
        return $this->isFollow;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     * @return UserFollowHistory
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime 
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set follower
     *
     * @param User $follower
     * @return UserFollowHistory
     */
    public function setFollower(User $follower = null)
    {
        $this->follower = $follower;

        return $this;
    }

    /**
     * Get follower
     *
     * @return User
     */
    public function getFollower()
    {
        return $this->follower;
    }

    /**
     * Set followee
     *
     * @param User $followee
     * @return UserFollowHistory
     */
    public function setFollowee(User $followee = null)
    {
        $this->followee = $followee;

        return $this;
    }

    /**
     * Get followee
     *
     * @return User
     */
    public function getFollowee()
    {
        return $this->followee;
    }
}
