<?php


namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Yilinker\Bundle\CoreBundle\Entity\OneTimePassword;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Carbon\Carbon;

class LoadOneTimePasswordData extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $encoder = $this->container->get("security.password_encoder");

        $validOtpRegisterTest1 = new OneTimePassword();
        $validOtpRegisterTest1->setUser(null)
                              ->setContactNumber("09056671101")
                              ->setToken("votp01")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest2 = new OneTimePassword();
        $validOtpRegisterTest2->setUser(null)
                              ->setContactNumber("09056671102")
                              ->setToken("votp02")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest3 = new OneTimePassword();
        $validOtpRegisterTest3->setUser(null)
                              ->setContactNumber("09056671103")
                              ->setToken("votp03")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest4 = new OneTimePassword();
        $validOtpRegisterTest4->setUser(null)
                              ->setContactNumber("09056671104")
                              ->setToken("votp04")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest5 = new OneTimePassword();
        $validOtpRegisterTest5->setUser(null)
                              ->setContactNumber("09056671105")
                              ->setToken("votp05")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest6 = new OneTimePassword();
        $validOtpRegisterTest6->setUser(null)
                              ->setContactNumber("09056671106")
                              ->setToken("votp06")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest7 = new OneTimePassword();
        $validOtpRegisterTest7->setUser(null)
                              ->setContactNumber("09056671107")
                              ->setToken("votp07")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $validOtpRegisterTest8 = new OneTimePassword();
        $validOtpRegisterTest8->setUser(null)
                              ->setContactNumber("09056671108")
                              ->setToken("votp08")
                              ->setDateAdded(Carbon::now())
                              ->setDateLastModified(Carbon::now())
                              ->setTokenExpiration(Carbon::now()->addYears(3))
                              ->setIsActive(true)
                              ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                              ->setCountry($this->getReference("philippines"));

        $invalidOtpRegisterTest1 = new OneTimePassword();
        $invalidOtpRegisterTest1->setUser(null)
                                ->setContactNumber("09056671101")
                                ->setToken("iotp01")
                                ->setDateAdded(Carbon::now())
                                ->setDateLastModified(Carbon::now())
                                ->setTokenExpiration(Carbon::now()->subYears(3))
                                ->setIsActive(false)
                                ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                                ->setCountry($this->getReference("philippines"));

        $invalidOtpRegisterTest2 = new OneTimePassword();
        $invalidOtpRegisterTest2->setUser(null)
                                ->setContactNumber("09056671102")
                                ->setToken("iotp02")
                                ->setDateAdded(Carbon::now())
                                ->setDateLastModified(Carbon::now())
                                ->setTokenExpiration(Carbon::now()->subYears(3))
                                ->setIsActive(false)
                                ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                                ->setCountry($this->getReference("philippines"));

        $invalidOtpRegisterTest3 = new OneTimePassword();
        $invalidOtpRegisterTest3->setUser(null)
                                ->setContactNumber("09056671103")
                                ->setToken("iotp03")
                                ->setDateAdded(Carbon::now())
                                ->setDateLastModified(Carbon::now())
                                ->setTokenExpiration(Carbon::now()->subYears(3))
                                ->setIsActive(false)
                                ->setTokenType(OneTimePasswordService::OTP_TYPE_REGISTER)
                                ->setCountry($this->getReference("philippines"));

        $manager->persist($validOtpRegisterTest1);
        $manager->persist($validOtpRegisterTest2);
        $manager->persist($validOtpRegisterTest3);
        $manager->persist($validOtpRegisterTest4);
        $manager->persist($validOtpRegisterTest5);
        $manager->persist($validOtpRegisterTest6);
        $manager->persist($validOtpRegisterTest7);
        $manager->persist($validOtpRegisterTest8);
        $manager->persist($invalidOtpRegisterTest1);
        $manager->persist($invalidOtpRegisterTest2);
        $manager->persist($invalidOtpRegisterTest3);
        $manager->flush();

        $this->addReference("validOtpRegisterTest1", $validOtpRegisterTest1);
        $this->addReference("validOtpRegisterTest2", $validOtpRegisterTest2);
        $this->addReference("validOtpRegisterTest3", $validOtpRegisterTest3);
        $this->addReference("validOtpRegisterTest4", $validOtpRegisterTest4);
        $this->addReference("validOtpRegisterTest5", $validOtpRegisterTest5);
        $this->addReference("validOtpRegisterTest6", $validOtpRegisterTest6);
        $this->addReference("validOtpRegisterTest7", $validOtpRegisterTest7);
        $this->addReference("validOtpRegisterTest8", $validOtpRegisterTest8);
        $this->addReference("invalidOtpRegisterTest1", $invalidOtpRegisterTest1);
        $this->addReference("invalidOtpRegisterTest2", $invalidOtpRegisterTest2);
        $this->addReference("invalidOtpRegisterTest3", $invalidOtpRegisterTest3);
    }

    public function getDependencies()
    {
        return array("Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Globalization\LoadCountryData");
    }
}


