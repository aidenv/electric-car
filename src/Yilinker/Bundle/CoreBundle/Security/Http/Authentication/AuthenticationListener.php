<?php

namespace Yilinker\Bundle\CoreBundle\Security\Http\Authentication;

use Carbon\Carbon;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;

class AuthenticationListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * limit of login attempts
     *
     * @var int
     */
    private $initialLockAttempt = 5;

    /**
     * counter if login attempts exceeds $initialLockAttempt
     * @var int
     */
    private $intervalLockAttempt = 3;

    /**
     * initial account lock duration
     * @var int
     */
    private $initialLockDuration = 120; //2 minutes

    /**
     * added duration of lock account if login attempts exceeds $initialLockAttempt
     * @var int
     */
    private $intervalLockDuration = 300; //5 minutes

    private $container;

    public function __construct(EntityManager $entityManager, $container)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    public function __set($field, $value)
    {
        $this->$field = $value;
    }

    /**
     * invalid login
     * @param AuthenticationFailureEvent $authenticationFailureEvent
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $authenticationFailureEvent)
    {
        $username = $authenticationFailureEvent->getAuthenticationToken()->getUser();

        $user = $this->entityManager
                     ->getRepository('YilinkerCoreBundle:User')
                     ->loadUserByUsername($username);

        if($user)
        {
            if ($user instanceof User) {
                $user->setLastFailedLoginDate(Carbon::now())
                    ->setFailedLoginCount((int)$user->getFailedLoginCount() + 1);

                if($user->getFailedLoginCount() >= $this->initialLockAttempt)
                {
                    $this->lockAccount($user);
                }

                $this->entityManager->flush();
            }

        }
    }

    /**
     * successful login
     * @param InteractiveLoginEvent $interactiveLoginEvent
     */
    public function onAuthenticationSuccess(InteractiveLoginEvent $interactiveLoginEvent)
    {
        $user = $interactiveLoginEvent->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            if ($user->getUserType() == User::USER_TYPE_BUYER) {
                $country = $user->getCountry();
                if ($country && $country->getDomain() != $_SERVER['HTTP_HOST']) {
                    $hashLoginListener = $this->container->get('yilinker_core.action.user.hash.login');
                    $hash = $hashLoginListener->hash($user->getUserId());
                    
                    header('Location: http://'.$country->getDomain().'?hl='.$hash);
                    die();
                }
            }
            elseif ($user->getUserType() == User::USER_TYPE_SELLER) {
                $country = $user->getCountry();
                if ($country) {
                    $session = $interactiveLoginEvent->getRequest()->getSession();
                    $session->set('_country', $country->getCode(true));   
                }
            }

            $user->setLastLoginDate(Carbon::now())
                 ->setLastLoginIp($interactiveLoginEvent->getRequest()->getClientIp())
                 ->setLoginCount((int)$user->getLoginCount() + 1)
                 ->setFailedLoginCount(0)
                 ->setLockDuration(null);

            $this->entityManager->flush();
        }

    }

    /**
     * Sets the date and time when will the account will be unlocked
     *
     * @param $user
     *
     * NOTE : @param user is referenced
     */
    public function lockAccount(&$user)
    {
        $failedLoginCount = $user->getFailedLoginCount();
        if($failedLoginCount == $this->initialLockAttempt)
        {
            $user->setLockDuration(Carbon::now()->addSeconds($this->initialLockDuration));
        }
        elseif($failedLoginCount > $this->initialLockAttempt)
        {
            $failedLoginCount -= $this->initialLockAttempt;
            if($failedLoginCount%$this->intervalLockAttempt == 0)
            {
                $lockDuration = $failedLoginCount/$this->intervalLockAttempt * $this->intervalLockDuration;
                $lockDuration += $this->initialLockDuration;
                $user->setLockDuration(Carbon::now()->addSeconds($lockDuration));
            }
        }
    }
}
