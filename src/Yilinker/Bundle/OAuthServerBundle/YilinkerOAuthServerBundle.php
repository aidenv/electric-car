<?php

namespace Yilinker\Bundle\OAuthServerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class YilinkerOAuthServerBundle extends Bundle
{
    /**
     * Extend from FOSOAuthServerBundle so we can override the authorizeAction controller
     */
    public function getParent()  
    {  
        return 'FOSOAuthServerBundle';  
    }      
}
