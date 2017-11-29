<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;

/**
 * Class UserController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_MARKETING')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class VoucherController extends Controller
{
    use FormHandler;

    const VOUCHER_PER_PAGE = 15;
    
    public function listAction(Request $request)
    {
        $perPage = self::VOUCHER_PER_PAGE;
        $form = $this->handleVoucherForm($request);
        if ($request->isMethod('POST') && $request->isXmlHttpRequest() && !$form->isValid()) {
            return $this->render('YilinkerBackendBundle:Voucher:modal.html.twig', compact('form'));
        }

        $em = $this->getDoctrine()->getEntityManager();
        $tbVoucher = $em->getRepository('YilinkerCoreBundle:Voucher');
        $vouchers = $tbVoucher->qb()
                              ->setLimit($perPage)
                              ->paginate($request->get('page', 1));

        $data = array(
            'form'              => $form->createView(),
            'vouchers'          => $vouchers,
            'totalResults'      => $vouchers->count(),
            'perPage'           => $perPage
        );

        return $this->render('YilinkerBackendBundle:Voucher:index.html.twig', $data);
    }

    public function generateCodeAction(Request $request)
    {
        $quantity = $request->get('quantity', 1);
        $em = $this->getDoctrine()->getEntityManager();
        $tbVoucherCode = $em->getRepository('YilinkerCoreBundle:VoucherCode');

        $codes = array();
        while ($quantity--) {
            $codes[] = $tbVoucherCode->generateCode($quantity);
        }

        return new JsonResponse(compact('codes'));
    }
  
    public function editAction(Request $request)
    {
        $id = $request->get('id');
        $em = $this->getDoctrine()->getEntityManager();
        $tbUserOrder = $em->getRepository('YilinkerCoreBundle:UserOrder');
        $tbVoucher = $em->getRepository('YilinkerCoreBundle:Voucher');
        $tbVoucherCode = $em->getRepository('YilinkerCoreBundle:VoucherCode');
        $voucher = $id ? $tbVoucher->find($id): null;
        $editMode = (boolean)$voucher;
        $form = $this->createForm('voucher', $voucher);
        if ($request->isMethod('POST') && $voucher) {
            $form->submit($request);
        }
        else {
            $form->handleRequest($request);
        }

        if ($form->isValid()) {
            if (!$voucher) {
                $voucher = $form->getData();
                if ($form->get('batchUpload')->getData()) {
                    $tbVoucherCode->batchVoucherCodes($voucher);
                }
                $em->persist($voucher);
            }
            $em->flush();
            $form = $this->createForm('voucher');
        }
        $form = $form->createView();
        $transactions = $tbUserOrder->getOrdersWithVoucher($voucher);
        $data = compact('form', 'transactions', 'editMode');

        return $this->render('YilinkerBackendBundle:Voucher:modal.html.twig', $data);
    }

    private function handleVoucherForm($request)
    {
        $form = $this->createForm('voucher');
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            $voucher = $form->getData();
            $em->persist($voucher);
            $em->flush();
            $form = $this->createForm('voucher');
        }

        return $form;
    }
}
