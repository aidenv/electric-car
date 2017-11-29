<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * PayoutDocument
 */
class PayoutDocument
{
    /**
     * @var integer
     */
    private $payoutDocumentId;

    /**
     * @var integer
     */
    private $filename;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Payout
     */
    private $payout;

    private $file;
    
    /**
     * Get payoutDocumentId
     *
     * @return integer 
     */
    public function getPayoutDocumentId()
    {
        return $this->payoutDocumentId;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return PayoutDocument
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
     * @return PayoutDocument
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
     * Set payout
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Payout $payout
     * @return PayoutDocument
     */
    public function setPayout(\Yilinker\Bundle\CoreBundle\Entity\Payout $payout = null)
    {
        $this->payout = $payout;

        return $this;
    }

    /**
     * Get payout
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Payout 
     */
    public function getPayout()
    {
        return $this->payout;
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

    public static function getUploadDir()
    {
        return 'assets/images/uploads/payouts';
    }

    public function getFilepath()
    {
        return $this->getPayout()->getPayoutId().'/'.$this->filename;
    }

}
