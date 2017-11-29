<?php
namespace Yilinker\Bundle\CoreBundle\Entity;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Serializable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\UserFollow;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;
use Yilinker\Bundle\CoreBundle\Entity\Device;
use Yilinker\Bundle\FrontendBundle\Services\User\Login;

/**
 * User
 */
class User implements AdvancedUserInterface, Serializable
{
    /**
     * User type for buyer
     */
    const USER_TYPE_BUYER = 0;

    /**
     * User type for seller
     */
    const USER_TYPE_SELLER = 1;

    /**
     * User type for guest
     */
    const USER_TYPE_GUEST = 2;

    /**
     * User isActive
     */
    const USER_ACTIVE = 1;

    /**
     * User isInactive
     */
    const USER_INACTIVE = 0;

    /**
     * Registartion Type Wev
     */
    const REGISTRATION_TYPE_WEB = 0;

    /**
     * Registartion Type Wev
     */
    const REGISTRATION_TYPE_MOBILE = 1;

    const YLA_CONSUMER = 0;

    const YLA_CORPORATE = 1;
    
    const RESOURCE_BUYER_ID = 0;
    
    const RESOURCE_AFFILIATE_ID = 1;
    
    const RESOURCE_ALL_ID = 2;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $accountId;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $plainPassword;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $contactNumber;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var boolean
     */
    private $isActive;

    /**
     * @var boolean
     */
    private $isMobileVerified;

    /**
     * @var boolean
     */
    private $isEmailVerified;

    /**
     * @var integer
     */
    private $loginCount;

    /**
     * @var string
     */
    private $gender;

    /**
     * @var \DateTime
     */
    private $birthdate;

    /**
     * @var \DateTime
     */
    private $lastLoginDate;

    /**
     * @var \DateTime
     */
    private $lastLogoutDate;

    /**
     * @var string
     */
    private $lastLoginIp;

    /**
     * @var \DateTime
     */
    private $lastFailedLoginDate;

    /**
     * @var integer
     */
    private $failedLoginCount;

    /**
     * @var string
     */
    private $nickname;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var boolean
     */
    private $isBanned;

    /**
     * @var integer
     */
    private $banType;

    /**
     * @var integer
     */
    private $userType = '0';

    /**
     * @var \DateTime
     */
    private $lockDuration;

    /**
     * @var string
     */
    private $forgotPasswordToken;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $images;

    /**
     * @var \DateTime
     */
    private $forgotPasswordTokenExpiration;

    /**
     * @var string
     */
    private $forgotPasswordCode;

    /**
     * @var \DateTime
     */
    private $forgotPasswordCodeExpiration;

    /**
     * @var string
     */
    private $referralCode;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $addresses;

    /**
     * @var string
     */
    private $verificationSalt;

    /**
     * @var Store
     */
    private $store;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $followees;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $followers;

    /**
     * @var boolean
     */
    private $slugChanged;

    /**
     * @var
     */
    private $orders;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userActivityHistories;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserImage
     */
    private $primaryImage;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserImage
     */
    private $primaryCoverPhoto;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productUploads;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $devices;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $recievedMessages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $sentMessages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $notifications;

    /**
     * @var string
     */
    private $reactivationCode;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userVerificationTokens;

    /**
     * @var string
     */
    private $tin = '';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $bankAccounts;

    /**
     * @var boolean
     */
    private $isSocialMedia = '0';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $socialMediaAccounts;

    private $contactId;

    /**
     * @var integer
     */
    private $registrationType = '0';

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $products;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $earnings;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserReferral
     */
    private $userReferral;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $referrers;

    /**
     * @var integer
     */
    private $consecutiveLoginCount = '0';
    
    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication
     */
    private $accreditationApplication;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $inhouseProductUsers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->devices = new ArrayCollection();
        $this->recievedMessages = new ArrayCollection();
        $this->sentMessages = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->userVerificationTokens = new ArrayCollection();
        $this->addresses = new ArrayCollection();
        $this->earnings = new ArrayCollection();
        $this->inhouseProductUsers = new ArrayCollection();
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->userId;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->contactNumber;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set Plain Password
     *
     * @param string $plainPassword
     * @return User
     */
    public function setPlainPassword($plainPassword)
    {
       $this->plainPassword = $plainPassword;
       /** 
        * Set password to default non-encoded value to trigger
        * UserSubscriber preUpdate and prePersist events
        */
       $this->setPassword("password-placeholder");
       return $this;
    }

    /**
     * Get Plain Password
     *
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set contactNumber
     *
     * @param string $contactNumber
     * @return User
     */
    public function setContactNumber($contactNumber)
    {
        $this->contactNumber = $contactNumber;

        return $this;
    }

    /**
     * Get contactNumber
     *
     * @return string
     */
    public function getContactNumber()
    {
        return $this->contactNumber;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return User
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
     * @return User
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isMobileVerified
     *
     * @param boolean $isMobileVerified
     * @return User
     */
    public function setIsMobileVerified($isMobileVerified)
    {
        $this->isMobileVerified = $isMobileVerified;

        return $this;
    }

    /**
     * Get isMobileVerified
     *
     * @return boolean
     */
    public function getIsMobileVerified()
    {
        return $this->isMobileVerified;
    }

    /**
     * Set isEmailVerified
     *
     * @param boolean $isEmailVerified
     * @return User
     */
    public function setIsEmailVerified($isEmailVerified)
    {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    /**
     * Get isEmailVerified
     *
     * @return boolean
     */
    public function getIsEmailVerified()
    {
        return $this->isEmailVerified;
    }

    /**
     * Set loginCount
     *
     * @param integer $loginCount
     * @return User
     */
    public function setLoginCount($loginCount)
    {
        $this->loginCount = $loginCount;

        return $this;
    }

    /**
     * Get loginCount
     *
     * @return integer
     */
    public function getLoginCount()
    {
        return $this->loginCount;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return User
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set birthdate
     *
     * @param \DateTime $birthdate
     * @return User
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Get birthdate
     *
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set lastLoginDate
     *
     * @param \DateTime $lastLoginDate
     * @return User
     */
    public function setLastLoginDate($lastLoginDate)
    {
        $this->lastLoginDate = $lastLoginDate;

        return $this;
    }

    /**
     * Get lastLoginDate
     *
     * @return \DateTime
     */
    public function getLastLoginDate()
    {
        return $this->lastLoginDate;
    }

    /**
     * Set lastLoginIp
     *
     * @param string $lastLoginIp
     * @return User
     */
    public function setLastLoginIp($lastLoginIp)
    {
        $this->lastLoginIp = $lastLoginIp;

        return $this;
    }

    /**
     * Get lastLoginIp
     *
     * @return string
     */
    public function getLastLoginIp()
    {
        return $this->lastLoginIp;
    }

    /**
     * Set lastFailedLoginDate
     *
     * @param \DateTime $lastFailedLoginDate
     * @return User
     */
    public function setLastFailedLoginDate($lastFailedLoginDate)
    {
        $this->lastFailedLoginDate = $lastFailedLoginDate;

        return $this;
    }

    /**
     * Get lastFailedLoginDate
     *
     * @return \DateTime
     */
    public function getLastFailedLoginDate()
    {
        return $this->lastFailedLoginDate;
    }

    /**
     * Set failedLoginCount
     *
     * @param integer $failedLoginCount
     * @return User
     */
    public function setFailedLoginCount($failedLoginCount)
    {
        $this->failedLoginCount = $failedLoginCount;

        return $this;
    }

    /**
     * Get failedLoginCount
     *
     * @return integer
     */
    public function getFailedLoginCount()
    {
        return $this->failedLoginCount;
    }

    /**
     * Set nickname
     *
     * @param string $nickname
     * @return User
     */
    public function setNickname($nickname)
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nickname
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return User
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug($forceDetectType = true)
    {
        if($this->userType == self::USER_TYPE_SELLER && $forceDetectType){
            return $this->getStore()->getStoreSlug();
        }

        return $this->slug;
    }

    /**
     * Set accredationLevel
     *
     * @param integer $accredationLevel
     * @return User
     */
    public function setAccredationLevel($accredationLevel)
    {
        $this->accredationLevel = $accredationLevel;

        return $this;
    }

    /**
     * Get accredationLevel
     *
     * @return integer
     */
    public function getAccredationLevel()
    {
        return $this->accredationLevel;
    }

    /**
     * Set isBanned
     *
     * @param boolean $isBanned
     * @return User
     */
    public function setIsBanned($isBanned)
    {
        $this->isBanned = $isBanned;

        return $this;
    }

    /**
     * Get isBanned
     *
     * @return boolean
     */
    public function getIsBanned()
    {
        return $this->isBanned;
    }

    /**
     * Set banType
     *
     * @param integer $banType
     * @return User
     */
    public function setBanType($banType)
    {
        $this->banType = $banType;

        return $this;
    }

    /**
     * Set userType
     *
     * @param integer $userType
     * @return User
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
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Get banType
     *
     * @return integer
     */
    public function getBanType()
    {
        return $this->banType;
    }

    /**
     * Set lockDuration
     *
     * @param \DateTime $lockDuration
     * @return User
     */
    public function setLockDuration($lockDuration)
    {
        $this->lockDuration = $lockDuration;

        return $this;
    }

    /**
     * Get lockDuration
     *
     * @return \DateTime
     */
    public function getLockDuration()
    {
        return $this->lockDuration;
    }

    /**
     * Set verificationSalt
     *
     * @param string $verificationSalt
     * @return User
     */
    public function setVerificationSalt($verificationSalt)
    {
        $this->verificationSalt = $verificationSalt;

        return $this;
    }

    /**
     * Get verificationSalt
     *
     * @return string
     */
    public function getVerificationSalt()
    {
        return $this->verificationSalt;
    }


    /**
     * Set forgotPasswordToken
     *
     * @param string $forgotPasswordToken
     * @return User
     */
    public function setForgotPasswordToken($forgotPasswordToken)
    {
        $this->forgotPasswordToken = $forgotPasswordToken;

        return $this;
    }

    /**
     * Get forgotPasswordToken
     *
     * @return string
     */
    public function getForgotPasswordToken()
    {
        return $this->forgotPasswordToken;
    }

    /**
     * Get forgotPasswordExpiration
     *
     * @return \DateTime
     */
    public function getForgotPasswordExpiration()
    {
        return $this->forgotPasswordExpiration;
    }

    /**
     * Set forgotPasswordTokenExpiration
     *
     * @param \DateTime $forgotPasswordTokenExpiration
     * @return User
     */
    public function setForgotPasswordTokenExpiration($forgotPasswordTokenExpiration)
    {
        $this->forgotPasswordTokenExpiration = $forgotPasswordTokenExpiration;

        return $this;
    }

    /**
     * Get forgotPasswordTokenExpiration
     *
     * @return \DateTime
     */
    public function getForgotPasswordTokenExpiration()
    {
        return $this->forgotPasswordTokenExpiration;
    }

    /**
     * Set forgotPasswordCode
     *
     * @param string $forgotPasswordCode
     * @return User
     */
    public function setForgotPasswordCode($forgotPasswordCode)
    {
        $this->forgotPasswordCode = $forgotPasswordCode;

        return $this;
    }

    /**
     * Get forgotPasswordCode
     *
     * @return string
     */
    public function getForgotPasswordCode()
    {
        return $this->forgotPasswordCode;
    }

    /**
     * Set forgotPasswordCodeExpiration
     *
     * @param \DateTime $forgotPasswordCodeExpiration
     * @return User
     */
    public function setForgotPasswordCodeExpiration($forgotPasswordCodeExpiration)
    {
        $this->forgotPasswordCodeExpiration = $forgotPasswordCodeExpiration;

        return $this;
    }

    /**
     * Get forgotPasswordCodeExpiration
     *
     * @return \DateTime
     */
    public function getForgotPasswordCodeExpiration()
    {
        return $this->forgotPasswordCodeExpiration;
    }

    /**
     * Add images
     *
     * @param UserImage $images
     * @return User
     */
    public function addImage(UserImage $images)
    {
        $this->images[] = $images;

        return $this;
    }

    /**
     * Remove images
     *
     * @param UserImage $images
     */
    public function removeImage(UserImage $images)
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

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        // TODO: Implement serialize() method.
        return serialize(array(
            $this->userId,
            $this->email,
            $this->password,
            $this->isActive
        ));
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
        list (
            $this->userId,
            $this->email,
            $this->password,
            $this->isActive
            ) = unserialize($serialized);
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        $roles = array('ROLE_USER');
        if($this->userType === self::USER_TYPE_BUYER ){
            $roles = array('ROLE_BUYER');
        }
        else if($this->userType === self::USER_TYPE_SELLER ){
            $store = $this->getStore();
            if($store){

                $roles =  array('ROLE_UNACCREDITED_MERCHANT');
                if($store->getAccreditationLevel()
                    && $store->getStoreType() == Store::STORE_TYPE_MERCHANT){
                    $roles = array('ROLE_MERCHANT');
                }

                if ($store->getStoreType() == Store::STORE_TYPE_RESELLER) {
                    $roles = array('ROLE_RESELLER');
                    if ($store->getAccreditationLevel()) {
                        $roles[] = 'ROLE_RESELLER_ACCREDITED';
                    }

                    if ($this->isAffiliateVerified()) {
                        $roles[] = 'ROLE_VERIFIED';
                    }
                }
            }
        }

        return $roles;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        // TODO: Implement isAccountNonExpired() method.
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        // if null then the username is not locked
        if(!is_null($this->lockDuration)){
            $timeNow = Carbon::now()->getTimestamp();
            $lockTime = $this->lockDuration->getTimestamp();

            // if lock time is greater than the time now then the account is still locked
            if($lockTime > $timeNow){
                return false;
            }
            else{
                return true;
            }
        }

        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        // TODO: Implement isCredentialsNonExpired() method.
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->getIsActive();
    }

    public function getFullName()
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getDefaultAddress()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isDefault", true))
                            ->setFirstResult(0)
                            ->setMaxResults(1);
        $userAddress = $this->getAddresses()->matching($criteria)->first();

        return $userAddress;
    }

    /**
     * Retrieves the best location available
     *
     * @return Yilinker\Bundle\CoreBundle\Entity\UserAddress
     */
    public function getAddressNonNullLocation()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->neq("location", null))
                            ->setFirstResult(0)
                            ->setMaxResults(1)
                            ->orderBy(array('isDefault' => 'DESC'));

        $userAddress = $this->addresses->matching($criteria)->first();

        return $userAddress;
    }

    /**
     * Retrieves the default bank account
     *
     * @return Yilinker\Bundle\CoreBundle\Entity\BankAccount
     */
    public function getDefaultBank()
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isDefault", true))
                            ->andWhere(Criteria::expr()->eq("isDelete", false))
                            ->setFirstResult(0)
                            ->setMaxResults(1);
        $userAddress = $this->getBankAccounts()->matching($criteria)->first();

        return $userAddress;
    }

    /**
     * Set referralCode
     *
     * @param string $referralCode
     * @return User
     */
    public function setReferralCode($referralCode)
    {
        $this->referralCode = $referralCode;

        return $this;
    }

    /**
     * Get referralCode
     *
     * @return string
     */
    public function getReferralCode()
    {
        return $this->referralCode;
    }

    /**
     * Add addresses
     *
     * @param UserAddress $addresses
     * @return User
     */
    public function addAddress(UserAddress $addresses)
    {
        $this->addresses[] = $addresses;
        return $this;
    }

    /**
     * Remove addresses
     *
     * @param UserAddress $addresses
     */
    public function removeAddress(UserAddress $addresses)
    {
        $this->addresses->removeElement($addresses);
    }

    /**
     * Get addresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddresses()
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq('isDelete', false));

        return $this->addresses->matching($criteria);
    }

    public function getAddressesSortedBy($orderBy = array('isDefault' => 'DESC'))
    {
        $criteria = Criteria::create()->orderBy($orderBy);

        return $this->getAddresses()->matching($criteria);
    }

    /**
     * Set store
     *
     * @param Store $store
     * @return User
     */
    public function setStore(Store $store = null)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Add followees
     *
     * @param UserFollow $followees
     * @return User
     */
    public function addFollowee(UserFollow $followees)
    {
        $this->followees[] = $followees;
    }

    /**
     * Add orders
     *
     * @param UserOrder $orders
     * @return User
     */
    public function addOrder(UserOrder $orders)
    {
        $this->orders[] = $orders;

        return $this;
    }

    /**
     * Remove followees
     *
     * @param UserFollow $followees
     */
    public function removeFollowee(UserFollow $followees)
    {
        $this->followees->removeElement($followees);
    }

    /**
     * Get followees
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFollowees()
    {
        return $this->followees;
    }

    /**
     * Add followers
     *
     * @param UserFollow $followers
     * @return User
     */
    public function addFollower(UserFollow $followers)
    {
        $this->followers[] = $followers;

        return $this;
    }

    /**
     * Remove followers
     *
     * @param UserFollow $followers
     */
    public function removeFollower(UserFollow $followers)
    {
        $this->followers->removeElement($followers);
    }

    /**
     * Get followers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFollowers()
    {
        return $this->followers;
    }

    /**
     * Remove orders
     *
     * @param UserOrder $orders
     */
    public function removeOrder(UserOrder $orders)
    {
        $this->orders->removeElement($orders);
    }

    /**
     * Get orders
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Set slugChanged
     *
     * @param boolean $slugChanged
     * @return User
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
     * Get seller storename
     *
     * @return string
     */
    public function getStorename()
    {
        $storeName = null;
        if($this->getUserType() === self::USER_TYPE_SELLER){
            $store = $this->getStore();
            if($store === null || ($store !== null && strlen($store->getStorename()) === 0) ){
                $storeName = $this->getFirstname()."'s Store";
            }
            else{
                $storeName = $store->getStorename();
            }
        }

        return $storeName;
    }

    public function getStoreSlug()
    {
        $storeSlug = '';
        if($this->getUserType() === self::USER_TYPE_SELLER){
            $store = $this->getStore();
            if ($store) {
                $storeSlug = $store->getStoreSlug();
            }
        }

        return $storeSlug;
    }

    /**
     * Check if user is verified
     *
     * @return bool
     */
    public function isVerified()
    {
        return $this->getIsEmailVerified() || $this->getIsMobileVerified();
    }

    /**
     * Add userActivityHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories
     * @return User
     */
    public function addUserActivityHistory(\Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories)
    {
        $this->userActivityHistories[] = $userActivityHistories;

        return $this;
    }

    /**
     * Remove userActivityHistories
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories
     */
    public function removeUserActivityHistory(\Yilinker\Bundle\CoreBundle\Entity\UserActivityHistory $userActivityHistories)
    {
        $this->userActivityHistories->removeElement($userActivityHistories);
    }

    /**
     * Get userActivityHistories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserActivityHistories()
    {
        return $this->userActivityHistories;
    }

    /**
     * Set primaryImage
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserImage $primaryImage
     * @return User
     */
    public function setPrimaryImage(\Yilinker\Bundle\CoreBundle\Entity\UserImage $primaryImage = null)
    {
        $this->primaryImage = $primaryImage;

        return $this;
    }

    /**
     * Get primaryImage
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserImage
     */
    public function getPrimaryImage()
    {
        return $this->primaryImage;
    }


    /**
     * Set primaryCoverPhoto
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserImage $primaryCoverPhoto
     * @return User
     */
    public function setPrimaryCoverPhoto(\Yilinker\Bundle\CoreBundle\Entity\UserImage $primaryCoverPhoto = null)
    {
        $this->primaryCoverPhoto = $primaryCoverPhoto;

        return $this;
    }

    /**
     * Get primaryCoverPhoto
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserImage
     */
    public function getPrimaryCoverPhoto()
    {
        return $this->primaryCoverPhoto;
    }

    /**
     * Add productUploads
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $productUploads
     * @return User
     */
    public function addProductUpload(\Yilinker\Bundle\CoreBundle\Entity\Product $productUploads)
    {
        $this->productUploads[] = $productUploads;

        return $this;
    }

    /**
     * Remove productUploads
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $productUploads
     */
    public function removeProductUpload(\Yilinker\Bundle\CoreBundle\Entity\Product $productUploads)
    {
        $this->productUploads->removeElement($productUploads);
    }

    /**
     * Get productUploads
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductUploads()
    {
        return $this->productUploads;
    }

    /**
     * Get popular uploads (by clickcount)
     *
     * @param int $limit
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMostPopularUploads($limit = 5)
    {
        $popularUploads = array();
        if (count($this->productUploads) > 0) {
            $criteria = Criteria::create()
                                ->andWhere(Criteria::expr()->eq("status", Product:: ACTIVE))
                                ->orderBy(array('clickCount' => Criteria::DESC))
                                ->setMaxResults($limit);

            $popularUploads = $this->productUploads->matching($criteria);
        }

        return $popularUploads;
    }

    /**
     * Add devices
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Device $devices
     * @return User
     */
    public function addDevice(\Yilinker\Bundle\CoreBundle\Entity\Device $devices)
    {
        $this->devices[] = $devices;

        return $this;
    }

    /**
     * Remove devices
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Device $devices
     */
    public function removeDevice(\Yilinker\Bundle\CoreBundle\Entity\Device $devices)
    {
        $this->devices->removeElement($devices);
    }

    /**
     * Get devices
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    public function getActiveDevices()
    {
        $isDeleteCriteria = Criteria::expr()->eq("isDelete", false);
        $tokenTypes = Criteria::expr()->in("tokenType", array(
                        Device::TOKEN_TYPE_REGISTRATION_ID,
                        Device::TOKEN_TYPE_JWT
                    ));

        $criteria = Criteria::create()
                            ->andWhere($tokenTypes)
                            ->andWhere($isDeleteCriteria);

        $activeDevices = $this->getDevices()->matching($criteria);

        return $activeDevices;
    }


    public function getActiveNonIdleDevices()
    {
        $isIdleCriteria = Criteria::expr()->eq("isIdle", false);
        $isDeleteCriteria = Criteria::expr()->eq("isDelete", false);
        $tokenTypes = Criteria::expr()->in("tokenType", array(
                        Device::TOKEN_TYPE_REGISTRATION_ID,
                        Device::TOKEN_TYPE_JWT
                    ));

        $criteria = Criteria::create()
                            ->andWhere($isIdleCriteria)
                            ->andWhere($tokenTypes)
                            ->andWhere($isDeleteCriteria);

        $activeDevices = $this->getDevices()->matching($criteria);

        return $activeDevices;
    }
    public function getIdleDevices()
    {
        $isIdleCriteria = Criteria::expr()->eq("isIdle", true);
        $isDeleteCriteria = Criteria::expr()->eq("isDelete", false);
        $tokenTypes = Criteria::expr()->in("tokenType", array(
                        Device::TOKEN_TYPE_REGISTRATION_ID,
                        Device::TOKEN_TYPE_JWT
                    ));

        $criteria = Criteria::create()
                            ->andWhere($tokenTypes)
                            ->andWhere($isIdleCriteria)
                            ->andWhere($isDeleteCriteria);

        $idleDevices = $this->getDevices()->matching($criteria);

        return $idleDevices;
    }

    public function getIsOnline()
    {
        $devices = $this->getActiveDevices()->count();
        $idleDevices = $this->getIdleDevices()->count();

        if($devices != $idleDevices){
            return true;
        }

        return false;
    }

    /**
     * Add recievedMessages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $recievedMessages
     * @return User
     */
    public function addRecievedMessage(\Yilinker\Bundle\CoreBundle\Entity\Message $recievedMessages)
    {
        $this->recievedMessages[] = $recievedMessages;

        return $this;
    }

    /**
     * Remove recievedMessages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $recievedMessages
     */
    public function removeRecievedMessage(\Yilinker\Bundle\CoreBundle\Entity\Message $recievedMessages)
    {
        $this->recievedMessages->removeElement($recievedMessages);
    }

    /**
     * Get recievedMessages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecievedMessages()
    {
        return $this->recievedMessages;
    }

    /**
     * Add sentMessages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $sentMessages
     * @return User
     */
    public function addSentMessage(\Yilinker\Bundle\CoreBundle\Entity\Message $sentMessages)
    {
        $this->sentMessages[] = $sentMessages;

        return $this;
    }

    /**
     * Remove sentMessages
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Message $sentMessages
     */
    public function removeSentMessage(\Yilinker\Bundle\CoreBundle\Entity\Message $sentMessages)
    {
        $this->sentMessages->removeElement($sentMessages);
    }

    /**
     * Get sentMessages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSentMessages()
    {
        return $this->sentMessages;
    }

    /**
     * Get sentMessages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUnreadRecievedMessagesFromUser(User $user)
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("recipient", $user))
                            ->andWhere(Criteria::expr()->eq("isSeen", false));

        $unreadMessages = $this->getSentMessages()->matching($criteria);

        return $unreadMessages;
    }

    public function getLastMessage(User $user)
    {
        $sentCriteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("recipient", $user))
                            ->orderBy(array("timeSent" => "DESC"));

        $sentMessage = $this->getSentMessages()->matching($sentCriteria)->first();

        $recievedCriteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("sender", $user))
                            ->orderBy(array("timeSent" => "DESC"));

        $recievedMessage = $this->getRecievedMessages()->matching($recievedCriteria)->first();

        if($sentMessage && $recievedMessage){
            $sentMessageTimestamp = $sentMessage->getTimeSent()->getTimestamp();
            $recievedMessageTimestamp = $recievedMessage->getTimeSent()->getTimestamp();

            if($sentMessageTimestamp > $recievedMessageTimestamp){
                return $sentMessage;
            }
            else{
                return $recievedMessage;
            }
        }
        elseif(!$sentMessage){
            return $recievedMessage;
        }
        elseif(!$recievedMessage){
            return $sentMessage;
        }
        else{
            return null;
        }
    }

    /**
     * Convert the object into an array
     */
    public function getUserCard()
    {
        return array(
            'fullname' => $this->getFullname(),
            'address'  => $this->getDefaultAddress() ? $this->getDefaultAddress()->getShortAddressString() : '',
            'image'    => $this->getPrimaryImage() ? $this->getPrimaryImage()->getFullImagePath() : '',
        );
    }

    /**
     * Add notifications
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserNotification $notifications
     * @return User
     */
    public function addNotification(\Yilinker\Bundle\CoreBundle\Entity\UserNotification $notifications)
    {
        $this->notifications[] = $notifications;

        return $this;
    }

    /**
     * Remove notifications
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserNotification $notifications
     */
    public function removeNotification(\Yilinker\Bundle\CoreBundle\Entity\UserNotification $notifications)
    {
        $this->notifications->removeElement($notifications);
    }

    /**
     * Get notifications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    public function getRecentNotifications($page = null, $perPage = null)
    {
        $criteria = Criteria::create()->orderBy(array(
            'dateAdded' => 'DESC'
        ));
        if (!is_null($page)) {
            $offset = $page > 0 ? $page - 1 : 0;
            $criteria->setFirstResult($offset);
        }
        if (!is_null($perPage)) {
            $criteria->setMaxResults($perPage);
        }

        return $this->getNotifications()->matching($criteria);
    }

    /**
     * initially used to identify users on socket
     *
     * @return hashed value
     */
    public function hashkey()
    {
        $string = $this->getId().'user'.date('mdY');

        return hash('sha256', $string);
    }

    /**
     * Set reactivationCode
     *
     * @param string $reactivationCode
     * @return User
     */
    public function setReactivationCode($reactivationCode)
    {
        $this->reactivationCode = $reactivationCode;

        return $this;
    }

    /**
     * Get reactivationCode
     *
     * @return string
     */
    public function getReactivationCode()
    {
        return $this->reactivationCode;
    }

    /**
     * Set accountId
     *
     * @param integer $accountId
     * @return User
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    public function isSeller()
    {
        return self::USER_TYPE_SELLER == $this->getUserType();
    }

    public function isAffiliate($includeInhouse = true)
    {
        $store = $this->getStore();
        if ($store) {
            $isAffiliate = ($store->getStoreType() == Store::STORE_TYPE_RESELLER);
            
            return $isAffiliate && ($includeInhouse || !$store->getIsInhouse());
        }

        return false;
    }

    /**
     * Add userVerificationTokens
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken $userVerificationTokens
     * @return User
     */
    public function addUserVerificationToken(\Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken $userVerificationTokens)
    {
        $this->userVerificationTokens[] = $userVerificationTokens;

        return $this;
    }

    /**
     * Remove userVerificationTokens
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken $userVerificationTokens
     */
    public function removeUserVerificationToken(\Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken $userVerificationTokens)
    {
        $this->userVerificationTokens->removeElement($userVerificationTokens);
    }

    /**
     * Get userVerificationTokens
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserVerificationTokens()
    {
        return $this->userVerificationTokens;
    }

    /**
     * Get active userVerificationTokens
     *
     * @param int $type
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getActiveUserVerificationTokens($type = null)
    {
        $criteria = Criteria::create()
                            ->andWhere(Criteria::expr()->eq("isActive", true))
                            ->orderBy(array("dateAdded" => Criteria::DESC));

        if($type !== null){
            $criteria->andWhere(Criteria::expr()->eq("tokenType", $type));
        }

        return $this->getUserVerificationTokens()->matching($criteria);
    }


    /**
     * Get verificationToken
     *
     * @return string
     */
    public function getVerificationToken($type = null)
    {
        $tokens = $this->getActiveUserVerificationTokens($type);

        return $tokens ? $tokens->first()->getToken() : null;
    }

    /**
     * Set tin
     *
     * @param string $tin
     * @return User
     */
    public function setTin($tin)
    {
        $this->tin = $tin;

        return $this;
    }

    /**
     * Get tin
     *
     * @return string
     */
    public function getTin()
    {
        return $this->tin;
    }

    /**
     * Set lastLogoutDate
     *
     * @param \DateTime $lastLogoutDate
     * @return User
     */
    public function setLastLogoutDate($lastLogoutDate)
    {
        $this->lastLogoutDate = $lastLogoutDate;

        return $this;
    }

    /**
     * Get lastLogoutDate
     *
     * @return \DateTime
     */
    public function getLastLogoutDate()
    {
        return $this->lastLogoutDate;
    }

    /**
     * Add bankAccounts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\BankAccount $bankAccounts
     * @return User
     */
    public function addBankAccount(\Yilinker\Bundle\CoreBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts[] = $bankAccounts;

        return $this;
    }

    /**
     * Remove bankAccounts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\BankAccount $bankAccounts
     */
    public function removeBankAccount(\Yilinker\Bundle\CoreBundle\Entity\BankAccount $bankAccounts)
    {
        $this->bankAccounts->removeElement($bankAccounts);
    }

    /**
     * Get bankAccounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankAccounts()
    {
        return $this->bankAccounts;
    }

    /**
     * Set isSocialMedia
     *
     * @param boolean $isSocialMedia
     * @return User
     */
    public function setIsSocialMedia($isSocialMedia)
    {
        $this->isSocialMedia = $isSocialMedia;

        return $this;
    }

    /**
     * Get isSocialMedia
     *
     * @return boolean
     */
    public function getIsSocialMedia()
    {
        return $this->isSocialMedia;
    }

    /**
     * Add socialMediaAccounts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserMerge $socialMediaAccounts
     * @return User
     */
    public function addSocialMediaAccount(\Yilinker\Bundle\CoreBundle\Entity\UserMerge $socialMediaAccounts)
    {
        $this->socialMediaAccounts[] = $socialMediaAccounts;

        return $this;
    }

    /**
     * Remove socialMediaAccounts
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserMerge $socialMediaAccounts
     */
    public function removeSocialMediaAccount(\Yilinker\Bundle\CoreBundle\Entity\UserMerge $socialMediaAccounts)
    {
        $this->socialMediaAccounts->removeElement($socialMediaAccounts);
    }

    /**
     * Get socialMediaAccounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSocialMediaAccounts()
    {
        return $this->socialMediaAccounts;
    }

    /**
     * Set registrationType
     *
     * @param integer $registrationType
     * @return User
     */
    public function setRegistrationType($registrationType)
    {
        $this->registrationType = $registrationType;

        return $this;
    }

    /**
     * Get registrationType
     *
     * @return integer
     */
    public function getRegistrationType()
    {
        return $this->registrationType;
    }

    /**
     * Add products
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $products
     * @return User
     */
    public function addProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $products)
    {
        $this->products[] = $products;

        return $this;
    }

    /**
     * Remove products
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Product $products
     */
    public function removeProduct(\Yilinker\Bundle\CoreBundle\Entity\Product $products)
    {
        $this->products->removeElement($products);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    public function getProductsByCategory($productCategory)
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq("productCategory", $productCategory));

        return $this->getProducts()->matching($criteria);
    }

    public function getProductsByCategoryAndStatus($productCategory, $status)
    {
        $criteria = Criteria::create()->andWhere(Criteria::expr()->eq("productCategory", $productCategory))
                                      ->andWhere(Criteria::expr()->eq("status", $status));

        return $this->getProducts()->matching($criteria);
    }

    /**
     * Add earnings
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earnings
     * @return User
     */
    public function addEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earnings)
    {
        $this->earnings[] = $earnings;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $oneTimePasswords;

    /**
     * Add oneTimePasswords
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OneTimePassword $oneTimePasswords
     * @return User
     */
    public function addOneTimePassword(\Yilinker\Bundle\CoreBundle\Entity\OneTimePassword $oneTimePasswords)
    {
        $this->oneTimePasswords[] = $oneTimePasswords;

        return $this;
    }

    /**
     * Remove earnings
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Earning $earnings
     */
    public function removeEarning(\Yilinker\Bundle\CoreBundle\Entity\Earning $earnings)
    {
        $this->earnings->removeElement($earnings);
    }

    /**
     * Get earnings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEarnings()
    {
        return $this->earnings;
    }

    /**
     * Remove oneTimePasswords
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OneTimePassword $oneTimePasswords
     */
    public function removeOneTimePassword(\Yilinker\Bundle\CoreBundle\Entity\OneTimePassword $oneTimePasswords)
    {
        $this->oneTimePasswords->removeElement($oneTimePasswords);
    }

    /**
     * Get oneTimePasswords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOneTimePasswords()
    {
        return $this->oneTimePasswords;
    }

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\UserBanType
     */
    private $country;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Language
     */
    private $language;

    /**
     * Set country
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country $country
     * @return Country
     */
    public function setCountry(\Yilinker\Bundle\CoreBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    public function getCountry()
    {
        if ($this->country && $this->country->getCountryId()) {
            return $this->country;
        }

        return null;
    }

    /**
     * Set userReferral
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserReferral $userReferral
     * @return User
     */
    public function setUserReferral(\Yilinker\Bundle\CoreBundle\Entity\UserReferral $userReferral = null)
    {
        $this->userReferral = $userReferral;

        return $this;
    }

    /**
     * Get userReferral
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\UserReferral
     */
    public function getUserReferral()
    {
        return $this->userReferral;
    }

    /**
     * Add referrers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserReferral $referrers
     * @return User
     */
    public function addReferrer(\Yilinker\Bundle\CoreBundle\Entity\UserReferral $referrers)
    {
        $this->referrers[] = $referrers;

        return $this;
    }

    /**
     * Remove referrers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserReferral $referrers
     */
    public function removeReferrer(\Yilinker\Bundle\CoreBundle\Entity\UserReferral $referrers)
    {
        $this->referrers->removeElement($referrers);
    }

    /**
     * Get referrers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReferrers()
    {
        return $this->referrers;
    }

    public function createAutoGeneratedPassword()
    {
        $password = '';
        $letters = array_merge(range('A', 'Z'), range('a', 'z'));
        $rand = array_rand($letters, 4);

        foreach ($rand as $key) {
            $password .= $letters[$key];
        }

        $numbers = implode('', array_rand(range(0, 9), 4));
        $password .= $numbers;
        $this->setPlainPassword($password);

        return $this->getPlainPassword();
    }

    /**
     * Set consecutiveLoginCount
     *
     * @param integer $consecutiveLoginCount
     * @return User
     */
    public function setConsecutiveLoginCount($consecutiveLoginCount)
    {
        $this->consecutiveLoginCount = $consecutiveLoginCount;

        return $this;
    }

    /**
     * Get consecutiveLoginCount
     *
     * @return integer 
     */
    public function getConsecutiveLoginCount()
    {
        return $this->consecutiveLoginCount;
    }

    public function canEarnPoints()
    {
        return in_array(
            $this->getUserType(),
            array(
                self::USER_TYPE_BUYER,
                self::USER_TYPE_GUEST
            )
        );
    }

    /**
     * Check if user "affiliate" is verified
     * verified means it has store name, first and last name
     * email and store slug
     * @return boolean
     */
    public function isAffiliateVerified()
    {
        $isVerified = false;
        $store = $this->getStore();
        if ($store && $store->getStoreType() == Store::STORE_TYPE_RESELLER) {
            $isVerified = 
                $this->getFirstName() &&
                $this->getLastName() &&
                $store->getStoreName() &&
                $store->getStoreSlug()
            ;
        }

        return $isVerified;
    }

    /**
     * Set accreditationApplication
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication $accreditationApplication
     * @return User
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $warehouses;

    /**
     * Add warehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $warehouses
     * @return User
     */
    public function addWarehouse(\Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $warehouses)
    {
        $this->warehouses[] = $warehouses;

        return $this;
    }

    /**
     * Remove warehouses
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $warehouses
     */
    public function removeWarehouse(\Yilinker\Bundle\CoreBundle\Entity\UserWarehouse $warehouses)
    {
        $this->warehouses->removeElement($warehouses);
    }

    /**
     * Get warehouses
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getWarehouses($includeDeleted = false)
    {
        $criteria = Criteria::create();

        if (!$includeDeleted) {
            $criteria->where(Criteria::expr()->eq('isDelete', false));
        }

        $criteria->orderBy(array('userWarehouseId' => Criteria::DESC));

        $warehouses = $this->warehouses->matching($criteria);

        return $warehouses;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $productGroups;


    /**
     * Add productGroups
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserProductGroup $productGroups
     * @return User
     */
    public function addProductGroup(\Yilinker\Bundle\CoreBundle\Entity\UserProductGroup $productGroups)
    {
        $this->productGroups[] = $productGroups;

        return $this;
    }

    /**
     * Remove productGroups
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\UserProductGroup $productGroups
     */
    public function removeProductGroup(\Yilinker\Bundle\CoreBundle\Entity\UserProductGroup $productGroups)
    {
        $this->productGroups->removeElement($productGroups);
    }

    /**
     * Get productGroups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProductGroups()
    {
        return $this->productGroups;
    }

    /**
     * Set language
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Language $language
     * @return User
     */
    public function setLanguage(\Yilinker\Bundle\CoreBundle\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Language 
     */
    public function getLanguage()
    {
        return $this->language;
    }
    
    /**
     * @var integer
     */
    private $resourceId = '0';
    
    
    /**
     * Set resourceId
     *
     * @param integer $resourceId
     * @return User
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

    /**
     * Add inhouseProductUsers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers
     * @return User
     */
    public function addInhouseProductUser(\Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers)
    {
        $this->inhouseProductUsers[] = $inhouseProductUsers;

        return $this;
    }

    /**
     * Remove inhouseProductUsers
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers
     */
    public function removeInhouseProductUser(\Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser $inhouseProductUsers)
    {
        $this->inhouseProductUsers->removeElement($inhouseProductUsers);
    }

    /**
     * Get inhouseProductUsers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInhouseProductUsers()
    {
        return $this->inhouseProductUsers;
    }

    public function getStoreSpace()
    {
        if (!$this->getStore()) {
            return 0;
        }
        
        $storeSpace = $this
            ->getStore()
            ->getStoreLevel()
            ->getStoreSpace()
        ;

        return $storeSpace;
    }

    public function getAvailableStoreSpace()
    {
        $storeSpace = $this->getStoreSpace();
        $selected = $this->getInhouseProductUsers()->count();

        return $storeSpace - $selected;
    }
}
