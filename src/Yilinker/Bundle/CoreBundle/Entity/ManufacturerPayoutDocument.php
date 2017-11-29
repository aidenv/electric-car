<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * ManufacturerPayoutDocument
 */
class ManufacturerPayoutDocument
{

    /**
     * @var integer
     */
    private $manufacturerPayoutDocumentId;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout
     */
    private $manufacturerPayout;

    private $file;

    /**
     * Get manufacturerPayoutDocumentId
     *
     * @return integer 
     */
    public function getManufacturerPayoutDocumentId()
    {
        return $this->manufacturerPayoutDocumentId;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return ManufacturerPayoutDocument
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
     * @return ManufacturerPayoutDocument
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
     * Set manufacturerPayout
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout $manufacturerPayout
     * @return ManufacturerPayoutDocument
     */
    public function setManufacturerPayout(\Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout $manufacturerPayout = null)
    {
        $this->manufacturerPayout = $manufacturerPayout;

        return $this;
    }

    /**
     * Get manufacturerPayout
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\ManufacturerPayout 
     */
    public function getManufacturerPayout()
    {
        return $this->manufacturerPayout;
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
        return 'assets/images/uploads/manufacturer_payouts';
    }

    public function getFilepath()
    {
        return $this->getManufacturerPayout()->getManufacturerPayoutId().'/'.$this->filename;
    }
}
