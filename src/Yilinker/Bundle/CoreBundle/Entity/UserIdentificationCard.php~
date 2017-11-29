<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * UserIdentificationCard
 */
class UserIdentificationCard
{

    /**
     * @var integer
     */
    private $userIdentificationCardId;

    /**
     * @var string
     */
    private $filename = '';

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    private $file;

    /**
     * Get user_identification_card_id
     *
     * @return integer 
     */
    public function getUserIdentificationCardId()
    {
        return $this->userIdentificationCardId;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return UserIdentificationCard
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return UserIdentificationCard
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
     * @return UserIdentificationCard
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
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    public function getWebPath()
    {
        return null === $this->filename ? null : $this->getUploadDir().'/'.$this->getFilepath();
    }

    public function getUploadDir()
    {
        return 'assets/images/uploads/user_documents';
    }

    public function getFilepath()
    {
        return $this->getUser()->getUserId().'/'.$this->filename;
    }

}
