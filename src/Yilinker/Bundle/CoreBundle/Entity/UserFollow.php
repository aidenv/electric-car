<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * UserFollow
 */
class UserFollow
{
    /**
     * @var integer
     */
    private $userFollowId;

    /**
     * @var User
     */
    private $follower;

    /**
     * @var User
     */
    private $followee;


    /**
     * Get userFollowId
     *
     * @return integer 
     */
    public function getUserFollowId()
    {
        return $this->userFollowId;
    }

    /**
     * Set follower
     *
     * @param User $follower
     * @return UserFollow
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
     * @return UserFollow
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
