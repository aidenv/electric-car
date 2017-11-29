<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

class CountryCodeListener
{
    private $container;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $traits = class_uses($entity);
        if ($traits && array_key_exists(
            'Yilinker\Bundle\CoreBundle\Entity\Traits\CountryCodeTrait', $traits)
        ) {
            // set country code for product
            $trans = $this->container->get('yilinker_core.translatable.listener');
            $countryCode = $trans->getCountry();
            $entity->setCountryCode($countryCode);
            
            // set country for product
            $em = $args->getEntityManager();
            $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
            $country = $tbCountry->findOneByCode($countryCode);
            $entity->setCountry($country);
        }
    }
}