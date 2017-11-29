<?php

namespace Yilinker\Bundle\CoreBundle\Services\Translation;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LocaleListener
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->countryDomain($event);
        $request = $event->getRequest();

        /**
         * Set the request object _locale for the twig translator
         *
         * _locale: session persistent twig translations
         * languageCode: per request twig translations
         */
        $locale = $request->get('_locale', null);
        if ($locale) {
            $request->getSession()->set('_locale', $locale);
        }
        
        $locale = $request->getSession()->get('_locale', null);
        $languageCode = $request->get('languageCode');

        if ($languageCode) {
            $request->setLocale($languageCode);
        }
        elseif ($locale) {
            $request->setLocale($locale);
        }

        /**
         * Set the gedmo translation locale
         *
         * _country: session persistent entity country locale
         * countryCode: per request entity country locale
         *
         * Language is based on primary langauge in country. 
         * If the country is not set, the entity will be translated by the twig locale by default.
         */
        $sessionCountry = $request->get('_country', null);
        $countryCode = $request->get('countryCode');
        $countryLocale = null;
        if($sessionCountry){
            $request->getSession()->set('_country', $sessionCountry);
            $countryLocale = $request->getSession()->get('_country', null);
        }
        else if($countryCode){
            $countryLocale = $countryCode;
        }
        else{
            $countryLocale = $request->getSession()->get('_country', null);
        }
        $countryLocale = strtolower($countryLocale);
        
        $entityLanguageLocale = $request->getLocale();
        if($countryLocale){
            $primaryLanguage = $request->getSession()->get($countryLocale.'_default_lang');           
            if($primaryLanguage === null){
                $em = $this->container->get('doctrine')->getManager();            
                $countryEntity = $em->getRepository('YilinkerCoreBundle:Country')
                                    ->findOneBy(array('code' => $countryLocale));
                if($countryEntity && $countryEntity->getLanguage()){                    
                    $request->getSession()->set(
                        $countryLocale.'_default_lang', $countryEntity->getLanguage()->getCode()
                    );
                    $primaryLanguage = $countryEntity->getLanguage()->getCode();
                }
            }
            $entityLanguageLocale = $primaryLanguage;
        }
        
        $trans = $this->container->get('yilinker_core.translatable.listener');
        if ($entityLanguageLocale) {
            $trans->setTranslatableLocale($entityLanguageLocale);
        }
        if($countryLocale){
            $trans->setCountry($countryLocale);
        }
    }

    private function countryDomain($event)
    {
        $request = $event->getRequest();
        $host = $request->getHost();
        
        $em = $this->container->get('doctrine.orm.entity_manager');
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        
        $apiCountry = $this->countryByApi($event);
        $country = $apiCountry ? $apiCountry : $tbCountry->findOneByDomain($host);

        if ($country) {
            $trans = $this->container->get('yilinker_core.translatable.listener');
            $trans->setCountry(strtolower($country->getCode()));

            $languageCountry = $country->getLanguageCountries()->first();
            if ($languageCountry) {
                $language = $languageCountry->getLanguage();
                $trans->setTranslatableLocale($language->getCode());
                $request->setLocale($language->getCode());
            }
        }
    }

    // api url - /api/v3/{countrycode}/{languagecode}
    private function countryByApi($event)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');

        $country = null;
        $request = $event->getRequest();
        $pathInfo = explode('/',$request->getPathInfo());

        if (isset($pathInfo[3]) && $pathInfo[2] == 'v3' &&  $tbCountry->findByCode($pathInfo[3])) {
            $country = $tbCountry->findByCode($pathInfo[3])[0];
            
            $request->request->set('languageCode',$pathInfo[4]);
        }

        return $country;
    }
}