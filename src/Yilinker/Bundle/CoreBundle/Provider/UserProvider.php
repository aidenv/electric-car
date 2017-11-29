<?php

namespace Yilinker\Bundle\CoreBundle\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
  * User Provider
 */
class UserProvider implements UserProviderInterface
{
    /**
     * User Repository
     *  
     * @var Doctrine\Common\Persistence\ObjectRepository
     */
    protected $userRepository;

    /**
     * User Type
     *
     * @var int
     */
    private $userType;

    public function __construct(ObjectRepository $userRepository, $userType = User::USER_TYPE_BUYER)
    {
        $this->userRepository = $userRepository;
        $this->userType = $userType;
    }
   
    /**
     * Load user by email instead of username
     *
     * {@inheritdoc}
     */
    public function loadUserByUsername($request)
    {
        $query = $this->userRepository
                      ->createQueryBuilder('u')
                      ->where('u.email = :request OR u.contactNumber = :request')
                      ->andWhere('u.userType = :userType')
                      ->andWhere('u.isActive = :active')
                      ->setParameter('request', $request)
                      ->setParameter('active', true)
                      ->setParameter('userType', $this->userType)
                      ->setMaxResults(1)
                      ->getQuery();

        try {
            if($request){
                $user = $query->getSingleResult();
            }
            else{
                throw new NoResultException("User not found", 1);
                
            }
        } 
        catch (NoResultException $e) {
            $message = sprintf(
                'Unable to find valid YilinkerCoreBundle:User object identified by "%s".',
                $request
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
        return $this->userRepository->find($user->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->userRepository->getClassName() === $class
        || is_subclass_of($class, $this->userRepository->getClassName());
    }


}
