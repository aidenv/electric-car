<?php
namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class BankApiController extends Controller
{
    /**
     * Get all enabled banks
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Bank",
     * )
     */
    public function getEnabledBanksAction(Request $request)
    {
        $bankService = $this->get('yilinker_core.service.bank.bank');
        
        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Bank collection.",
            "data" => $bankService->getEnabledBanks()
        ), 200);
    }
}

