<?php

namespace Yilinker\Bundle\CoreBundle\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;

/**
  * User Provider
 */
class AccountProvider implements UserProviderInterface
{

    /**
     * User Repository
     *  
     * @var Doctrine\Common\Persistence\ObjectRepository
     */
    protected $adminUserRepository;

    public function __construct(ObjectRepository $adminUserRepository)
    {
        $this->adminUserRepository = $adminUserRepository;
    }
   
    /**
     * Load user by email instead of username
     *
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
       $query = $this->adminUserRepository
                      ->createQueryBuilder('u')
                      ->where('u.username = :username')
                      ->andWhere('u.isActive = :isActive')
                      ->setParameter('username', $username)
                      ->setParameter('isActive', true)
                      ->getQuery();

        try {
            $user = $query->getSingleResult();
        }
        catch (NoResultException $e) {
            $message = sprintf(
                'Unable to find valid YilinkerCoreBundle:AdminUser object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $e);
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->adminUserRepository->find($user->getAdminUserId());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->adminUserRepository->getClassName() === $class
        || is_subclass_of($class, $this->adminUserRepository->getClassName());
    }


}
