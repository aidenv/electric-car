<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccreditationApplicationHistory
 */
class AccreditationApplicationHistory
{

    /**
     * @var integer
     */
    private $accreditationApplicationHistoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus
     */
    private $accreditationApplicationStatus;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication
     */
    private $accreditationApplication;


    /**
     * Get accreditationApplicationHistoryId
     *
     * @return integer 
     */
    public function getAccreditationApplicationHistoryId()
    {
        return $this->accreditationApplicationHistoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return AccreditationApplicationHistory
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
     * Set accreditationApplicationStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus $accreditationApplicationStatus
     * @return AccreditationApplicationHistory
     */
    public function setAccreditationApplicationStatus(\Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus $accreditationApplicationStatus = null)
    {
        $this->accreditationApplicationStatus = $accreditationApplicationStatus;

        return $this;
    }

    /**
     * Get accreditationApplicationStatus
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus 
     */
    public function getAccreditationApplicationStatus()
    {
        return $this->accreditationApplicationStatus;
    }

    /**
     * Set accreditationApplication
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication $accreditationApplication
     * @return AccreditationApplicationHistory
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
