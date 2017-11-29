<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

trait AuthenticatedUserHandler
{
    public function getAuthenticatedUser()
    {
        if(method_exists($this, "get")){
            $tokenStorage = $this->get("security.token_storage");
        }
        else{
            $tokenStorage = $this->getMainContainer()->get("security.token_storage");
        }

        if($tokenStorage->getToken()){
        	return $tokenStorage->getToken()->getUser();
        }
        else{
        	return null;
        }
    }
}
