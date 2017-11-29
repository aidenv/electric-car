<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApplicationRemark
 */
class ApplicationRemark
{

    /**
     * @var integer
     */
    private $applicationRemarkId;

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $adminUser;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType
     */
    private $applicationRemarkType;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication
     */
    private $accreditationApplication;

    /**
     * Get applicationRemarkId
     *
     * @return integer 
     */
    public function getApplicationRemarkId()
    {
        return $this->applicationRemarkId;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return ApplicationRemark
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return ApplicationRemark
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
     * Set adminUser
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser
     * @return ApplicationRemark
     */
    public function setAdminUser(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $adminUser = null)
    {
        $this->adminUser = $adminUser;

        return $this;
    }

    /**
     * Get adminUser
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getAdminUser()
    {
        return $this->adminUser;
    }

    /**
     * Set applicationRemarkType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType $applicationRemarkType
     * @return ApplicationRemark
     */
    public function setApplicationRemarkType(\Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType $applicationRemarkType = null)
    {
        $this->applicationRemarkType = $applicationRemarkType;

        return $this;
    }

    /**
     * Get applicationRemarkType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ApplicationRemarkType 
     */
    public function getApplicationRemarkType()
    {
        return $this->applicationRemarkType;
    }

    /**
     * Set accreditationApplication
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication $accreditationApplication
     * @return ApplicationRemark
     */
    public function setAccreditationApplication(\Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication $accreditationApplication = null)
    {
        $this->accreditationApplication = $accreditationApplication;

        return $this;
    }

    /**
     * Get accreditationApplication
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication 
     */
    public function getAccreditationApplication()
    {
        return $this->accreditationApplication;
    }
}
