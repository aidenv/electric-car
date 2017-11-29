<?php

namespace Yilinker\Bundle\MerchantBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class MerchantAuthListener
{
    private $tokenStorage;

    private $authService;
    
    public function __construct($tokenStorage, $authService)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authService = $authService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if(!is_null($this->tokenStorage)){
            $token = $this->tokenStorage->getToken();

            if($token instanceof RememberMeToken){
                $user = $token->getUser();
                $store = $user->getStore();

                if($store->getStoreType() == Store::STORE_TYPE_MERCHANT){
                    $this->authService->authenticateUser($user, 'seller');
                }
                elseif($store->getStoreType() == Store::STORE_TYPE_RESELLER){
                    $this->authService->authenticateUser($user, 'affiliate');
                }
            }
        }
    }
}
