<?php


namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Carbon\Carbon;
use Faker\Factory;

class LoadStoreData extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $affiliateStore1 = new Store();
        $affiliateStore1->setStorename("Affiliate Store One")
                        ->setStoreDescription($faker->sentence())
                        ->setStoreSlug("affiliate-store-one")
                        ->setUser($this->getReference("buyerAffiliate1"))
                        ->setSlugChanged(true)
                        ->setStoreType(Store::STORE_TYPE_RESELLER)
                        ->setAccreditationLevel($this->getReference("accreditationLevel1"))
                        ->setStoreLevel($this->getReference("silver"));

        $affiliateStore2 = new Store();
        $affiliateStore2->setStoreDescription($faker->sentence())
                        ->setUser($this->getReference("buyerAffiliate2"))
                        ->setSlugChanged(false)
                        ->setStoreType(Store::STORE_TYPE_RESELLER);

        $affiliateStore3 = new Store();
        $affiliateStore3->setStoreDescription($faker->sentence())
                        ->setUser($this->getReference("buyerAffiliate3"))
                        ->setSlugChanged(false)
                        ->setStoreType(Store::STORE_TYPE_RESELLER);

        $sellerStore1 = new Store();
        $sellerStore1->setStorename("Seller Store One")
                        ->setStoreDescription($faker->sentence())
                        ->setStoreSlug("seller-store-one")
                        ->setUser($this->getReference("seller1"))
                        ->setSlugChanged(true)
                        ->setStoreType(Store::STORE_TYPE_MERCHANT)
                        ->setAccreditationLevel($this->getReference("accreditationLevel1"))
                        ->setStoreLevel($this->getReference("silver"));

        $sellerStore2 = new Store();
        $sellerStore2->setStorename("Seller Store Two")
                        ->setStoreDescription($faker->sentence())
                        ->setStoreSlug("seller-store-two")
                        ->setUser($this->getReference("seller2"))
                        ->setSlugChanged(true)
                        ->setStoreType(Store::STORE_TYPE_MERCHANT)
                        ->setAccreditationLevel($this->getReference("accreditationLevel1"))
                        ->setStoreLevel($this->getReference("silver"));

        $manager->persist($affiliateStore1);
        $manager->persist($affiliateStore2);
        $manager->persist($affiliateStore3);
        $manager->persist($sellerStore1);
        $manager->persist($sellerStore2);
        $manager->flush();

        $this->addReference("affiliateStore1", $affiliateStore1);
        $this->addReference("affiliateStore2", $affiliateStore2);
        $this->addReference("affiliateStore3", $affiliateStore3);
        $this->addReference("sellerStore1", $sellerStore1);
        $this->addReference("sellerStore2", $sellerStore2);
    }

    public function getDependencies()
    {
        return array(
            "Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store\LoadAccreditationLevelData",
            "Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store\LoadStoreLevelData",
            "Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User\LoadUserData"
        );
    }
}


