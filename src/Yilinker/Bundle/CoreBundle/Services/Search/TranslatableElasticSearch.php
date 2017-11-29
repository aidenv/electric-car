<?php

namespace Yilinker\Bundle\CoreBundle\Services\Search;

use Doctrine\ORM\EntityManager;

class TranslatableElasticSearch
{
    /**
     * The default locale for the doctrine data source
     *
     * @var string
     */
    private $defaultLocale  = 'en';
    
    /**
     * Allowed languages for translatable fields
     *
     * @var mixed
     */
    private $translatableLanguages = array(
        'CN','EN-PH', 'TH'
    );

    /**
     * Language to country mapping for entities that are translated by the country
     *
     * @var mixed
     */
    private $languageCountryMapping = array(
        'EN-PH' => 'ph',
        'CN'    => 'cn',
        'TH'    => 'th'
    );

    /**
     * DoctrineEntity Manager
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $em;
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Get translatable language
     *
     * @return string[]
     */
    public function getTranslatableLanguages()
    {
        return $this->translatableLanguages;
    }

    /**
     * Set the translatable lanuages
     *
     * @param string[] $translatableLanguages
     */
    public function setTranslatableLaguages(array $translatableLanguages)
    {
        $this->translatableLanguages = $translatableLanguages;

        return $this;
    }

    /**
     * Get the language country mappings
     * 
     * @param boolean $isEntity
     * @return string[]
     */
    public function getLanguageCountryMapping($isEntity = false)
    {
        if($isEntity){
            $tblCountry = $this->em->getRepository('YilinkerCoreBundle:Country');
            $entityMap = array();
            foreach($this->languageCountryMapping as $key => $countryCode){
                $entityMap[$key] = $tblCountry->findOneBy(array(
                    "code" => $countryCode,
                ));
            }

            $response = $entityMap;
        }
        else{
            $response = $this->languageCountryMapping;
        }
        
        return $response;
    }

    /**
     * Retrieve the default language
     *
     * @return string
     */
    public function getDefaultLanguage()
    {
        return $this->defaultLocale;
    }

    /**
     * Retrieve the elastic field prefix based on the locale
     *
     * @param string $countryCode
     * @return string
     */
    public function getElasticFieldPrefix($countryCode = null)
    {
        $prefix = "EN-PH";

        if($countryCode){
            $prefix = array_search($countryCode, $this->languageCountryMapping);
        }
        
        return strlen($prefix) > 0 ? $prefix."_" : $prefix;
    }
           
}