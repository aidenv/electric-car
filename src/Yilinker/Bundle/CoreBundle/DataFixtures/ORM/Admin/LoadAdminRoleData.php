<?php

namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Admin;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\AdminRole;

class LoadAdminRoleData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $roles = self::getRoles();

        foreach ($roles as $key => $value) {

            $adminRole = new AdminRole();
            $adminRole->setName($value)
                      ->setRole($key);

            $manager->persist($adminRole);
            $manager->flush();

            $this->addReference($key, $adminRole);
        }
    }

    public static function getRoles()
    {
        return array(
            'ROLE_ADMIN' => 'Admin User',
            'ROLE_SELLER_SPECIALIST' => 'Seller & Affiliate Accreditation Specialist',
            'ROLE_PRODUCT_SPECIALIST' => 'Product Specialist',
            'ROLE_CSR' => 'Customer Support',
            'ROLE_MARKETING' => 'Promo Marketing',
            'ROLE_ACCOUNTING' => 'Accounting',
            'ROLE_OPERATIONS_ADMIN' => 'Operations Admin'
        );
    }
}
