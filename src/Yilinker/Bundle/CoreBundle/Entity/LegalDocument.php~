<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LegalDocuments
 */
class LegalDocument
{

    /**
     * @var integer
     */
    private $legalDocumentsId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication
     */
    private $accreditationApplication;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType
     */
    private $legalDocumentType;

    /**
     * @var integer
     */
    private $isApproved = 0;

    /**
     * Get legalDocumentsId
     *
     * @return integer 
     */
    public function getLegalDocumentsId()
    {
        return $this->legalDocumentsId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return LegalDocument
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @var integer
     */
    private $isEditable = 0;

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return LegalDocument
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
     * Set dateLastModified
     *
     * @param \DateTime $dateLastModified
     * @return LegalDocument
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get dateLastModified
     *
     * @return \DateTime 
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set accreditationApplication
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication $accreditationApplication
     * @return LegalDocument
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

    /**
     * Set legalDocumentType
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType $legalDocumentType
     * @return LegalDocument
     */
    public function setLegalDocumentType(\Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType $legalDocumentType = null)
    {
        $this->legalDocumentType = $legalDocumentType;

        return $this;
    }

    /**
     * Get legalDocumentType
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType 
     */
    public function getLegalDocumentType()
    {
        return $this->legalDocumentType;
    }

    /**
     * Set isApproved
     *
     * @param integer $isApproved
     * @return LegalDocument
     */
    public function setIsApproved($isApproved)
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    /**
     * Get isApproved
     *
     * @return integer 
     */
    public function getIsApproved()
    {
        return $this->isApproved;
    }

    /**
     * Set isEditable
     *
     * @param integer $isEditable
     * @return LegalDocument
     */
    public function setIsEditable($isEditable)
    {
        $this->isEditable = $isEditable;

        return $this;
    }

    /**
     * Get isEditable
     *
     * @return integer 
     */
    public function getIsEditable()
    {
        return $this->isEditable;
    }
}
