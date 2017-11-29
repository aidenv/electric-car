<?php

namespace Yilinker\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Criteria;

/**
 * Language
 */
class Language
{

    const ENGLISH = 1;

    const CHINESE = 2;

    /**
     * @var integer
     */
    private $languageId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $code;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $languageCountries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->languageCountries = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get languageId
     *
     * @return integer 
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Language
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
     * @return Language
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @param $toDisplayFlag
     * @return string 
     */
    public function getCode($toDisplayFlag = false)
    {
        $code = $this->code;
        if ($toDisplayFlag && $code == 'en') {
            $code = 'us';
        }

        return $code;
    }

    /**
     * Add languageCountries
     *
     * @param \Yilinker\Bundle\CoreBundle\Entity\LanguageCountry $languageCountries
     * @return Language
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

    public function getPrimaryLanguageCountry()
    {
        $eq = Criteria::expr()->eq("isPrimary", true);
        $criteria = Criteria::create()
                            ->andWhere($eq);

        $languageCountries = $this->getLanguageCountries()->matching($criteria);

        return $languageCountries->first();
    }

    public function getCountryCode()
    {
        if (strtolower($this->code) == 'en') {
            return 'us';
        }

        return $this->getCode();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Convert to array
     *
     * @return mixed
     */
    public function toArray()
    {
        return array(
            'id'    => $this->languageId,
            'name'  => $this->name,
            'code'  => $this->code,
        );
    }
    
}
