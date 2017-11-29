<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserProfileListener
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param  LifecycleEventArgs $event
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof User) {

            if(
                (
                    $entity->getUserType() == User::USER_TYPE_BUYER ||
                    (
                        $entity->getUserType() == User::USER_TYPE_SELLER &&
                        $entity->getStore() &&
                        $entity->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER
                    )
                ) &&
                (
                    $args->hasChangedField('email') ||
                    $args->hasChangedField('contactNumber') ||
                    $args->hasChangedField('password') ||
                    $args->hasChangedField('firstName') ||
                    $args->hasChangedField('lastName') ||
                    $args->hasChangedField('isActive') ||
                    $args->hasChangedField('isEmailVerified') ||
                    $args->hasChangedField('isMobileVerified') ||
                    $args->hasChangedField('isSocialMedia')
                )
            ){
                $this->formatContactNumber($entity);
                $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');

                /**
                 * Disable updates on accounts that are created
                 * during YLA downtime
                 */
                if(!is_null($entity->getAccountId())){
                    $userRepository->updateUserAccounts(
                        $entity->getEmail(),
                        $entity->getContactNumber(),
                        $entity->getPassword(),
                        $entity->getFirstName(),
                        $entity->getLastName(),
                        $entity->getIsActive(),
                        $entity->getIsEmailVerified(),
                        $entity->getIsMobileVerified(),
                        $entity->getIsSocialMedia(),
                        $entity->getAccountId()
                    );
                }
            }
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof User) {
            $this->formatContactNumber($entity);
        }
    }

    private function formatContactNumber($user)
    {
        $country = $user->getCountry();
        $countryCode = $user->getCountry() ? $user->getCountry()->getCode() : Country::COUNTRY_CODE_PHILIPPINES;

        switch($countryCode){
            case Country::COUNTRY_CODE_PHILIPPINES:
                if(strlen($user->getContactNumber()) == 10){
                    $user->setContactNumber("0".$user->getContactNumber());
                }
                break;
        }
    }

    private function updatePassword($changeSet, $user)
    {
        if (isset($changeSet['password'][0])) {
            $user->setPassword($changeSet['password'][0]);
        }

        if ($user->getPlainPassword() !== null &&
            trim($user->getPlainPassword()) !== "" &&
            $this->passwordEncoder->isPasswordValid($user, $user->getPlainPassword()) === false
        ) {
            $this->encodePassword($user);
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
