<?php

namespace Yilinker\Bundle\CoreBundle\Services\Jwt;

use \Firebase\JWT\JWT;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

/**
 * Class JwtManager
 */
class JwtManager
{
    private $container;

    private $key;

    private $token;

    public function setKey($key = "jwt_secret")
    {
        $this->key = $this->container->getParameter($key);

        return $this;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function encodeToken($token = null)
    {
        if(is_null($this->key)){
            $this->setKey();
        }

    	return JWT::encode(is_null($token)? $this->token : $token, $this->key);
    }

    public function decodeToken($jwt)
    {
        if(is_null($this->key)){
            $this->setKey();
        }

    	return json_decode(json_encode((array)JWT::decode($jwt, $this->key, array("HS256"))), true);
    }

    public function encodeUser(User $user)
    {
        if(!is_null($user)){
            if(!$user->getIsSocialMedia()){
                $this->token = array(
                    "userId" => $user->getAccountId(),
                    "email" => $user->getEmail(),
                    "password" => $user->getPassword(),
                    "firstName" => $user->getFirstName(),
                    "lastName" => $user->getLastName(),
                    "isEmailVerified" => $user->getIsEmailVerified(),
                    "isSocialMedia" => $user->getIsSocialMedia(),
                    "accountType" => (
                        $user->getUserType() == User::USER_TYPE_SELLER &&
                        $user->getStore() &&
                        $user->getStore()->getStoreType() == Store::STORE_TYPE_MERCHANT
                    )? User::YLA_CORPORATE : User::YLA_CONSUMER
                );
            }
            else{

                $socialMediaAccounts = array();

                if($user->getSocialMediaAccounts()){
                    foreach($user->getSocialMediaAccounts() as $socialMediaAccount){
                        if(!array_key_exists($socialMediaAccount->getSocialMediaId(), $socialMediaAccounts)){
                            $socialMediaAccounts[$socialMediaAccount->getSocialMediaId()] = array(
                                "socialMediaId" => $socialMediaAccount->getSocialMediaId(),
                                "provider" => $socialMediaAccount->getOauthProvider()->getOauthProviderId()
                            );
                        }
                    }
                }

                $this->token = array(
                    "userId" => $user->getAccountId(),
                    "email" => $user->getEmail(),
                    "password" => $user->getPassword(),
                    "firstName" => $user->getFirstName(),
                    "lastName" => $user->getLastName(),
                    "isEmailVerified" => $user->getIsEmailVerified(),
                    "isSocialMedia" => $user->getIsSocialMedia(),
                    "accountType" => (
                        $user->getUserType() == User::USER_TYPE_SELLER &&
                        $user->getStore() &&
                        $user->getStore()->getStoreType() == Store::STORE_TYPE_MERCHANT
                    )? User::YLA_CORPORATE : User::YLA_CONSUMER,
                    "socialMediaAccounts" => $socialMediaAccounts
                );
            }

            return $this;
        }

        return false;
    }

    public function getToken()
    {
        return $this->token;
    }
}
