<?php
namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel;

class LoadAccreditationLevelData extends AbstractFixture 
{
    public function load(ObjectManager $manager)
    {
        $accreditationLevel1 = new AccreditationLevel();
        $accreditationLevel1->setName("Accreditaiton Level 1");

        $accreditationLevel2 = new AccreditationLevel();
        $accreditationLevel2->setName("Accreditaiton Level 2");

        $accreditationLevel3 = new AccreditationLevel();
        $accreditationLevel3->setName("Accreditaiton Level 3");

        $manager->persist($accreditationLevel1);
        $manager->persist($accreditationLevel2);
        $manager->persist($accreditationLevel3);
        
        $manager->flush();

        $this->addReference("accreditationLevel1", $accreditationLevel1);
        $this->addReference("accreditationLevel2", $accreditationLevel2);
        $this->addReference("accreditationLevel3", $accreditationLevel3);
    }

}