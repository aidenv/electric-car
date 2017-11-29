<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\EarningType;
use Yilinker\Bundle\CoreBundle\Entity\Utility\EntityServiceable;

/**
 * Store
 */
class Store extends EntityServiceable
{
    const STORE_TYPE_MERCHANT = 0;

    const STORE_TYPE_RESELLER = 1;

    const ACCREDITATION_INCOMPLETE = 0;

    const ACCREDITATION_WAITING = 1;

    const ACCREDITATION_COMPLETE = 2;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var string
     */
    private $storeName;

    /**
     * @var string
     */
    private $storeNumber;

    /**
     * @var string
     */
    private $storeDescription = '';

    /**
     * @var string
     */
    private $storeSlug;

    /**
     * @var User
     */
    private $user;

    /**
     * @var boolean
     */
    private $slugChanged = false;

    /**
     * @var boolean
     */
    private $hasCustomCategory = false;

    /**
     * @var int
     */
    private $storeType = self::STORE_TYPE_MERCHANT;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel
     */
    private $accreditationLevel;

    /**
     * Specialty Category
     * Not mapped to the Store table. Hydrated by elastica
     *
     * @param array
     */
    private $specialtyCategory;

    /**
     * @var string
     */
    private $qrCodeLocation = '';

    /**
     * @var boolean
     */
    private $isEditable = '1';

    /**
     * @var boolean
     */
    private $isInhouse = '0';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\StoreLevel
     */
    private $storeLevel;

    /**
     * @var integer
     */
    private $storeViews = '0';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reviews = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set accreditationLevel
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel $accreditationLevel
     * @return Store
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
     * Get storeId
     *
     * @return integer
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Set storeName
     *
     * @param string $storeName
     * @return Store
     */
    public function setStoreName($storeName)
    {
        $this->storeName = $storeName;

        return $this;
    }

    /**
     * Get storeName
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * Set storeDescription
     *
     * @param string $storeDescription
     * @return Store
     */
    public function setStoreDescription($storeDescription)
    {
        $this->storeDescription = $storeDescription;

        return $this;
    }

    /**
     * Get storeDescription
     *
     * @return string
     */
    public function getStoreDescription()
    {
        return $this->storeDescription;
    }

    /**
     * Set storeSlug
     *
     * @param string $storeSlug
     * @return StoreDetails
     */
    public function setStoreSlug($storeSlug)
    {
        $this->storeSlug = $storeSlug;

        return $this;
    }

    /**
     * Get storeSlug
     *
     * @return string
     */
    public function getStoreSlug()
    {
        return $this->storeSlug;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Store
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set slugChanged
     *
     * @param boolean $slugChanged
     * @return StoreDetails
     */
    public function setSlugChanged($slugChanged)
    {
        $this->slugChanged = $slugChanged;

        return $this;
    }

    /**
     * Get slugChanged
     *
     * @return boolean
     */
    public function getSlugChanged()
    {
        return $this->slugChanged;
    }

    /**
     * Set storeType
     *
     * @param int $storeType
     * @return Store
     */
    public function setStoreType($storeType)
    {
        $this->storeType = $storeType;

        return $this;
    }

    /**
     * Get storeType
     *
     * @return int
     */
    public function getStoreType()
    {
        return $this->storeType;
    }

    /**
     * Set hasCustomCategory
     *
     * @param boolean $hasCustomCategory
     * @return Store
     */
    public function setHasCustomCategory($hasCustomCategory)
    {
        $this->hasCustomCategory = $hasCustomCategory;

        return $this;
    }

    /**
     * Get hasCustomCategory
     *
     * @return boolean
     */
    public function getHasCustomCategory()
    {
        return $this->hasCustomCategory;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $reviews;

    /**
     * Add reviews
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedback $reviews
     * @return Store
     */
    public function addReview(\Yilinker\Bundle\CoreBundle\Entity\UserFeedback $reviews)
    {
        $this->reviews[] = $reviews;

        return $this;
    }

    /**
     * Remove reviews
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserFeedback $reviews
     */
    public function removeReview(\Yilinker\Bundle\CoreBundle\Entity\UserFeedback $reviews)
    {
        $this->reviews->removeElement($reviews);
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * Check if the user has review
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User $reviewer
     * @param Yilinker\Bundle\CoreBundle\Entity\UserOrder $Order
     * @return boolean
     */
    public function hasReview(\Yilinker\Bundle\CoreBundle\Entity\User $reviewer = null, \Yilinker\Bundle\CoreBundle\Entity\UserOrder $order = null)
    {
        $review = $this->reviews;

        $criteria = Criteria::create();
        if($reviewer){
            $criteria->andWhere(Criteria::expr()->eq('reviewer', $reviewer));
        }
        if($order){
            $criteria->andWhere(Criteria::expr()->eq('order', $order));
        }

        $reviews = $this->getReviews()->matching($criteria);

        return $reviews->count() > 0;
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPaginatedReviews($limit, $offset)
    {
        $criteria = Criteria::create()
                        ->setFirstResult($offset)
                        ->setMaxResults($limit);
        $reviews = $this->getReviews()->matching($criteria);

        return $reviews;
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRating()
    {
        $rating = 0.00;
        $reviews = $this->getReviews()->matching(Criteria::create());

        if(count($reviews) > 0){
            foreach($reviews as $review){
                $rating += floatval($review->getRating());
            }

            $rating = ($rating/count($reviews));
        }

        return $rating;
    }

    /**
     * Set the specialty product category
     * Persisted from elastica result
     *
     * @param array $specialtyCategory
     */
    public function setSpecialtyCategory($specialtyCategory)
    {
        $this->specialtyCategory = $specialtyCategory;
    }

    /**
     * Returns the specialty category
     *
     * @return array
     */
    public function getSpecialtyCategory()
    {
        return $this->specialtyCategory;
    }

    public function __toString()
    {
        return $this->getStoreName();
    }


    /**
     * Get clean descriptions
     *
     * @return string
     */
    public function getCleanDescription()
    {
        return trim(preg_replace('/\s\s+/', ' ', $this->storeDescription));
    }

    /**
     * Set qrCodeLocation
     *
     * @param string $qrCodeLocation
     * @return Store
     */
    public function setQrCodeLocation($qrCodeLocation)
    {
        $this->qrCodeLocation = $qrCodeLocation;

        return $this;
    }

    /**
     * Get qrCodeLocation
     *
     * @return string
     */
    public function getQrCodeLocation()
    {
        return $this->storeId.'/'.$this->qrCodeLocation;
    }

    /**
     * Returns if the store is an affliate store or not
     *
     * @return boolean
     */
    public function isAffiliate()
    {
        return (int) $this->getStoreType() === self::STORE_TYPE_RESELLER;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $storeCategories;


    /**
     * Add storeCategories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\StoreCategory $storeCategories
     * @return Store
     */
    public function addStoreCategory(\Yilinker\Bundle\CoreBundle\Entity\StoreCategory $storeCategories)
    {
        $this->storeCategories[] = $storeCategories;

        return $this;
    }

    /**
     * Remove storeCategories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\StoreCategory $storeCategories
     */
    public function removeStoreCategory(\Yilinker\Bundle\CoreBundle\Entity\StoreCategory $storeCategories)
    {
        $this->storeCategories->removeElement($storeCategories);
    }

    /**
     * Get storeCategories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStoreCategories()
    {
        return $this->storeCategories;
    }

    /**
     * Retrieve product categories directly
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStoreProductCategories()
    {
        $categories = new \Doctrine\Common\Collections\ArrayCollection();
        foreach($this->storeCategories as $storeCategory){
            $categories[] = $storeCategory->getProductCategory();
        }

        return $categories;
    }

    /**
     * Set storeNumber
     *
     * @param string $storeNumber
     * @return Store
     */
    public function setStoreNumber($storeNumber)
    {
        $this->storeNumber = $storeNumber;

        return $this;
    }

    /**
     * Get storeNumber
     *
     * @return string
     */
    public function getStoreNumber()
    {
        return $this->storeNumber;
    }

    /**
     * Set isEditable
     *
     * @param boolean $isEditable
     * @return Store
     */
    public function setIsEditable($isEditable)
    {
        $this->isEditable = $isEditable;

        return $this;
    }

    /**
     * Get isEditable
     *
     * @return boolean
     */
    public function getIsEditable()
    {
        return $this->isEditable;
    }

    /**
     * Set isInhouse
     *
     * @param boolean $isInhouse
     * @return Store
     */
    public function setIsInhouse($isInhouse)
    {
        $this->isInhouse = $isInhouse;

        return $this;
    }

    /**
     * Get isInhouse
     *
     * @return boolean
     */
    public function getIsInhouse()
    {
        return $this->isInhouse;
    }

    /**
     * Set storeLevel
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\StoreLevel $storeLevel
     * @return Store
     */
    public function setStoreLevel(\Yilinker\Bundle\CoreBundle\Entity\StoreLevel $storeLevel = null)
    {
        $this->storeLevel = $storeLevel;

        return $this;
    }

    /**
     * Get storeLevel
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\StoreLevel
     */
    public function getStoreLevel()
    {
        return $this->storeLevel;
    }

    /**
     * Set storeViews
     *
     * @param integer $storeViews
     * @return Store
     */
    public function setStoreViews($storeViews)
    {
        $this->storeViews = $storeViews;

        return $this;
    }

    /**
     * Get storeViews
     *
     * @return integer
     */
    public function getStoreViews()
    {
        return $this->storeViews;
    }

    public function getEarningPrivilege()
    {
        $privilege = array(EarningType::PRIVILEGE_LEVEL_BOTH);

        if($this->isAffiliate()){
            array_push($privilege, EarningType::PRIVILEGE_LEVEL_AFFILIATE);
        }
        else{
            array_push($privilege, EarningType::PRIVILEGE_LEVEL_SELLER);
        }

        return $privilege;
    }

    public function ableToWithdraw()
    {
        return
            $this->getStoreType() == self::STORE_TYPE_MERCHANT ?
            true:
            $this->getAccreditationLevel()
        ;
    }

    public function hasApprovedLegalDoc()
    {

        if(
            !$this->getUser()->getAccreditationApplication() ||
            !$this->getUser()->getAccreditationApplication()->hasApprovedLegalDocument()
        ){
            return false;
        }

        return true;
    }

    public function hasApprovedBank()
    {

        if(
            !$this->getUser()->getAccreditationApplication() ||
            !$this->getUser()->getAccreditationApplication()->getIsBankApproved()
        ){
            return false;
        }

        return true;
    }

    public function getAccreditationStatus()
    {
        if(
            (
                $this->getUser()->getDefaultBank() &&
                $this->getUser()->getAccreditationApplication() &&
                $this->getUser()->getAccreditationApplication()->getIsBankApproved()
            ) && (
                $this->getUser()->getAccreditationApplication() &&
                $this->getUser()->getAccreditationApplication()->hasLegalDocument() &&
                $this->getUser()->getAccreditationApplication()->hasApprovedLegalDocument()
            )
        ){
            return self::ACCREDITATION_COMPLETE;
        }
        elseif(
            (
                $this->getUser()->getDefaultBank() &&
                $this->getUser()->getAccreditationApplication() &&
                !$this->getUser()->getAccreditationApplication()->getIsBankApproved()
            ) && (
                $this->getUser()->getAccreditationApplication() &&
                $this->getUser()->getAccreditationApplication()->hasLegalDocument() &&
                !$this->getUser()->getAccreditationApplication()->hasApprovedLegalDocument()
            )
        ){
            return self::ACCREDITATION_WAITING;
        }
        else{
            return self::ACCREDITATION_INCOMPLETE;
        }

    }

    /**
     * Get store details
     *
     * @return mixed
     */
    public function getStoreDetails()
    {
        $user = $this->user;
        return array(
            'sellerName'    => $user->getFullName(),
            'storeName'     => $this->storeName,
            'contactNumber' => $user->getContactNumber(),
        );
    }
}
