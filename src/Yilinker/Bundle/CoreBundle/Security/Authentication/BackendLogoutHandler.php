<?php

namespace Yilinker\Bundle\CoreBundle\Security\Authentication;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\AdminUser;
use Buzz\Message\Response as BuzzResponse;
use Buzz\Message\Form\FormRequest as BuzzForm;
use Buzz\Client\Curl as BuzzCurl;
use Carbon\Carbon;
use \Exception;

class BackendLogoutHandler implements LogoutSuccessHandlerInterface
{
    const CURL_TIMEOUT = 1;
    const CRM_AGENT_PREFIX = "ylo-admin";

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function onLogoutSuccess(Request $request)
    {
        $ts = $this->container->get("security.token_storage");
        $router = $this->container->get("router");

        $token = $ts->getToken();
        if ($token) {
            $user = $token->getUser();
            
            if ($user instanceof AdminUser) {
                $api = $this->container->getParameter("api_crm_hostname");
                $request = new BuzzForm(
                            BuzzForm::METHOD_POST, 
                            "/oauth/logout/ylo", 
                            $api
                        );

                $request->setFields([
                    "oauth_user_id" => self::CRM_AGENT_PREFIX."-".$user->getAdminUserId()
                ]);

                try{
                    $response = new BuzzResponse();

                    $client = new BuzzCurl();
                    $client->setTimeout(self::CURL_TIMEOUT);
                    $client->send($request, $response);
                }
                catch(Exception $e){
                    return new RedirectResponse($router->generate("admin_home_page"));
                }
            }
        }

        return new RedirectResponse($router->generate("admin_home_page"));
    }


}
