<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Yilinker\Bundle\CoreBundle\Entity\UserAddress;

class UserAddressListener
{
    public function singleDefault($userAddress, $args)
    {
        $user = $userAddress->getUser();
        if ($user) {
            $em = $args->getEntityManager();
            $tbUserAddress = $em->getRepository('YilinkerCoreBundle:UserAddress');
            $defaultAddresses = $tbUserAddress->getUserDefaultAddress($user->getUserId(), true);
            if (!$defaultAddresses) {
                $defaultAddress = $user->getAddresses()->first();
                if ($defaultAddress) {
                    $em->transactional(function($em) use ($defaultAddress) {
                        $defaultAddress->setIsDefault(true);
                    });
                }
            }
            elseif (is_array($defaultAddresses) && $userAddress->getIsDefault() && count($defaultAddresses) > 1) {
                $em->transactional(function($em) use ($defaultAddresses, $userAddress) {
                    foreach ($defaultAddresses as $defaultAddress) {
                        if ($userAddress->getUserAddressId() != $defaultAddress->getUserAddressId()) {
                            $defaultAddress->setIsDefault(false);
                        }
                    }
                });
            }
        }
    }

    public function postPersist($userAddress, $args)
    {
        $this->singleDefault($userAddress, $args);
    }

    public function postUpdate($userAddress, $args)
    {
        $this->singleDefault($userAddress, $args);   
    }

    public function postRemove($userAddress, $args)
    {
        $this->singleDefault($userAddress, $args);
    }
}