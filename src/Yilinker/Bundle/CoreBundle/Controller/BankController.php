<?php

namespace Yilinker\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BankController
 * @package Yilinker\Bundle\CoreBundle\Controller
 */
class BankController extends Controller
{

    /**
     * Get Enabled bank by name
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEnabledBankByNameAction (Request $request)
    {
        $bankKeyword = $request->query->get('bankKeyword');
        $em = $this->getDoctrine()->getManager();
        $bankRepository = $em->getRepository('YilinkerCoreBundle:Bank');
        $bankEntities = $bankRepository->getAllEnabledBanks($bankKeyword);
        $ctr = 0;
        $bankContainer = array();

        if (sizeof($bankEntities) > 0) {

            foreach ($bankEntities as $bankEntity) {
                $bankContainer[$ctr]['id'] = $bankEntity->getBankId();
                $bankContainer[$ctr]['value'] = $bankEntity->getBankName();
                $ctr++;
            }

        }

        return new JsonResponse($bankContainer);
    }

}
