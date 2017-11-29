<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * Country
 */
class Country
{
    const COUNTRY_CODE_PHILIPPINES = "PH";

    const COUNTRY_CODE_CHINA = "CN";

    const AREA_CODE_PHILIPPINES = "63";

    const AREA_CODE_CHINA = "86";

    const ACTIVE_DOMAIN = 1;
    
    /**
     * @var integer
     */
    private $countryId;

    /**
     * @var string
     */
    private $referenceNumber;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $code;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateLastModified;

    /**
     * @var string
     */
    private $domain = '';

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Currency
     */
    private $currency;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $languageCountries;

    /**
     * @var integer
     */
    private $status = '0';

    /**
     * Get countryId
     *
     * @return integer
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

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
     * Set code
     *
     * @param string $code
     * @return Country
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode($lowercase = false)
    {
        return $lowercase ? strtolower($this->code): $this->code;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Country
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
     * @return Country
     */
    public function setDateLastModified($dateLastModified)
    {
        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    public function toArray()
    {
        $data = array(
            'countryId' => $this->getCountryId(),
            'name'      => $this->getName(),
            'code'      => $this->getCode(),
            'domain'    => $this->getDomain(),
            'area_code' => $this->getAreaCode(),
            'isActive'    => $this->getStatus() ? true : false,
            'defaultLanguage'  => $this->getLanguage() ? $this->getLanguage()->toArray() : null,
        );

        return $data;
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
     * Set referenceNumber
     *
     * @param string $referenceNumber
     * @return Country
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    /**
     * Get referenceNumber
     *
     * @return string
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }
    /**
     * @var string
     */
    private $areaCode;


    /**
     * Set areaCode
     *
     * @param string $areaCode
     * @return Country
     */
    public function setAreaCode($areaCode)
    {
        $this->areaCode = $areaCode;

        return $this;
    }

    /**
     * Get areaCode
     *
     * @return string
     */
    public function getAreaCode()
    {
        return $this->areaCode;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $oneTimePasswords;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->oneTimePasswords = new \Doctrine\Common\Collections\ArrayCollection();
        $this->languageCountries = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add users
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $users
     * @return Country
     */
    public function addUser(\Yilinker\Bundle\CoreBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\User $users
     */
    public function removeUser(\Yilinker\Bundle\CoreBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add oneTimePasswords
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\OneTimePassword $oneTimePasswords
     * @return Country
     */
    public function addOneTimePassword(\Yilinker\Bundle\CoreBundle\Entity\OneTimePassword $oneTimePasswords)
    {
        $this->oneTimePasswords[] = $oneTimePasswords;

        return $this;
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
     * Add languageCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LanguageCountry $languageCountries
     * @return Country
     */
    public function addLanguageCountry(\Yilinker\Bundle\CoreBundle\Entity\LanguageCountry $languageCountries)
    {
        $this->languageCountries[] = $languageCountries;

        return $this;
    }

    /**
     * Remove languageCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LanguageCountry $languageCountries
     */
    public function removeLanguageCountry(\Yilinker\Bundle\CoreBundle\Entity\LanguageCountry $languageCountries)
    {
        $this->languageCountries->removeElement($languageCountries);
    }

    /**
     * Get languageCountries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLanguageCountries()
    {
        return $this->languageCountries;
    }

    public function getLanguageCountryIn($languages = array())
    {
        $inExpr = Criteria::expr()->in("language", $languages);
        $criteria = Criteria::create()
                            ->andWhere($inExpr);

        return $this->getLanguageCountries()->matching($criteria);
    }
    
    /**
     * Set domain
     *
     * @param string $domain
     * @return Country
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string 
     */
    public function getDomain()
    {
        return $this->domain ? $this->domain: 'www.yilinker.com';
    }

    /**
     * Set currency
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Currency $currency
     * @return Country
     */
    public function setCurrency(\Yilinker\Bundle\CoreBundle\Entity\Currency $currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return \Yilinker\Bundle\CoreBundle\Entity\Currency 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Retrieve the primary language
     *
     * @retunr \Yilinker\Bundle\CoreBundle\Entity\Language
     */
    public function getLanguage()
    {
        $criteria = Criteria::create()
                            ->orderBy(array("isPrimary" => Criteria::DESC))
                            ->setMaxResults(1);

        
        $primaryLanguage = $this->getLanguageCountries()->matching($criteria)->first();

        return $primaryLanguage ? $primaryLanguage->getLanguage(): null;
    }

    public function __toString()
    {
        return $this->getName();
    }
    /**
     * @var string
     */
    private $latitude = '';

    /**
     * @var string
     */
    private $longitude = '';


    /**
     * Set latitude
     *
     * @param string $latitude
     * @return Country
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string 
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return Country
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string 
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Country
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }
}
