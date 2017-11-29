<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AdminSellerRegistration
 * @Security("has_role('ROLE_ADMIN')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class AdminSellerRegistrationController extends Controller
{

    const PAGE_LIMIT = 30;

    /**
     * Render Manually Created Account
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderManuallyCreatedAccountAction (Request $request)
    {
        return $this->render('YilinkerBackendBundle:AdminSellerRegistration:seller_list.html.twig');
    }

    /**
     * Render Seller Registration
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderSellerRegistrationAction (Request $request)
    {
        $bankService = $this->get('yilinker_core.service.bank.bank');
        $banks = $bankService->getEnabledBanks();

        $data = compact (
            'banks'
        );

        /**
         * TODO:
         *      Javascript BankAccount Validation
         *      Javascript Address Validation
         *      Get Seller Type
         *      Get Accreditation Level
         *      Add column for Account Activation
         *      Get Account Activation
         */

        return $this->render('YilinkerBackendBundle:AdminSellerRegistration:seller_registration.html.twig', $data);
    }

}
