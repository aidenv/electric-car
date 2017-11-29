<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\Common\Collections\Criteria;
/**
 * MobileFeedBack
 */
class MobileFeedBack
{
 
    const USER_BUYER = 0;
    const USER_AFFILIATE = 1;
    const USER_SELLER = 2;
    const USER_GUEST = 3;

    protected $userTypes = array(
        self::USER_BUYER => 'Buyer',
        self::USER_AFFILIATE => 'Affiliate',
        self::USER_SELLER => 'Seller',
        self::USER_GUEST => 'Guest'
    );

    /**
     * @var integer
     */
    private $mobileFeedbackId;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $phoneModel;

    /**
     * @var string
     */
    private $osVersion;

    /**
     * @var string
     */
    private $osName;

    /**
     * @var integer
     */
    private $userType;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $mobileFeedbackAdmins;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\User
     */
    private $user;

    /**
     * @var \DateTime
     */
    private $dateAdded;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->mobileFeedbackAdmins = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get mobileFeedbackId
     *
     * @return integer 
     */
    public function getMobileFeedbackId()
    {
        return $this->mobileFeedbackId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return MobileFeedBack
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return MobileFeedBack
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set phoneModel
     *
     * @param string $phoneModel
     * @return MobileFeedBack
     */
    public function setPhoneModel($phoneModel)
    {
        $this->phoneModel = $phoneModel;

        return $this;
    }

    /**
     * Get phoneModel
     *
     * @return string 
     */
    public function getPhoneModel()
    {
        return $this->phoneModel;
    }

    /**
     * Set osVersion
     *
     * @param string $osVersion
     * @return MobileFeedBack
     */
    public function setOsVersion($osVersion)
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    /**
     * Get osVersion
     *
     * @return string 
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }

    /**
     * Set osName
     *
     * @param string $osName
     * @return MobileFeedBack
     */
    public function setOsName($osName)
    {
        $this->osName = $osName;

        return $this;
    }

    /**
     * Get osName
     *
     * @return string 
     */
    public function getOsName()
    {
        return $this->osName;
    }

    /**
     * Add mobileFeedbackAdmins
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\MobileFeedBackAdmin $mobileFeedbackAdmins
     * @return MobileFeedBack
     */
    public function addMobileFeedbackAdmin(\Yilinker\Bundle\CoreBundle\Entity\MobileFeedBackAdmin $mobileFeedbackAdmins)
    {
        $this->mobileFeedbackAdmins[] = $mobileFeedbackAdmins;

        return $this;
    }

    /**
     * Remove mobileFeedbackAdmins
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\MobileFeedBackAdmin $mobileFeedbackAdmins
     */
    public function removeMobileFeedbackAdmin(\Yilinker\Bundle\CoreBundle\Entity\MobileFeedBackAdmin $mobileFeedbackAdmins)
    {
        $this->mobileFeedbackAdmins->removeElement($mobileFeedbackAdmins);
    }

    /**
     * Get mobileFeedbackAdmins
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMobileFeedbackAdmins()
    {
        return $this->mobileFeedbackAdmins;
    }

    /**
     * Set user
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $user
     * @return MobileFeedBack
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
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return MobileFeedBack
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


    public function getIsSeen($admin)
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq("adminUser", $admin))
            ->setFirstResult(0)
            ->setMaxResults(1);

        $mobileFeedbackAdmin = $this->getMobileFeedbackAdmins()->matching($criteria)->first();

        return $mobileFeedbackAdmin ? true : false;
    }

    /**
     * Set userType
     *
     * @param integer $userType
     * @return MobileFeedBack
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return integer 
     */
    public function getUserType($isText=false)
    {
        if ($isText) {
           return $this->userTypes[$this->userType];
        }

        return $this->userType;
    }
}
