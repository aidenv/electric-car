<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Carbon\Carbon;

class HashLoginListener
{
    private $container;
    private $iv = '2234566123443224';
    private $method = 'aes-128-cbc';
    private $password = 'yilinker-hashlogin-2343246';

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $hash = $request->get('hl', null);
        if ($hash) {
            $id = $this->unhash($hash);
            if ($id) {
                $em = $this->container->get('doctrine.orm.entity_manager');
                $tbUser = $em->getRepository('YilinkerCoreBundle:User');
                $user = $tbUser->find($id);

                $token = new UsernamePasswordToken($user, null, 'buyer', $user->getRoles());
                $this->container->get('security.context')->setToken($token);
                $this->container->get('session')->set('_security_main', serialize($token));

                $event = new InteractiveLoginEvent($request, $token);
                $this->container->get("event_dispatcher")->dispatch("security.interactive_login", $event);
            }
        }
    }

    public function getSalt()
    {
        //validity of salt is only 3 minutes
        $time = Carbon::now()->subMinutes(3);
        $time->second(0);
        $salt = $time->getTimestamp();

        return $salt;
    }
    
    public function hash($id)
    {
        $salt = $this->getSalt();
        $str = $id.'-'.$salt;
        $hash = urlencode(openssl_encrypt(
            urlencode($str),
            $this->method,
            $this->password,
            false,
            $this->iv
        ));

        return $hash;
    }

    public function unhash($hash)
    {
        $str = trim(urldecode(openssl_decrypt(
            urldecode($hash),
            $this->method,
            $this->password,
            false,
            $this->iv
        )));

        $parts = explode('-', $str);
        $id = array_shift($parts);
        $salt = array_shift($parts);

        return $salt != $this->getSalt() ? false: $id;
    }
}