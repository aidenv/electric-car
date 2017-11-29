<?php


namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Carbon\Carbon;
use Faker\Factory;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $encoder = $this->container->get("security.password_encoder");

        $buyer1 = new User();
        $userPassword = $encoder->encodePassword($buyer1, "password1");
        $buyer1->setFirstName($faker->firstName)
               ->setLastName($faker->lastName)
               ->setPassword($userPassword)
               ->setEmail("buyer@yilinker.com")
               ->setContactNumber("0000000001")
               ->setDateAdded(Carbon::now())
               ->setDateLastModified(Carbon::now())
               ->setGender("M")
               ->setIsActive(true)
               ->setIsMobileVerified(true)
               ->setIsEmailVerified(false)
               ->setUserType(User::USER_TYPE_BUYER)
               ->setLoginCount(0)
               ->setReferralCode("BUYER001")
               ->setAccountId(1)
               ->setCountry($this->getReference("philippines"))
               ->setIsBanned(false);

        $buyerAffiliate1 = new User();
        $userPassword = $encoder->encodePassword($buyerAffiliate1, "password1");
        $buyerAffiliate1->setFirstName($faker->firstName)
                        ->setLastName($faker->lastName)
                        ->setPassword($userPassword)
                        ->setEmail("buyer@yilinker.com")
                        ->setContactNumber("0000000001")
                        ->setDateAdded(Carbon::now())
                        ->setDateLastModified(Carbon::now())
                        ->setGender("M")
                        ->setIsActive(true)
                        ->setIsMobileVerified(true)
                        ->setIsEmailVerified(false)
                        ->setLoginCount(0)
                        ->setReferralCode("AFFILIATE001")
                        ->setAccountId(1)
                        ->setUserType(User::USER_TYPE_SELLER)
                        ->setCountry($this->getReference("philippines"))
                        ->setIsBanned(false);

        $buyer2 = new User();
        $userPassword = $encoder->encodePassword($buyer2, "password1");
        $buyer2->setFirstName($faker->firstName)
               ->setLastName($faker->lastName)
               ->setPassword($userPassword)
               ->setEmail("buyer+0002@yilinker.com")
               ->setContactNumber("0000000002")
               ->setDateAdded(Carbon::now())
               ->setDateLastModified(Carbon::now())
               ->setGender("M")
               ->setIsActive(true)
               ->setIsMobileVerified(true)
               ->setIsEmailVerified(false)
               ->setUserType(User::USER_TYPE_BUYER)
               ->setLoginCount(0)
               ->setReferralCode("BUYER002")
               ->setAccountId(2)
               ->setCountry($this->getReference("philippines"))
               ->setIsBanned(false);

        $buyerAffiliate2 = new User();
        $userPassword = $encoder->encodePassword($buyerAffiliate2, "password1");
        $buyerAffiliate2->setFirstName($faker->firstName)
                        ->setLastName($faker->lastName)
                        ->setPassword($userPassword)
                        ->setEmail("buyer+0002@yilinker.com")
                        ->setContactNumber("0000000002")
                        ->setDateAdded(Carbon::now())
                        ->setDateLastModified(Carbon::now())
                        ->setGender("M")
                        ->setIsActive(true)
                        ->setIsMobileVerified(true)
                        ->setIsEmailVerified(false)
                        ->setLoginCount(0)
                        ->setReferralCode("AFFILIATE002")
                        ->setAccountId(2)
                        ->setUserType(User::USER_TYPE_SELLER)
                        ->setCountry($this->getReference("philippines"))
                        ->setIsBanned(false);

        $seller1 = new User();
        $userPassword = $encoder->encodePassword($seller1, "password1");
        $seller1->setFirstName($faker->firstName)
                ->setLastName($faker->lastName)
                ->setPassword($userPassword)
                ->setEmail("seller@yilinker.com")
                ->setContactNumber("0000000001")
                ->setDateAdded(Carbon::now())
                ->setDateLastModified(Carbon::now())
                ->setGender("M")
                ->setIsActive(true)
                ->setIsMobileVerified(true)
                ->setIsEmailVerified(false)
                ->setLoginCount(0)
                ->setReferralCode("SELLER001")
                ->setAccountId(3)
                ->setUserType(User::USER_TYPE_SELLER)
                ->setCountry($this->getReference("philippines"))
                ->setIsBanned(false);

        $seller2 = new User();
        $userPassword = $encoder->encodePassword($seller2, "password1");
        $seller2->setFirstName($faker->firstName)
                ->setLastName($faker->lastName)
                ->setPassword($userPassword)
                ->setEmail("seller+003@yilinker.com")
                ->setContactNumber("0000000003")
                ->setDateAdded(Carbon::now())
                ->setDateLastModified(Carbon::now())
                ->setGender("M")
                ->setIsActive(true)
                ->setIsMobileVerified(true)
                ->setIsEmailVerified(false)
                ->setLoginCount(0)
                ->setReferralCode("SELLER002")
                ->setAccountId(4)
                ->setUserType(User::USER_TYPE_SELLER)
                ->setCountry($this->getReference("philippines"))
                ->setIsBanned(false);

        $buyer3 = new User();
        $userPassword = $encoder->encodePassword($buyer3, "password123");
        $buyer3->setFirstName($faker->firstName)
               ->setLastName($faker->lastName)
               ->setPassword($userPassword)
               ->setEmail("buyer3@yilinker.com")
               ->setContactNumber("09071234567")
               ->setDateAdded(Carbon::now())
               ->setDateLastModified(Carbon::now())
               ->setGender("M")
               ->setIsActive(true)
               ->setIsMobileVerified(true)
               ->setIsEmailVerified(true)
               ->setUserType(User::USER_TYPE_BUYER)
               ->setLoginCount(0)
               ->setReferralCode("BUYER003")
               ->setAccountId(1)
               ->setCountry($this->getReference("philippines"))
               ->setIsBanned(false);

        $buyerAffiliate3 = new User();
        $userPassword = $encoder->encodePassword($buyerAffiliate3, "password123");
        $buyerAffiliate3->setFirstName($faker->firstName)
            ->setLastName($faker->lastName)
            ->setPassword($userPassword)
            ->setEmail("buyer_affiliate3@yilinker.com")
            ->setContactNumber("09071234567")
            ->setDateAdded(Carbon::now())
            ->setDateLastModified(Carbon::now())
            ->setGender("M")
            ->setIsActive(true)
            ->setIsMobileVerified(true)
            ->setIsEmailVerified(false)
            ->setLoginCount(0)
            ->setReferralCode("AFFILIATE003")
            ->setAccountId(1)
            ->setUserType(User::USER_TYPE_SELLER)
            ->setCountry($this->getReference("philippines"))
            ->setIsBanned(false);

        $manager->persist($buyer1);
        $manager->persist($buyerAffiliate1);
        $manager->persist($buyer2);
        $manager->persist($buyer3);
        $manager->persist($buyerAffiliate2);
        $manager->persist($buyerAffiliate3);
        $manager->persist($seller1);
        $manager->persist($seller2);
        $manager->flush();

        $this->addReference("buyer1", $buyer1);
        $this->addReference("buyerAffiliate1", $buyerAffiliate1);
        $this->addReference("buyer2", $buyer2);
        $this->addReference("buyer3", $buyer3);
        $this->addReference("buyerAffiliate2", $buyerAffiliate2);
        $this->addReference("buyerAffiliate3", $buyerAffiliate3);

        $this->addReference("seller1", $seller1);
        $this->addReference("seller2", $seller2);
    }

    public function getDependencies()
    {
        return array("Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Globalization\LoadCountryData");
    }
}


