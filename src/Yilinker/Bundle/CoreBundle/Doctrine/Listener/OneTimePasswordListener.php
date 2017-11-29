<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class OneTimePasswordListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof OneTimePassword) {
            $this->formatContactNumber($entity);
        }
    }

    private function formatContactNumber($oneTimePassword)
    {
        $country = $oneTimePassword->getCountry();

        switch($country->getCode()){
            case Country::COUNTRY_CODE_PHILIPPINES:
                if(strlen($oneTimePassword->getContactNumber()) == 10){
                    $oneTimePassword->setContactNumber("0".$oneTimePassword->getContactNumber());
                }
                break;
        }
    }
}
