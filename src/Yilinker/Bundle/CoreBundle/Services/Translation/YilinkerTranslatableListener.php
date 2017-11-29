<?php

namespace Yilinker\Bundle\CoreBundle\Services\Translation;

use Gedmo\Translatable\TranslatableListener;

class YilinkerTranslatableListener extends TranslatableListener
{
    protected $locale = 'en';
    private $defaultLocale = 'en';
    private $container;
    private $country = 'ph';
    private $countryTrans = array(
        'Yilinker\Bundle\CoreBundle\Entity\ProductUnit',
        'Yilinker\Bundle\CoreBundle\Entity\ProductWarehouse',
        'Yilinker\Bundle\CoreBundle\Entity\ProductRemarks',
    );

    public function setContainer($container)
    {
        $this->container = $container;
        $this->setDefaultLocale($this->container->getParameter('default_locale'));
    }

    public function setCountry($country)
    {
        if (!is_null($country)) {
            $this->country = $country;
        }
    }

    public function getCountry($entity = false)
    {
        if ($entity) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
            $country = $tbCountry->findOneByCode($this->country);
            if ($country) {
                return $country;
            }
        }

        return $this->country;
    }

    public function isCountryTranslatable($object)
    {
        return in_array(get_class($object), $this->countryTrans);
    }

    public function getTranslatableLocale($object, $meta, $om = null)
    {
        $countrTrans = $this->isCountryTranslatable($object);
        $tempLocale = $this->locale;
        if ($countrTrans) {
            $this->locale = $this->country;
        }
        $locale = parent::getTranslatableLocale($object, $meta, $om);
        $this->locale = $tempLocale;
        
        return $locale;
    }
}