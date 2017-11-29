<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

class LanguageCountry
{
    
    /**
     * @var integer
     */
    private $languageCountryId;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Language
     */
    private $language;

    /**
     * @var \Yilinker\Bundle\CoreBundle\Entity\Country
     */
    private $country;

    /**
     * @var boolean
     */
    private $isPrimary = '0';

    /**
     * Get languageCountryId
     *
     * @return integer 
     */
    public function getLanguageCountryId()
    {
        return $this->languageCountryId;
    }

    /**
     * Set language
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Language $language
     * @return LanguageCountry
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
     * Set country
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\Country $country
     * @return LanguageCountry
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
        return $this->country;
    }

    /**
     * Set isPrimary
     *
     * @param boolean $isPrimary
     * @return LanguageCountry
     */
    public function setIsPrimary($isPrimary)
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    /**
     * Get isPrimary
     *
     * @return boolean 
     */
    public function getIsPrimary()
    {
        return $this->isPrimary;
    }
}
