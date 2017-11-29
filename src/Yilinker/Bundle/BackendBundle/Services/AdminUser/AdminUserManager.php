<?php

namespace Yilinker\Bundle\BackendBundle\Services\AdminUser;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;

/**
 * Class AdminUserManager
 */
class AdminUserManager
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct (EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Add Admin User Account
     * @param AdminUser $adminUser
     * @return AdminUser
     */
    public function addAdminUser (AdminUser $adminUser)
    {
        $adminUser->setDateAdded(Carbon::now());
        $adminUser->setLastDateModified(Carbon::now());
        $adminUser->setIsActive(true);
        $this->em->persist($adminUser);
        $this->em->flush();

        return $adminUser;
    }

    /**
     * Edit Admin User
     * @param AdminUser $adminUser
     * @param $firstName
     * @param $lastName
     * @return AdminUser
     */
    public function editAdminUser (AdminUser $adminUser, $firstName, $lastName)
    {
        $adminUser->setFirstName($firstName);
        $adminUser->setLastName($lastName);
        $adminUser->setLastDateModified(Carbon::now());
        $this->em->persist($adminUser);
        $this->em->flush();

        return $adminUser;
    }

    /**
     * Deactivate Admin user
     * @param AdminUser $adminUser
     * @param $isActive
     * @return AdminUser
     */
    public function deactivateAdminUser (AdminUser $adminUser, $isActive)
    {
        $adminUser->setIsActive($isActive);
        $adminUser->setLastDateModified(Carbon::now());
        $this->em->persist($adminUser);
        $this->em->flush();

        return $adminUser;
    }


}
