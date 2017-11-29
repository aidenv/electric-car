<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Subscriber;

use Yilinker\Bundle\CoreBundle\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserSubscriber implements EventSubscriber
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
            'prePersist',
        );
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);
        if ($entity instanceof User) {
            if (isset($changeSet['password'][0])) {
                /**
                 * If the password field has been changed, set back the value of the password field 
                 * so that isPasswordValid() uses the correct password.
                 *
                 * This is necessary because User:setPlainPassword sets the password field as an empty string
                 */ 
                $entity->setPassword($changeSet['password'][0]);
            }

            /**
             * If plainPassword is set and the plainPassword is not the currently valid password, reencode the new plainPassword
             */
            if ($entity->getPlainPassword() !== null &&
                trim($entity->getPlainPassword()) !== "" &&
                $this->passwordEncoder->isPasswordValid($entity, $entity->getPlainPassword()) === false
            ) {
                $this->encodePassword($entity);
            }
        }
    }

    /**
     * Encode password
     * @param  User $user
     */
    private function encodePassword(User $user)
    {
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($encodedPassword);
    }
}
