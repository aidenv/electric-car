<?php

namespace Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Oauth;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadOAuthClientData extends AbstractFixture implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $sqlStatement = $this->container
                             ->get('doctrine.orm.entity_manager')
                             ->getConnection()
                             ->prepare($this->getSql());

        $sqlStatement->execute();
    }

    private function getSql()
    {
        $redirectUri1 = 'a:1:{i:0;s:3:"url";}';
        $allowedGrantType1 = 'a:5:{i:0;s:42:"http://yilinker-online.com/grant/affiliate";i:1;s:38:"http://yilinker-online.com/grant/buyer";i:2;s:39:"http://yilinker-online.com/grant/seller";i:3;s:18:"client_credentials";i:4;s:13:"refresh_token";}';

        return "
          INSERT INTO OauthClient (id, random_id, redirect_uris, secret, allowed_grant_types) VALUES
(4, '1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck', '{$redirectUri1}', '26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w', '{$allowedGrantType1}');
        ";
    }
}


