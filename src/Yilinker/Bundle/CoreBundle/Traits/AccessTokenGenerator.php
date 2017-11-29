<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

use OAuth2\OAuth2ServerException;

trait AccessTokenGenerator
{
    public function generateAccessToken($request){

        if(method_exists($this, "get")){
            $oauthServer = $this->get("fos_oauth_server.server");
        }
        else if(method_exists($this,'getContainer')) {
            $oauthServer = $this->getContainer()->get('fos_oauth_server.server');   
        }
        else{
            $oauthServer = $this->getMainContainer()->get("fos_oauth_server.server");
        }
        
        if(is_null($request->get("email", null))){
        	
        	if(!is_null($request->get("contactNumber", null))){
	        	$request->request->set("email", $request->get("contactNumber"));
        	} 
        	else{
        		return array();
        	}
        }

        try {
            $response = $oauthServer->grantAccessToken($request);
            
            $content = $response->getContent();
            $jsonContent = json_decode($content, true);
            $token = $jsonContent['access_token'];

            $accessToken = $oauthServer->verifyAccessToken($token);

            return $jsonContent;
        } catch (OAuth2ServerException $e) {
        	return json_decode($e->getResponseBody(), true);
        }
    }
}
