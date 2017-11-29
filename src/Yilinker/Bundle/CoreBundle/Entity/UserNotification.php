<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class UserNotification
{
    /**
     * @var integer
     */
    private $userNotificationId;

    /**
     * @var string
     */
    private $affectedTable;

    /**
     * @var string
     */
    private $mysqlAction;

    /**
     * @var string
     */
    private $data;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;


    /**
     * Get userNotificationId
     *
     * @return integer 
     */
    public function getUserNotificationId()
    {
        return $this->userNotificationId;
    }

    /**
     * Set affectedTable
     *
     * @param string $affectedTable
     * @return UserNotification
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
     * Set mysqlAction
     *
     * @param string $mysqlAction
     * @return UserNotification
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

    /**
     * Set data
     *
     * @param string $data
     * @return UserNotification
     */
    public function setData($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string 
     */
    public function getData()
    {
        $data = json_decode($this->data, true);

        return $data;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserNotification
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
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return UserNotification
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
}
