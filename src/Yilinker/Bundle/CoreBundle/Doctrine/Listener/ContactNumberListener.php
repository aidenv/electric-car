<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\ContactNumber;
use Yilinker\Bundle\CoreBundle\Entity\User;

class ContactNumberListener
{
    /**
     * @param  LifecycleEventArgs $event
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof User) {
            if ($args->hasChangedField('contactNumber')) {
                $existingContactNumber = $entityManager->getRepository('YilinkerCoreBundle:ContactNumber')
                                                       ->findOneBy(array(
                                                           'contactNumber' => $entity->getContactNumber()
                                                       ));
                if($existingContactNumber === null){
                    $newContactNumber = new ContactNumber();
                    $newContactNumber->setUser($entity);
                    $newContactNumber->setContactNumber($entity->getContactNumber());
                    $entityManager->persist($newContactNumber);
                    $entityManager->flush();
                }
            }
        }
    }
}

