<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Message
 */
class Message
{
    /**
     * @var integer
     */
    private $messageId;

    /**
     * @var string
     */
    private $message;

    /**
     * @var boolean
     */
    private $isImage;

    /**
     * @var \DateTime
     */
    private $timeSent;

    /**
     * @var boolean
     */
    private $isDeleteSender;

    /**
     * @var boolean
     */
    private $isDeleteRecipient;

    /**
     * @var boolean
     */
    private $isSeen;

    /**
     * @var \DateTime
     */
    private $timeSeen;

    /**
     * @var User
     */
    private $sender;

    /**
     * @var User
     */
    private $recipient;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $images;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get messageId
     *
     * @return integer 
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Message
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
     * Set isImage
     *
     * @param boolean $isImage
     * @return Message
     */
    public function setIsImage($isImage)
    {
        $this->isImage = $isImage;

        return $this;
    }

    /**
     * Get isImage
     *
     * @return boolean 
     */
    public function getIsImage()
    {
        return $this->isImage;
    }

    /**
     * Set timeSent
     *
     * @param \DateTime $timeSent
     * @return Message
     */
    public function setTimeSent($timeSent)
    {
        $this->timeSent = $timeSent;

        return $this;
    }

    /**
     * Get timeSent
     *
     * @return \DateTime 
     */
    public function getTimeSent()
    {
        return $this->timeSent;
    }

    /**
     * Set isDeleteSender
     *
     * @param boolean $isDeleteSender
     * @return Message
     */
    public function setIsDeleteSender($isDeleteSender)
    {
        $this->isDeleteSender = $isDeleteSender;

        return $this;
    }

    /**
     * Get isDeleteSender
     *
     * @return boolean 
     */
    public function getIsDeleteSender()
    {
        return $this->isDeleteSender;
    }

    /**
     * Set isDeleteRecipient
     *
     * @param boolean $isDeleteRecipient
     * @return Message
     */
    public function setIsDeleteRecipient($isDeleteRecipient)
    {
        $this->isDeleteRecipient = $isDeleteRecipient;

        return $this;
    }

    /**
     * Get isDeleteRecipient
     *
     * @return boolean 
     */
    public function getIsDeleteRecipient()
    {
        return $this->isDeleteRecipient;
    }

    /**
     * Set isSeen
     *
     * @param boolean $isSeen
     * @return Message
     */
    public function setIsSeen($isSeen)
    {
        $this->isSeen = $isSeen;

        return $this;
    }

    /**
     * Get isSeen
     *
     * @return boolean 
     */
    public function getIsSeen()
    {
        return $this->isSeen;
    }

    /**
     * Set timeSeen
     *
     * @param \DateTime $timeSeen
     * @return Message
     */
    public function setTimeSeen($timeSeen)
    {
        $this->timeSeen = $timeSeen;

        return $this;
    }

    /**
     * Get timeSeen
     *
     * @return \DateTime 
     */
    public function getTimeSeen()
    {
        return $this->timeSeen;
    }

    /**
     * Set senderId
     *
     * @param User $senderId
     * @return Message
     */
    public function setSender(User $sender = null)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get senderId
     *
     * @return User
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set recipientId
     *
     * @param User $recipientId
     * @return Message
     */
    public function setRecipient(User $recipient = null)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Get recipientId
     *
     * @return User
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Add images
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $images
     * @return Message
     */
    public function addImage(\Yilinker\Bundle\CoreBundle\Entity\Message $images)
    {
        $this->images[] = $images;

        return $this;
    }

    /**
     * Remove images
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $images
     */
    public function removeImage(\Yilinker\Bundle\CoreBundle\Entity\Message $images)
    {
        $this->images->removeElement($images);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getImages()
    {
        return $this->images;
    }
}
