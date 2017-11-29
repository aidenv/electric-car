<?php

namespace Yilinker\Bundle\MerchantBundle\Provider;

use Exception;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Yilinker\Bundle\MerchantBundle\Provider\RequestStack;

class MerchantProvider implements UserProviderInterface
{
    protected $userRepository;

    protected $request;

    private $userType;

    private $storeType;

    private $container;

    private $availableRoles = array(Store::STORE_TYPE_MERCHANT, Store::STORE_TYPE_RESELLER);

    public function __construct(
        ObjectRepository $userRepository, 
        $userType = User::USER_TYPE_SELLER, 
        $storeType = Store::STORE_TYPE_MERCHANT,
        $container
    )
    {
        $this->storeType = $storeType;
        $this->userRepository = $userRepository;
        $this->userType = $userType;
        $this->container = $container;
    }

    public function setRequest($requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }
   
    public function loadUserByUsername($request)
    {
       $user = $this->userRepository
                     ->createQueryBuilder('u')
                     ->innerJoin('YilinkerCoreBundle:Store', 's', 'WITH', 's.user = u')
                     ->where('u.email = :request OR u.contactNumber = :request')
                     ->andWhere('u.userType = :userType')
                     ->andWhere('s.storeType = :storeType')
                     ->andWhere('u.isActive = :active')
                     ->setParameter('request', $request)
                     ->setParameter('active', true)
                     ->setParameter('userType', User::USER_TYPE_SELLER)
                     ->setParameter('storeType', $this->storeType)             
                     ->getQuery()
                     ->getOneOrNullResult();

       $cookieName = $this->container->getParameter("session.merchant.remember_me.name");

        if(
            !is_null($user) && 
            !is_null($user->getStore()) && 
            $user->getIsActive()
        ){
       
            $store = $user->getStore();

            if(
                /** if has rememberme cookie */
                (
                    in_array($store->getStoreType(), $this->availableRoles) &&
                    $this->request->cookies->has($cookieName) 
                ) ||
                /** if has no rememberme cookie (from login) */
                (
                    !$this->request->cookies->has($cookieName) &&
                    $store->getStoreType() == $this->storeType
                )
            ){
                return $user;
            }
        }

        $message = sprintf(
            'Unable to find valid YilinkerCoreBundle:User object identified by "%s".',
            $request
        );

        throw new UsernameNotFoundException($message, 0);
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
