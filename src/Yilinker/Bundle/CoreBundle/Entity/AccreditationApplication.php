<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * AccreditationApplication
 */
class AccreditationApplication
{

    const SELLER_TYPE_MERCHANT = 0;

    const SELLER_TYPE_RESELLER = 1;

    const USER_APPLICATION_TYPE_ACCREDITED = 1;

    const USER_APPLICATION_TYPE_UNACCREDITED = 2;

    const USER_APPLICATION_TYPE_ALL = 3;

    const USER_APPLICATION_TYPE_WAITING = 4;

    const BUSINESS_INFORMATION_PERCENTAGE = 30;

    const BANK_INFORMATION_PERCENTAGE = 30;

    const DTI_FILE_PERCENTAGE = 10;

    const MAYORS_FILE_PERCENTAGE = 10;

    const BIR_FILE_PERCENTAGE = 10;

    const FORM_FILE_PERCENTAGE = 10;

    const TIN_PERCENTAGE = 20;

    const SSS_PERCENTAGE = 20;

    const PAGIBIG_PERCENTAGE = 20;

    const POSTAL_PERCENTAGE = 20;

    const PASSPORT_PERCENTAGE = 20;

    const DRIVERS_PERCENTAGE = 20;

    const PRC_PERCENTAGE = 20;

    const VOTERS_PERCENTAGE = 20;

    const SCHOOL_PERCENTAGE = 20;

    /**
     * @var integer
     */
    private $accreditationApplicationId;

    /**
     * @var integer
     */
    private $sellerType = 0;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $lastModifiedDate;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus
     */
    private $accreditationApplicationStatus;

    /**
     * @var string
     */
    private $businessWebsiteUrl;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel
     */
    private $accreditationLevel;

    /**
     * @var integer
     */
    private $isBusinessApproved = 0;

    /**
     * @var integer
     */
    private $isBankApproved = 0;

    /**
     * @var integer
     */
    private $isBusinessEditable = 0;

    /**
     * @var integer
     */
    private $isBankEditable = 0;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $legalDocuments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->legalDocuments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get accreditationApplicationId
     *
     * @return integer
     */
    public function getAccreditationApplicationId()
    {
        return $this->accreditationApplicationId;
    }

    /**
     * Set sellerType
     *
     * @param integer $sellerType
     * @return AccreditationApplication
     */
    public function setSellerType($sellerType)
    {
        $this->sellerType = $sellerType;

        return $this;
    }

    /**
     * Get sellerType
     *
     * @return integer
     */
    public function getSellerType()
    {
        return $this->sellerType;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return AccreditationApplication
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
     * Set lastModifiedDate
     *
     * @param \DateTime $lastModifiedDate
     * @return AccreditationApplication
     */
    public function setLastModifiedDate($lastModifiedDate)
    {
        $this->lastModifiedDate = $lastModifiedDate;

        return $this;
    }

    /**
     * Get lastModifiedDate
     *
     * @return \DateTime
     */
    public function getLastModifiedDate()
    {
        return $this->lastModifiedDate;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return AccreditationApplication
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
     * Set accreditationApplicationStatus
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus $accreditationApplicationStatus
     * @return AccreditationApplication
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
     * Set businessWebsiteUrl
     *
     * @param string $businessWebsiteUrl
     * @return AccreditationApplication
     */
    public function setBusinessWebsiteUrl($businessWebsiteUrl)
    {
        $this->businessWebsiteUrl = $businessWebsiteUrl;

        return $this;
    }

    /**
     * Get businessWebsiteUrl
     *
     * @return string
     */
    public function getBusinessWebsiteUrl()
    {
        return $this->businessWebsiteUrl;
    }

    /**
     * Set accreditationLevel
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel $accreditationLevel
     * @return AccreditationApplication
     */
    public function setAccreditationLevel(\Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel $accreditationLevel = null)
    {
        $this->accreditationLevel = $accreditationLevel;

        return $this;
    }

    /**
     * Get accreditationLevel
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel
     */
    public function getAccreditationLevel()
    {
        return $this->accreditationLevel;
    }

    /**
     * Get Application Status
     *
     * @return string
     */
    public function getApplicationStatus ()
    {
        return $this->accreditationLevel !== null ? 'Accredited' : 'Waiting for Accreditation';
    }

    /**
     * Set isBusinessApproved
     *
     * @param integer $isBusinessApproved
     * @return AccreditationApplication
     */
    public function setIsBusinessApproved($isBusinessApproved)
    {
        $this->isBusinessApproved = $isBusinessApproved;

        return $this;
    }

    /**
     * Get isBusinessApproved
     *
     * @return integer
     */
    public function getIsBusinessApproved()
    {
        return $this->isBusinessApproved;
    }

    /**
     * Set isBankApproved
     *
     * @param integer $isBankApproved
     * @return AccreditationApplication
     */
    public function setIsBankApproved($isBankApproved)
    {
        $this->isBankApproved = $isBankApproved;

        return $this;
    }

    /**
     * Get isBankApproved
     *
     * @return integer
     */
    public function getIsBankApproved()
    {
        return $this->isBankApproved;
    }

    /**
     * Set isBusinessEditable
     *
     * @param integer $isBusinessEditable
     * @return AccreditationApplication
     */
    public function setIsBusinessEditable($isBusinessEditable)
    {
        $this->isBusinessEditable = $isBusinessEditable;

        return $this;
    }

    /**
     * Get isBusinessEditable
     *
     * @return integer
     */
    public function getIsBusinessEditable()
    {
        return $this->isBusinessEditable;
    }

    /**
     * Set isBankEditable
     *
     * @param integer $isBankEditable
     * @return AccreditationApplication
     */
    public function setIsBankEditable($isBankEditable)
    {
        $this->isBankEditable = $isBankEditable;

        return $this;
    }

    /**
     * Get isBankEditable
     *
     * @return integer
     */
    public function getIsBankEditable()
    {
        return $this->isBankEditable;
    }

    /**
     * Add legalDocuments
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LegalDocument $legalDocuments
     * @return AccreditationApplication
     */
    public function addLegalDocument(\Yilinker\Bundle\CoreBundle\Entity\LegalDocument $legalDocuments)
    {
        $this->legalDocuments[] = $legalDocuments;

        return $this;
    }

    /**
     * Remove legalDocuments
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LegalDocument $legalDocuments
     */
    public function removeLegalDocument(\Yilinker\Bundle\CoreBundle\Entity\LegalDocument $legalDocuments)
    {
        $this->legalDocuments->removeElement($legalDocuments);
    }

    /**
     * Get legalDocuments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLegalDocuments()
    {
        return $this->legalDocuments;
    }

    public function getLegalDocumentByType($legalDocumentType)
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("legalDocumentType", $legalDocumentType))
                            ->setFirstResult(0)
                            ->setMaxResults(1);

        $legalDocument = $this->getLegalDocuments()->matching($criteria)->first();

        return $legalDocument;
    }

    public function hasLegalDocument()
    {
        if(sizeof($this->getLegalDocuments()) > 0){
            return true;
        }

        return false;
    }

    public function hasApprovedLegalDocument()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isApproved", 1))
                            ->setFirstResult(0)
                            ->setMaxResults(1);

        $legalDocument = $this->getLegalDocuments()->matching($criteria)->first();

        return $legalDocument;
    }

    /**
     * @var integer
     */
    private $resourceId = 0;


    /**
     * Set resourceId
     *
     * @param integer $resourceId
     * @return AccreditationApplication
     */
    public function setResourceId($resourceId)
    {
    	$this->resourceId = $resourceId;

    	return $this;
    }

    /**
     * Get resourceId
     *
     * @return integer
     */
    public function getResourceId()
    {
    	return $this->resourceId;
    }
}
