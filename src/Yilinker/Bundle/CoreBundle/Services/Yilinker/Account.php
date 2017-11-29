<?php

namespace Yilinker\Bundle\CoreBundle\Services\Yilinker;

use Monolog\Logger AS SymfonyLogger;
use Monolog\Handler\StreamHandler;
use Carbon\Carbon;
use Exception;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class GuzzleService
 */
class Account
{
    private $config;

    private $client;

    private $container;

    private $ylaBaseUri = null;

    private $clientId = null;

    private $clientSecret = null;

    private $accessToken = null;

    private $accessTokenExpiration = null;

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function setEndpoint($oauthEnabled = false)
    {
        $this->ylaBaseUri = $this->container->getParameter("yla_hostname");

        if($oauthEnabled && (is_null($this->clientId) || is_null($this->clientSecret))){
            $this->clientId = $this->container->getParameter("yla_client_id");
            $this->clientSecret = $this->container->getParameter("yla_client_secret");
        }

        return $this;
    }

    public function getAccessToken()
    {
        $session = $this->container->get("session");

        $accessToken = $session->get("access_token", null);
        $refreshToken = $session->get("refresh_token", null);
        $expiration = $session->get("access_token_expiration", null);

        if(!is_null($expiration)){
            $authenticatedUser = $this->getAuthenticatedUser();

            $expiration = Carbon::createFromFormat("Y-m-d H:i:s", $expiration);

            if($expiration->lte(Carbon::now())){

                $response = $this->sendRequest("login", "post", array(
                    "client_id" => $this->clientId,
                    "client_secret" => $this->clientSecret,
                    "refresh_token" => $refreshToken,
                    "grant_type" => "refresh_token"
                ));

                if(array_key_exists("access_token", $response)){
                    $session->set("access_token", $response["access_token"]);
                    $session->set("refresh_token", $response["refresh_token"]);
                    $session->set("access_token_expiration", Carbon::now()->addMinutes(55)->format("Y-m-d H:i:s"));

                    return $response["access_token"];
                }
                else{
                    return false;
                }
            }
            else{
                return $accessToken;
            }
        }

        return false;
    }

    public function getRoute($endpoint = "")
    {
        if(array_key_exists($endpoint, $this->config["routes"])){
            return $this->ylaBaseUri.$this->config["routes"][$endpoint];
        }

        return false;
    }

    public function getClientToken()
    {
        $session = $this->container->get("session");

        $accessToken = $session->get("access_token", null);
        $expiration = $session->get("access_token_expiration", null);

        if(!is_null($expiration)){

            $expiration = Carbon::createFromFormat("Y-m-d H:i:s", $expiration);

            if($expiration->lte(Carbon::now())){
                $response = $this->requestClientToken();
            }
            else{
                return $accessToken;
            }
        }
        else{
            $response = $this->requestClientToken();
        }

        if(array_key_exists("access_token", $response)){
            $session = $this->container->get("session");
            $session->set("access_token", $response["access_token"]);
            $session->set("refresh_token", null);
            $session->set("access_token_expiration", Carbon::now()->addMinutes(55)->format("Y-m-d H:i:s"));

            return $response["access_token"];
        }
    }

    public function getClientCredentials()
    {
        return array(
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret
        );
    }

    public function sendRequest($endpoint = "", $method = "post", $data = array())
    {
        $route = null;
        if(is_array($endpoint)){
            $queryString = http_build_query($endpoint["params"]);
            if(array_key_exists($endpoint = $endpoint["route"], $this->config["routes"])){
                $route = $this->ylaBaseUri.$this->config["routes"][$endpoint]."?".$queryString;
            }
            else{
                throw new Exception("Route not found");
            }
        }
        else{
            if(array_key_exists($endpoint, $this->config["routes"])){
                $route = $this->ylaBaseUri.$this->config["routes"][$endpoint];
            }
            else{
                throw new Exception("Route not found");
            }
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $route);

        if($method == "post"){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if($response){
            return $response;
        }
        else{

            $kernelDir = $this->container->getParameter("kernel.root_dir");

            $log = new SymfonyLogger('account');

            $log->pushHandler(new StreamHandler($kernelDir.'/../accounts/logs/prod.log', SymfonyLogger::EMERGENCY));

            $log->addEmergency(json_encode(array(
                "date"  => Carbon::now()->format("Y-m-d H:i:d"),
                "route" => $route,
                "data"  => $data
            )));

            return false;
        }
    }

    private function requestClientToken()
    {
        return $this->sendRequest("login", "post", array(
                    "client_id" => $this->clientId,
                    "client_secret" => $this->clientSecret,
                    "grant_type" => "client_credentials"
                ));
    }

    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }
}
