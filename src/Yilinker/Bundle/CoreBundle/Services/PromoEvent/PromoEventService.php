<?php

namespace Yilinker\Bundle\CoreBundle\Services\PromoEvent;

use Exception;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\PromoEvent;
use Yilinker\Bundle\CoreBundle\Entity\PromoEventUser;

class PromoEventService
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function subscribePromoEvent(PromoEvent $promoEvent, User $user)
    {   
        if(!$promoEvent->isUserSubscribed($user)){
            $promoEventUser = new PromoEventUser();
            $promoEventUser->setPromoEvent($promoEvent)
                           ->setUser($user);

            $this->em->persist($promoEventUser);
            $this->em->flush();
        }
    }
}
