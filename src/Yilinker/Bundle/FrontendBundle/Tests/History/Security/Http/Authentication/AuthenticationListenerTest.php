<?php

namespace Yilinker\FrontendBundle\Tests\Security\Http\Authentication;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;
use Yilinker\Bundle\FrontendBundle\Security\Http\Authentication\AuthenticationListener;

class AuthenticationListenerTest extends YilinkerWebTestCase
{
    /**
     * test failed authentication. registered user (by email)
     */
    public function testOnAuthenticationFailureRegisteredByEmail()
    {
        //email from data fixture : LoadUserData
        $email = 'super.admin@admin.ad';

        $authenticationFailureEvent = $this->getMockBuilder('Symfony\Component\Security\Core\Event\AuthenticationFailureEvent')
                                           ->disableOriginalConstructor()
                                           ->setMethods(array(
                                                   'getAuthenticationToken',
                                                   'getUser'
                                               )
                                           )
                                           ->getMock();

        $authenticationFailureEvent->expects($this->any())
                                   ->method('getAuthenticationToken')
                                   ->will($this->returnSelf());

        $authenticationFailureEvent->expects($this->any())
                                   ->method('getUser')
                                   ->will($this->returnValue($email));

        $this->assertEquals('super.admin@admin.ad', $authenticationFailureEvent->getAuthenticationToken()->getUser());


        $client = $this->client;
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');

        $authenticationListener = new AuthenticationListener($entityManager);
        $authenticationListener->onAuthenticationFailure($authenticationFailureEvent);

        $user = $userRepository->loadUserByUsername($email);

        $this->assertNotNull($user);
        $this->assertGreaterThan('0', $user->getFailedLoginCount());
    }

    /**
     * test failed authentication. unregistered user
     */
    public function testOnAuthenticationFailureNotRegistered()
    {
        $username = 'nonexistentuser';

        $authenticationFailureEvent = $this->getMockBuilder('Symfony\Component\Security\Core\Event\AuthenticationFailureEvent')
                                           ->disableOriginalConstructor()
                                           ->setMethods(array(
                                                   'getAuthenticationToken',
                                                   'getUser'
                                                )
                                           )
                                           ->getMock();

        $authenticationFailureEvent->expects($this->any())
                                   ->method('getAuthenticationToken')
                                   ->will($this->returnSelf());

        $authenticationFailureEvent->expects($this->any())
                                   ->method('getUser')
                                   ->will($this->returnValue($username));

        $this->assertEquals('nonexistentuser', $authenticationFailureEvent->getAuthenticationToken()->getUser());

        $client = $this->client;
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');

        $authenticationListener = new AuthenticationListener($entityManager);
        $authenticationListener->onAuthenticationFailure($authenticationFailureEvent);

        $user = $userRepository->loadUserByUsername($username);

        $this->assertNull($user);
    }

    /**
     * Test authenticated user
     */
    public function testOnAuthenticationSuccess()
    {
        $client = $this->client;
        $container = $client->getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');
        $user = $userRepository->loadUserByUsername('superadmin');

        $interactiveLoginEvent = $this->getMockBuilder('Symfony\Component\Security\Http\Event\InteractiveLoginEvent')
                                      ->disableOriginalConstructor()
                                      ->setMethods(array(
                                            'getAuthenticationToken',
                                            'getUser',
                                            'getRequest',
                                            'getClientIp'
                                          )
                                      )
                                      ->getMock();

        $interactiveLoginEvent->expects($this->any())
                              ->method('getAuthenticationToken')
                              ->will($this->returnSelf());

        $interactiveLoginEvent->expects($this->any())
                              ->method('getUser')
                              ->will($this->returnValue($user));

        $interactiveLoginEvent->expects($this->any())
                              ->method('getRequest')
                              ->will($this->returnSelf());

        $interactiveLoginEvent->expects($this->any())
                              ->method('getClientIp')
                              ->will($this->returnValue('127.0.0.1'));

        $this->assertEquals($user, $interactiveLoginEvent->getAuthenticationToken()->getUser());
        $this->assertEquals('127.0.0.1', $interactiveLoginEvent->getRequest()->getClientIp());

        $authenticationListener = new AuthenticationListener($entityManager);
        $authenticationListener->onAuthenticationSuccess($interactiveLoginEvent);

        $this->assertGreaterThan('0', $user->getLoginCount());
        $this->assertEquals('0', $user->getFailedLoginCount());
        $this->assertNull($user->getLockDuration());
    }

    /**
     * Test when login attempts reach the initial lock
     */
    public function testLockAccountInitialLockSuccess()
    {
        $user = $this->getUserEntity();
        $user->setFailedLoginCount(5);

        $authenticationListener = new AuthenticationListener($this->getEntityManager());
        $authenticationListener->lockAccount($user);

        $timeExpected = Carbon::now()->addMinute(2)->getTimestamp(); //add 2 minutes because its set in the listener as 2 minutes
        $lockDuration = $user->getLockDuration()->getTimestamp();

        $this->assertNotNull($user->getLockDuration());
        $this->assertEquals($timeExpected, $lockDuration);
    }

    /**
     * Test when login attempts reach the initial lock
     */
    public function testLockAccountInitialLockFail()
    {
        $user = $this->getUserEntity();
        $user->setFailedLoginCount(4);

        $authenticationListener = new AuthenticationListener($this->getEntityManager());
        $authenticationListener->lockAccount($user);

        $this->assertNull($user->getLockDuration());
    }

    /**
     * Test when user exceeds the initial lock and secondary lock
     */
    public function testLockAccountFirstIntervalSuccess()
    {
        $user = $this->getUserEntity();
        $user->setFailedLoginCount(8);

        $authenticationListener = new AuthenticationListener($this->getEntityManager());
        $authenticationListener->lockAccount($user);

        $timeExpected = Carbon::now()->addMinute(7)->getTimestamp(); //add 7 minutes (initial is 2 mins + interval is 5mins)
        $lockDuration = $user->getLockDuration()->getTimestamp();

        $this->assertNotNull($user->getLockDuration());
        $this->assertEquals($timeExpected, $lockDuration);
    }


    /**
     * Test when user exceeds the initial lock and but doesnt exceed secondary lock
     */
    public function testLockAccountFirstIntervalFail()
    {
        $user = $this->getUserEntity();
        $user->setFailedLoginCount(7);
        $user->setLockDuration(Carbon::now()->addMinute(2)); //done with initial lock

        $authenticationListener = new AuthenticationListener($this->getEntityManager());
        $authenticationListener->lockAccount($user);

        $timeExpected = Carbon::now()->addMinute(2)->getTimestamp(); //add 2 minutes (initial is 2 mins + havent reach first interval limit which is 8)
        $lockDuration = $user->getLockDuration()->getTimestamp();

        $this->assertNotNull($user->getLockDuration());
        $this->assertEquals($timeExpected, $lockDuration);
    }

    /**
     * Test when user exceeds the initial lock and tertiary lock
     */
    public function testLockAccountSecondIntervalSuccess()
    {
        $user = $this->getUserEntity();
        $user->setFailedLoginCount(11);

        $authenticationListener = new AuthenticationListener($this->getEntityManager());
        $authenticationListener->lockAccount($user);

        $timeExpected = Carbon::now()->addMinute(12)->getTimestamp(); //add 12 minutes (initial is 2 mins + 2 interval[5 min per interval])
        $lockDuration = $user->getLockDuration()->getTimestamp();

        $this->assertNotNull($user->getLockDuration());
        $this->assertEquals($timeExpected, $lockDuration);
    }


    /**
     * Test when user exceeds the initial lock and secondary lock but doesnt exceed tertiary lock
     */
    public function testLockAccountSecondIntervalFail()
    {
        $user = $this->getUserEntity();
        $user->setFailedLoginCount(10);
        $user->setLockDuration(Carbon::now()->addMinute(7)); //done with secondary lock

        $authenticationListener = new AuthenticationListener($this->getEntityManager());
        $authenticationListener->lockAccount($user);

        $timeExpected = Carbon::now()->addMinute(7)->getTimestamp(); //add 12 minutes (initial is 2 mins + 1 interval[5 min per interval])
        $lockDuration = $user->getLockDuration()->getTimestamp();

        $this->assertNotNull($user->getLockDuration());
        $this->assertEquals($timeExpected, $lockDuration);
    }

    public function getUserEntity()
    {
        $entityManager = $this->getEntityManager();

        $userRepository = $entityManager->getRepository('YilinkerCoreBundle:User');
        return $userRepository->loadUserByUsername('superadmin');
    }

    public function getEntityManager()
    {
        $client = $this->client;
        $container = $client->getContainer();
        return $container->get('doctrine.orm.entity_manager');
    }
}

