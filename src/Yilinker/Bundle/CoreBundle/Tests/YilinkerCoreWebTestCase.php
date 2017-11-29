<?php

namespace Yilinker\Bundle\CoreBundle\Tests;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yilinker\Bundle\CoreBundle\Traits\AccessTokenGenerator;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;


class YilinkerCoreWebTestCase extends WebTestCase
{
    use AccessTokenGenerator;

    protected $client;
    protected $token;
    protected $fixtures;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->fixtures = $this->loadFixtures(array(
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User\LoadAssetDirectories',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Globalization\LoadCountryData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store\LoadAccreditationLevelData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store\LoadStoreLevelData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User\LoadUserData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Store\LoadStoreData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\User\LoadOneTimePasswordData',
            'Yilinker\Bundle\CoreBundle\DataFixtures\ORM\Oauth\LoadOAuthClientData',
        ), null, 'doctrine', ORMPurger::PURGE_MODE_TRUNCATE)->getReferenceRepository();

        $this->client = static::createClient();
    }

    protected function createAuthenticatedClient($username, $password)
    {
        $credentials = array(
            'username' => $username,
            'password' => $password
        );

        $this->client = static::makeClient($credentials);

        return $this->client;
    }

    protected function createAuthenticatedUser($username)
    {
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => 'password',
        ));

        return $this->client;
    }

    protected function accessToken($grantType='buyer')
    {
        $container = $this->client->getContainer();

        $kernel = $container->get('kernel');

        if ($kernel->getName() == 'frontend') {
            $grantType = 'buyer';
        } else if ($kernel->getName() == 'merchant') {
            $grantType = 'seller';
        }

        $provider = $this->provider($grantType);

        $request = new Request();
        $request->setMethod("POST");
        $request->request->set("client_id", $provider["client_id"]);
        $request->request->set("client_secret", $provider["client_secret"]);
        $request->request->set("grant_type", $provider["grant_type"]);
        $request->request->set("email", $provider["email"]);
        $request->request->set("password", $provider["plainPassword"]);

        $token = $this->generateAccessToken($request);


        $this->token = $token;

        $auth = $container->get("yilinker_core.security.authentication");
        $buyer = $this->fixtures->getReference('buyer3');
        $affiliate = $this->fixtures->getReference('buyerAffiliate3');
        $seller = $this->fixtures->getReference('seller1');


        if($provider['userType'] == User::USER_TYPE_BUYER){
            $auth->authenticateUser($buyer, 'buyer', array('ROLE_BUYER'));
        }
        else{
            if($provider['storeType'] == Store::STORE_TYPE_MERCHANT){
                $auth->authenticateUser($seller, 'seller', array('ROLE_UNACCREDITED_MERCHANT'));
            }
            else if ($provider['storeType'] == Store::STORE_TYPE_RESELLER){
                $auth->authenticateUser($affiliate, 'affiliate', array('ROLE_UNACCREDITED_MERCHANT'));
            }
        }

    }

    protected function provider($key='buyer') 
    {
        $provider = array(
            'buyer' => array(
                    "plainPassword" => 'password123',
                    "contactNumber" => "09071234567",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "email" => "buyer3@yilinker.com",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/buyer"
                ),
            'affiliate' => array(
                    "plainPassword" => 'password123', 
                    "contactNumber" => "09071234567",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_SELLER,
                    "email" => "buyer_affiliate3@yilinker.com",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/affiliate"
                ),
            'seller' => array(
                    "plainPassword" => 'password1',
                    "contactNumber" => "0000000001",
                    "storeType" => Store::STORE_TYPE_MERCHANT,
                    "userType" => User::USER_TYPE_SELLER,
                    "email" => "0000000001",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/seller"
                ),
        );

        return $provider[$key];
    }
}
