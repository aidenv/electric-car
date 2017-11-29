<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserActivityType
 */
class UserActivityType
{
    /**
     * @var integer
     */
    private $userActivityTypeId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userActivityHistories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userActivityHistories = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get userActivityTypeId
     *
     * @return integer 
     */
    public function getUserActivityTypeId()
    {
        return $this->userActivityTypeId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserActivityType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add userActivityHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories
     * @return UserActivityType
     */
    public function addUserActivityHistory(\Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories)
    {
        $this->userActivityHistories[] = $userActivityHistories;

        return $this;
    }

    /**
     * Remove userActivityHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories
     */
    public function removeUserActivityHistory(\Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories)
    {
        $this->userActivityHistories->removeElement($userActivityHistories);
    }

    /**
     * Get userActivityHistories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserActivityHistories()
    {
        return $this->userActivityHistories;
    }
}
