<?php

namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadAssetDirectories extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $this->mkdir("web/assets/images/uploads", 0777);
        $this->mkdir("web/assets/images/uploads/cms", 0777);
        $this->mkdir("web/assets/images/uploads/users", 0777);
        $this->mkdir("web/assets/images/uploads/users/defaults", 0777);
        $this->mkdir("web/assets/images/uploads/chats", 0777);
        $this->mkdir("web/assets/images/uploads/products", 0777);
        $this->mkdir("web/assets/images/uploads/products/temp", 0777);
        $this->mkdir("web/assets/images/uploads/manufacturer_products", 0777);
        $this->mkdir("web/assets/images/uploads/user_documents", 0777);
        $this->mkdir("web/assets/images/uploads/qr_code", 0777);
    }

    private function mkdir($dir)
    {
        if (!file_exists($dir)) {
            return mkdir($dir, 0777);   
        }

        return false;
    }
}

