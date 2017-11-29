<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MessageImage
 */
class MessageImage
{
    /**
     * @var integer
     */
    private $messageImageId;

    /**
     * @var string
     */
    private $fileLocation;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Message
     */
    private $message;


    /**
     * Get messageImageId
     *
     * @return integer 
     */
    public function getMessageImageId()
    {
        return $this->messageImageId;
    }

    /**
     * Set fileLocation
     *
     * @param string $fileLocation
     * @return MessageImage
     */
    public function setFileLocation($fileLocation)
    {
        $this->fileLocation = $fileLocation;

        return $this;
    }

    /**
     * Get fileLocation
     *
     * @return string 
     */
    public function getFileLocation()
    {
        return $this->fileLocation;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return MessageImage
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
     * Set message
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $message
     * @return MessageImage
     */
    public function setMessage(\Yilinker\Bundle\CoreBundle\Entity\Message $message = null)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Message 
     */
    public function getMessage()
    {
        return $this->message;
    }
}
