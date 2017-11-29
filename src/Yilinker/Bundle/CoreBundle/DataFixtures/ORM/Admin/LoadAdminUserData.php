<?php

namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Admin;

use Carbon\Carbon;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Admin\LoadAdminRoleData;

class LoadAdminUserData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        foreach (self::getUserRoles() as $key => $value) {
            $userAdmin = new AdminUser();
            $userAdmin->setAdminRole($this->getReference($key))
                      ->setUsername($value['username'])
                      ->setPlainPassword('password')
                      ->setIsActive(true)
                      ->setFirstname($value['firstname'])
                      ->setLastname('User')
                      ->setDateAdded(Carbon::now())
                      ->setLastDateModified(Carbon::now());

            $manager->persist($userAdmin);
            $manager->flush();
        }
    }

    private static function getUserRoles()
    {
        return array(
            'ROLE_ADMIN' => array(
                'username' => 'admin',
                'firstname' => 'admin',
            ),
            'ROLE_SELLER_SPECIALIST' => array(
                'username' => 'seller_specialist',
                'firstname' => 'seller specialist',
            ),
            'ROLE_PRODUCT_SPECIALIST' => array(
                'username' => 'product_specialist',
                'firstname' => 'product specialist',
            ),
            'ROLE_CSR' => array(
                'username' => 'csr',
                'firstname' => 'customer service',
            ),
            'ROLE_MARKETING' => array(
                'username' => 'marketing',
                'firstname' => 'marketing',
            ),
            'ROLE_ACCOUNTING' => array(
                'username' => 'accounting',
                'firstname' => 'accounting',
            ),
            'ROLE_OPERATIONS_ADMIN' => array(
                'username' => 'operations_admin',
                'firstname' => 'operations_admin',
            )
        );
    }

    public function getDependencies()
    {
        return array('Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Admin\LoadAdminRoleData');
    }
}
