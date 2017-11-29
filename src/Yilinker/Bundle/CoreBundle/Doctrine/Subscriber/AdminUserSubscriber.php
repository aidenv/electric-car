<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Subscriber;

use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

/**
 * Class AdminUserSubscriber
 * @package Yilinker\Bundle\CoreBundle\Doctrine\Subscriber
 */
class AdminUserSubscriber implements EventSubscriber
{

    /**
     * @var UserPasswordEncoder
     */
    private $passwordEncoder;

    /**
     * @param UserPasswordEncoder $passwordEncoder
     */
    public function __construct(UserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
            'prePersist',
        );
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function index(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);

        if ($entity instanceof AdminUser) {

            if (isset($changeSet['password'][0])) {
                $entity->setPassword($changeSet['password'][0]);
            }

            if (trim($entity->getPlainPassword()) !== ""
                && $entity->getPlainPassword() !== null
                && $this->passwordEncoder->isPasswordValid($entity, $entity->getPlainPassword()) === false
            ) {
                $this->encodePassword($entity);
            }
        }

    }

    /**
     * Encode password
     * @param  AdminUser $user
     */
    private function encodePassword(AdminUser $user)
    {
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
        $user->setPassword($encodedPassword);
    }

}
