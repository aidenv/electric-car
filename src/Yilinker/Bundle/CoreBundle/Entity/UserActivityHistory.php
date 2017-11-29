<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserActivityHistory
 */
class UserActivityHistory
{
    /**
     * @var integer
     */
    private $userActivityHistoryId;

    /**
     * @var string
     */
    private $affectedTable;

    /**
     * @var string
     */
    private $mysqlAction = '';

    /**
     * @var string
     */
    private $activityData;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserActivityType
     */
    private $userActivityType;


    /**
     * Get userActivityHistoryId
     *
     * @return integer 
     */
    public function getUserActivityHistoryId()
    {
        return $this->userActivityHistoryId;
    }

    /**
     * Set affectedTable
     *
     * @param string $affectedTable
     * @return UserActivityHistory
     */
    public function setAffectedTable($affectedTable)
    {
        $this->affectedTable = $affectedTable;

        return $this;
    }

    /**
     * Get affectedTable
     *
     * @return string 
     */
    public function getAffectedTable()
    {
        return $this->affectedTable;
    }

    /**
     * Set activityData
     *
     * @param string|array $activityData
     * @return UserActivityHistory
     */
    public function setActivityData($activityData)
    {
        if (is_array($activityData)) {
            $activityData = json_encode($activityData);
        }

        $this->activityData = $activityData;

        return $this;
    }

    /**
     * Get activityData
     *
     * @return string 
     */
    public function getActivityData()
    {
        $activityData = json_decode($this->activityData, true);

        return $activityData;
    }

    public function getChanges()
    {
        $data = $this->getActivityData();
        
        return array_key_exists('__changes', $data) ? $data['__changes']: array();
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserActivityHistory
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime 
     */
    public function getDateAdded($format = '')
    {
        $dateAdded = $this->dateAdded;
        if ($format && $dateAdded instanceof \DateTime) {
            $dateAdded = $dateAdded->format($format);
        }

        return $dateAdded;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserActivityHistory
     */
    public function setUser(\Yilinker\Bundle\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set userActivityType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserActivityType $userActivityType
     * @return UserActivityHistory
     */
    public function setUserActivityType(\Yilinker\Bundle\CoreBundle\Entity\UserActivityType $userActivityType = null)
    {
        $this->userActivityType = $userActivityType;

        return $this;
    }

    /**
     * Get userActivityType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserActivityType 
     */
    public function getUserActivityType()
    {
        return $this->userActivityType;
    }

    /**
     * Set mysqlAction
     *
     * @param string $mysqlAction
     * @return UserActivityHistory
     */
    public function setMysqlAction($mysqlAction)
    {
        $this->mysqlAction = $mysqlAction;

        return $this;
    }

    /**
     * Get mysqlAction
     *
     * @return string 
     */
    public function getMysqlAction()
    {
        return $this->mysqlAction;
    }
}
