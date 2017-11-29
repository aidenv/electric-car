<?php

namespace Yilinker\FrontendBundle\Tests\Controller;

use Carbon\Carbon;
use Yilinker\Bundle\FrontendBundle\Tests\YilinkerWebTestCase;
use Yilinker\Bundle\FrontendBundle\Services\User\Register;
use Yilinker\Bundle\CoreBundle\Entity\User;

class RegisterTest extends YilinkerWebTestCase
{
    public function testAddUser()
    {
        //mock entity manager
        $entityManager = $this->getMockedEntityManager();
        $registerService = new Register($entityManager);

        $newUser = new User();
        $user = $registerService->addUser($newUser);

        $this->assertLessThanOrEqual(Carbon::now(), $user->getDateAdded());
        $this->assertLessThanOrEqual(Carbon::now(), $user->getDateLastModified());
        $this->assertEquals('M', $user->getGender());
        $this->assertTrue($user->getIsActive());
        $this->assertFalse($user->getIsMobileVerified());
        $this->assertFalse($user->getIsEmailVerified());
        $this->assertEquals(0, $user->getLoginCount());
        $this->assertFalse($user->getIsBanned());
    }

    /**
     * Return Mocked Entity Manager
     * @return Doctrine\ORM\EntityManager
     */
    public function getMockedEntityManager()
    {
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
                              ->disableOriginalConstructor()
                              ->getMock();

        $entityManager->expects($this->any())
                      ->method('flush')
                      ->will($this->returnValue(null));

        $entityManager->expects($this->any())
                      ->method('persist')
                      ->will($this->returnValue(null));

        return $entityManager;
    }
}
