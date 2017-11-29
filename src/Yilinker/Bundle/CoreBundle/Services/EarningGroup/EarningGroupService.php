<?php

namespace Yilinker\Bundle\CoreBundle\Services\EarningGroup;

use Yilinker\Bundle\CoreBundle\Entity\User;

class EarningGroupService
{
    private $em;

    private $assetsHelper;

    public function __construct($em, $assetsHelper)
    {
        $this->em = $em;
        $this->assetsHelper = $assetsHelper;
    }

    public function getUserEarningGroups($user, $excludedStatus)
    {
        $store = $user->getStore();
        $earningPrivilege = $store->getEarningPrivilege();

        $earningGroupRepository = $this->em->getRepository("YilinkerCoreBundle:EarningGroup");
        $earningGroups = $earningGroupRepository->getUserEarningGroupsDetails(
                            $user,
                            $earningPrivilege,
                            $excludedStatus
                        );

        foreach ($earningGroups as $key => &$earningGroup) {
            $earningGroup["imageLocation"] = $this->assetsHelper->getUrl($earningGroup["imageLocation"], "cms");
            $earningGroup["totalAmount"] = number_format($earningGroup["totalAmount"], 2);
            $earningGroup["currencyCode"] = "P";
        }

        return $earningGroups;
    }

    public function getUserEarningsByGroup(
        $user, 
        $earningGroup, 
        $limit = 10, 
        $offset = 0, 
        $excludedStatus = array()
    ){
        $store = $user->getStore();
        $earningPrivilege = $store->getEarningPrivilege();

        $earningGroupRepository = $this->em->getRepository("YilinkerCoreBundle:EarningGroup");
        $earningTypes = $earningGroupRepository->getEarningTypesByPrivilegeLevel(
                            $earningPrivilege
                        );

        $earningRepository = $this->em->getRepository("YilinkerCoreBundle:Earning");
        $earnings = $earningRepository->getUserEarningsIn(
                        $user, 
                        "e.dateLastModified", 
                        "DESC", 
                        $earningGroup, 
                        $earningTypes, 
                        $limit, 
                        $offset, 
                        $excludedStatus
                    );

        $userEarnings = array();
        foreach($earnings as $earning){
            $earningType = $earning->getEarningType();
            array_push($userEarnings, array(
                "date" => $earning->getDateLastModified()->format("m/d/Y"),
                "description" => $earning->getDescription(),
                "earningTypeName" => $earningType->getName(),
                "earningTypeId" => $earningType->getEarningTypeId(),
                "amount" => number_format($earning->getAmount(), 2),
                "currencyCode" => "P",
                "statusId" => $earning->getStatus(false),
                "status" => $earning->getStatus(true)
            ));
        }

        return $userEarnings;
    }


    /**
     * [getUserPoint of seller or buyer]
     * 
     */
    public function getUserPoint($user)
    {
        $usertype = $user->getUserType();
        $totalEarnings = 0;

        if ($usertype == User::USER_TYPE_BUYER) {
            $totalEarnings = $this->em->getRepository("YilinkerCoreBundle:UserPoint")->sumUserPoint($user->getUserId());
        
        } else if ($usertype == User::USER_TYPE_SELLER) {
            $totalEarnings = $this->em->getRepository("YilinkerCoreBundle:Earning")->getTotalEarningByUser($user);
        }
        return $totalEarnings;
    }
}
