<?php

namespace Yilinker\Bundle\BackendBundle\Services\User;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;

/**
 * Class UserManager
 * @package Yilinker\Bundle\BackendBundle\Services\User
 */
class UserManager
{
    const PAGE_LIMIT = 30;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityManager
     */
    private $userAddressService;

    /**
     * @param EntityManager $entityManager
     * @param $userAddressService
     */
    public function __construct (EntityManager $entityManager, $userAddressService)
    {
        $this->em = $entityManager;
        $this->userAddressService = $userAddressService;
    }

    /**
     * Retrieve the list of registered buyers
     *
     * @param string $searchKeyword
     * @param string $dateFrom
     * @param string $dateTo
     * @param boolean $isActive
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getRegisteredBuyers (
        $searchKeyword = null,
        $dateFrom = null,
        $dateTo = null,
        $isActive = null,
        $offset = 0,
        $limit = self::PAGE_LIMIT
    )
    {
        $userType = User::USER_TYPE_BUYER;

        if (!is_null($dateFrom)) {
            $dateFromCarbon = new Carbon($dateFrom);
            $dateFrom = $dateFromCarbon->startOfDay()->format('Y-m-d H:i:s');
        }

        if (!is_null($dateTo)) {
            $dateToCarbon = new Carbon($dateTo);
            $dateTo = $dateToCarbon->endOfDay()->format('Y-m-d H:i:s');
        }

        $users = $this->em->getRepository('YilinkerCoreBundle:User')
                          ->getRegisteredUser($searchKeyword, $dateFrom, $dateTo, $userType, $isActive, $offset, $limit);

        if (sizeof($users['users']) > 0) {
            foreach ($users['users'] as &$user) {
                $user['userAddress'] = $this->userAddressService->getDefaultUserAddress($user['userEntity']);
            }
        }

        return array (
            'users' => $users['users'],
            'userCount' => $users['count'],
        );
    }

    /**
     * Retrieved registered sellers
     *
     * @param string $searchKeyword
     * @param boolean $isActive
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getRegisteredSellers($searchKeyword = null, $storeType = null, $isActive = null, $offset = 0, $limit = self::PAGE_LIMIT)
    {
        $users = $this->em->getRepository('YilinkerCoreBundle:User')
                      ->getUsersByStore(
                          $searchKeyword, 
                          $storeType, 
                          $isActive, 
                          $offset,
                          $limit
                      );

        if (sizeof($users['users']) > 0) {
            foreach ($users['users'] as &$user) {
                $user['userAddress'] = $this->userAddressService->getDefaultUserAddress($user['userEntity']);
            }
        }

        return array (
            'users' => $users['users'],
            'userCount' => $users['count'],
        );
    }

}
