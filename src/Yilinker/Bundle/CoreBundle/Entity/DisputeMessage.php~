<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DisputeMessage
 */
class DisputeMessage
{

    /**
     * @var integer
     */
    private $disputeMessageId;

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var integer
     */
    private $isAdmin = 1;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Dispute
     */
    private $dispute;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AdminUser
     */
    private $author;

    /**
     * Get disputeMessageId
     *
     * @return integer 
     */
    public function getDisputeMessageId()
    {
        return $this->disputeMessageId;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return DisputeMessage
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
     * @return DisputeMessage
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
     * Set isAdmin
     *
     * @param integer $isAdmin
     * @return DisputeMessage
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return integer 
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Set dispute
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Dispute $dispute
     * @return DisputeMessage
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
     * Set authorId
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AdminUser $author
     * @return DisputeMessage
     */
    public function setAuthor(\Yilinker\Bundle\CoreBundle\Entity\AdminUser $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get authorId
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AdminUser 
     */
    public function getAuthor()
    {
        return $this->author;
    }

}
