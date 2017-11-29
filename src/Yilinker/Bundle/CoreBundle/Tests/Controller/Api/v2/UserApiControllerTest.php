<?php

namespace Yilinker\Bundle\CoreBundle\Tests\Controller\Api\v2;

use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Tests\YilinkerCoreWebTestCase;
use Yilinker\Bundle\CoreBundle\Traits\ContainerHandler;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Traits\AccessTokenGenerator;
use Yilinker\Bundle\CoreBundle\Services\SMS\OneTimePasswordService;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;

class UserApiControllerTest extends YilinkerCoreWebTestCase
{
    use ContainerHandler;
    use FormHandler;
    use AccessTokenGenerator;

    public function validRegisterDataProvider()
    {
        return array(
            array(
                //with buyer to buyer referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671101",
                    "verificationCode" => "votp01",
                    "areaCode" => "63",
                    "referralCode" => "BUYER001",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671101",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/buyer"
                )
            ),
            array(
                //with affiliate to affiliate referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671102",
                    "verificationCode" => "votp02",
                    "areaCode" => "63",
                    "referralCode" => "AFFILIATE001",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_SELLER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671102",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/affiliate"
                )
            ),
            array(
                //with affiliate to buyer referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671103",
                    "verificationCode" => "votp03",
                    "areaCode" => "63",
                    "referralCode" => "AFFILIATE001",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671103",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/buyer"
                )
            ),
            array(
                //with seller to buyer referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671104",
                    "verificationCode" => "votp04",
                    "areaCode" => "63",
                    "referralCode" => "SELLER001",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671104",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/buyer"
                )
            ),
            array(
                //register seller no referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671105",
                    "verificationCode" => "votp05",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_MERCHANT,
                    "userType" => User::USER_TYPE_SELLER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671105",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/seller"
                )
            ),
            array(
                //register affiliate no referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671106",
                    "verificationCode" => "votp06",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_SELLER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671106",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/affiliate"
                )
            ),
            array(
                //register buyer no referral
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671107",
                    "verificationCode" => "votp07",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                    "email" => "09056671107",
                    "client_id" => "4_1iea3xa1wow00s8ccg40g80ksgc484kkgk8s4804ss48sw8wck",
                    "client_secret" => "26g1z2ztijk0s0gowkk008w4c08sskwg80o00g800ow0o8w08w",
                    "grant_type" => "http://yilinker-online.com/grant/buyer"
                )
            ),
        );
    }

    /**
     * @dataProvider validRegisterDataProvider
     * @param array $data
     */
    public function testRegisterUserActionSuccess($provider)
    {
        $container = $this->client->getContainer();
        $kernel = $container->get("kernel")->getName();

        $this->setMainContainer($container);

        $em = $container->get("doctrine.orm.entity_manager");

        $userType = $provider["userType"];
        $storeType = $provider["storeType"];

        $formData = array(
            "plainPassword"     => $provider["plainPassword"],
            "contactNumber"     => $provider["contactNumber"],
            "verificationCode"  => $provider["verificationCode"],
            "areaCode"          => $provider["areaCode"],
            "referralCode"      => $provider["referralCode"]
        );

        $form = $this->getForm("core_user_add", null, $formData, array(
            "csrf_protection" => false,
            "storeType"       => $storeType,
            "mustVerify"      => true,
            "contactNumber"   => $provider["contactNumber"],
            "token"           => $provider["verificationCode"],
            "user"            => null,
            "type"            => $provider["otpType"],
            "areaCode"        => $provider["areaCode"],
            "userType"        => $userType
        ));

        $data = $form->getData();
        $accountManager = $container->get("yilinker_core.service.account_manager");
        $user = $accountManager->mapUser($data, $userType, $storeType);

        $accountId = $user->getAccountId();

        $users = $em->getRepository("YilinkerCoreBundle:User")->findByAccountId($accountId);

        $this->assertTrue($form->isValid());
        $this->assertTrue($user instanceof User);

        $requestStack = $container->get("request_stack");

        $request = new Request();
        $request->setMethod("POST");
        $request->request->set("client_id", $provider["client_id"]);
        $request->request->set("client_secret", $provider["client_secret"]);
        $request->request->set("grant_type", $provider["grant_type"]);
        $request->request->set("email", $provider["email"]);
        $request->request->set("password", $data["plainPassword"]);

        $oauthClientRepo = $container->get("doctrine.orm.entity_manager")->getRepository("YilinkerCoreBundle:OauthClient")->findAll();

        $accessToken = $this->generateAccessToken($request);


        if(
            $provider["userType"] === User::USER_TYPE_BUYER ||
            (
                $provider["userType"] === User::USER_TYPE_SELLER && 
                $storeType === Store::STORE_TYPE_RESELLER
            )
        ){
            $this->assertSame(2, count($users)); 

            if($kernel == "frontend" && $provider["userType"] == User::USER_TYPE_BUYER){
                $this->assertArrayHasKey("access_token", $accessToken);
                $this->assertArrayHasKey("refresh_token", $accessToken);
            }
            elseif(
                $kernel == "merchant" && 
                $provider["userType"] == User::USER_TYPE_SELLER && 
                $provider["storeType"] == Store::STORE_TYPE_RESELLER
            ){
                $this->assertArrayHasKey("access_token", $accessToken);
                $this->assertArrayHasKey("refresh_token", $accessToken);
            }
            else{
                $this->assertArrayHasKey("error", $accessToken);
                $this->assertContains("unsupported_grant_type", $accessToken);
            }
        }
        else{
            $this->assertSame(1, count($users)); 

            if($kernel == "frontend"){
                $this->assertArrayHasKey("error", $accessToken);
                $this->assertContains("unsupported_grant_type", $accessToken);
            }
            elseif($kernel == "merchant" && $provider["storeType"] == Store::STORE_TYPE_MERCHANT){
                $this->assertArrayHasKey("error", $accessToken);
                $this->assertContains("unaccredited", $accessToken);
            }
            else{
                $this->assertArrayHasKey("access_token", $accessToken);
                $this->assertArrayHasKey("refresh_token", $accessToken);
            }
        }
    }

    public function invalidRegisterDataProvider()
    {
        return array(
            array(
                //password not match
                array(
                    "plainPassword" => array(
                        "first" => "password2",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //password not blank
                array(
                    "plainPassword" => array(
                        "first" => "",
                        "second" => ""
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //password not null
                array(
                    "plainPassword" => array(
                        "first" => null,
                        "second" => null
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //password min 8
                array(
                    "plainPassword" => array(
                        "first" => "pswd1",
                        "second" => "pswd1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //password max 25
                array(
                    "plainPassword" => array(
                        "first" => "longpasswordthatismorethantwentyfivecharacters!1",
                        "second" => "longpasswordthatismorethantwentyfivecharacters!1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //yilinker password
                array(
                    "plainPassword" => array(
                        "first" => "password",
                        "second" => "password"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //contact number not null
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => null,
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //not blank
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //valid contact number
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "0905667110E",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //contact number unique
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671101",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //verification code not blank
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //verification code not null
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => null,
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //verification code valid
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "iotp01",
                    "areaCode" => "63",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //area code not blank
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //area code not null
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => null,
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //area code valid
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "INVALID",
                    "referralCode" => null,
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_BUYER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //buyer to affiliate
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => "BUYER001",
                    "storeType" => Store::STORE_TYPE_RESELLER,
                    "userType" => User::USER_TYPE_SELLER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //buyer to seller
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => "BUYER001",
                    "storeType" => Store::STORE_TYPE_MERCHANT,
                    "userType" => User::USER_TYPE_SELLER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            ),
            array(
                //seller to seller
                array(
                    "plainPassword" => array(
                        "first" => "password1",
                        "second" => "password1"
                    ),
                    "contactNumber" => "09056671108",
                    "verificationCode" => "votp08",
                    "areaCode" => "63",
                    "referralCode" => "SELLER001",
                    "storeType" => Store::STORE_TYPE_MERCHANT,
                    "userType" => User::USER_TYPE_SELLER,
                    "otpType" => OneTimePasswordService::OTP_TYPE_REGISTER,
                )
            )
        );
    }

    /**
     * @dataProvider invalidRegisterDataProvider
     * @param array $data
     */
    public function testRegisterUserActionFail($provider)
    {
        $container = $this->client->getContainer();
        $kernel = $container->get("kernel")->getName();

        $this->setMainContainer($container);

        $em = $container->get("doctrine.orm.entity_manager");

        $userType = $provider["userType"];
        $storeType = $provider["storeType"];

        $formData = array(
            "plainPassword"     => $provider["plainPassword"],
            "contactNumber"     => $provider["contactNumber"],
            "verificationCode"  => $provider["verificationCode"],
            "areaCode"          => $provider["areaCode"],
            "referralCode"      => $provider["referralCode"]
        );

        $form = $this->getForm("core_user_add", null, $formData, array(
            "csrf_protection" => false,
            "storeType"       => $storeType,
            "mustVerify"      => true,
            "contactNumber"   => $provider["contactNumber"],
            "token"           => $provider["verificationCode"],
            "user"            => null,
            "type"            => $provider["otpType"],
            "areaCode"        => $provider["areaCode"],
            "userType"        => $userType
        ));

        $this->assertFalse($form->isValid());
    }

    private function getForm($formType, $entity = null, $request = array(), $options = array())
    {
        return $this->transactForm($formType, $entity, $request, $options);
    }
}

