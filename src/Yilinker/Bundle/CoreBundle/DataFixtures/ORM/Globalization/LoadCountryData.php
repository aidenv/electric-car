<?php
namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Globalization;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Faker\Factory as Faker;
use Carbon\Carbon;

class LoadCountryData extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Faker::create();

        $philippines = new Country();
        $philippines->setName("Philippines")
                    ->setCode(Country::COUNTRY_CODE_PHILIPPINES)
                    ->setDateAdded(Carbon::now())
                    ->setDateLastModified(Carbon::now())
                    ->setReferenceNumber("001")
                    ->setAreaCode("63");

        $manager->persist($philippines);
        
        $manager->flush();
        $this->addReference("philippines", $philippines);
    }
}