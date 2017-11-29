<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DisputeHistory
 */
class DisputeHistory
{

    /**
     * @var integer
     */
    private $disputeHistoryId;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Dispute
     */
    private $dispute;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType
     */
    private $disputeStatusType;

    /**
     * Get disputeHistoryId
     *
     * @return integer 
     */
    public function getDisputeHistoryId()
    {
        return $this->disputeHistoryId;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return DisputeHistory
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
     * Set dispute
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute
     * @return DisputeHistory
     */
    public function setDispute(\Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute = null)
    {
        $this->dispute = $dispute;

        return $this;
    }

    /**
     * Get dispute
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Dispute 
     */
    public function getDispute()
    {
        return $this->dispute;
    }

    /**
     * Set disputeStatusType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType $disputeStatusType
     * @return DisputeHistory
     */
    public function setDisputeStatusType(\Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType $disputeStatusType = null)
    {
        $this->disputeStatusType = $disputeStatusType;

        return $this;
    }

    /**
     * Get disputeStatusType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\DisputeStatusType 
     */
    public function getDisputeStatusType()
    {
        return $this->disputeStatusType;
    }
}
