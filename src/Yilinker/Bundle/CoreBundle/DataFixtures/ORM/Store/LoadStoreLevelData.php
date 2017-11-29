<?php
namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\StoreLevel;

class LoadStoreLevelData extends AbstractFixture 
{
    public function load(ObjectManager $manager)
    {
        $silver = new StoreLevel();
        $silver->setName("Silver")
               ->setStoreSpace(150)
               ->setStoreEarning(5000);

        $gold = new StoreLevel();
        $gold->setName("Gold")
             ->setStoreSpace(250)
             ->setStoreEarning(20000);

        $platinum = new StoreLevel();
        $platinum->setName("Platinum")
                 ->setStoreSpace(350)
                 ->setStoreEarning(50000);

        $manager->persist($silver);
        $manager->persist($gold);
        $manager->persist($platinum);
        
        $manager->flush();

        $this->addReference('silver', $silver);
        $this->addReference('gold', $gold);
        $this->addReference('platinum', $platinum);
    }
}